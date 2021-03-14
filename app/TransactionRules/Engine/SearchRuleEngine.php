<?php

/*
 * SearchRuleEngine.php
 * Copyright (c) 2021 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\TransactionRules\Engine;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Search\SearchInterface;
use FireflyIII\TransactionRules\Factory\ActionFactory;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class SearchRuleEngine
 */
class SearchRuleEngine implements RuleEngineInterface
{
    private User       $user;
    private Collection $rules;
    private array      $operators;
    private Collection $groups;
    private array      $resultCount;

    public function __construct()
    {
        $this->rules       = new Collection;
        $this->groups      = new Collection;
        $this->operators   = [];
        $this->resultCount = [];
    }

    /**
     * @inheritDoc
     */
    public function setUser(User $user): void
    {
        $this->user      = $user;
        $this->operators = [];
    }

    /**
     * @inheritDoc
     */
    public function setRules(Collection $rules): void
    {
        Log::debug(__METHOD__);
        foreach ($rules as $rule) {
            if ($rule instanceof Rule) {
                Log::debug(sprintf('Adding a rule to the SearchRuleEngine: #%d ("%s")', $rule->id, $rule->title));
                $this->rules->push($rule);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function setRuleGroups(Collection $ruleGroups): void
    {
        Log::debug(__METHOD__);
        foreach ($ruleGroups as $group) {
            if ($group instanceof RuleGroup) {
                Log::debug(sprintf('Adding a rule group to the SearchRuleEngine: #%d ("%s")', $group->id, $group->title));
                $this->groups->push($group);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function addOperator(array $operator): void
    {
        Log::debug('Add extra operator: ', $operator);
        $this->operators[] = $operator;
    }

    /**
     * @inheritDoc
     * @throws FireflyException
     */
    public function fire(): void
    {
        $this->resultCount = [];
        Log::debug('SearchRuleEngine::fire()!');

        // if rules and no rule groups, file each rule separately.
        if (0 !== $this->rules->count()) {
            Log::debug(sprintf('SearchRuleEngine:: found %d rule(s) to fire.', $this->rules->count()));
            foreach ($this->rules as $rule) {
                $this->fireRule($rule);
            }
            Log::debug('SearchRuleEngine:: done processing all rules!');

            return;
        }
        if (0 !== $this->groups->count()) {
            Log::debug(sprintf('SearchRuleEngine:: found %d rule group(s) to fire.', $this->groups->count()));
            // fire each group:
            /** @var RuleGroup $group */
            foreach ($this->groups as $group) {
                $this->fireGroup($group);
            }
        }
        Log::debug('SearchRuleEngine:: done processing all rules!');
    }

    /**
     *
     */
    public function find(): Collection
    {
        Log::debug('SearchRuleEngine::find()');
        $collection = new Collection;
        foreach ($this->rules as $rule) {
            $found = new Collection;
            if (true === $rule->strict) {
                $found = $this->findStrictRule($rule);
            }
            if (false === $rule->strict) {
                $found = $this->findNonStrictRule($rule);
            }
            $collection = $collection->merge($found);
        }

        return $collection->unique();
    }

    /**
     * Return the number of changed transactions from the previous "fire" action.
     *
     * @return int
     */
    public function getResults(): int
    {
        return count($this->resultCount);
    }

    /**
     * Returns true if the rule has been triggered.
     *
     * @param Rule $rule
     *
     * @return bool
     * @throws FireflyException
     */
    private function fireRule(Rule $rule): bool
    {
        Log::debug(sprintf('Now going to fire rule #%d', $rule->id));
        if (true === $rule->strict) {
            Log::debug(sprintf('Rule #%d is a strict rule.', $rule->id));

            return $this->fireStrictRule($rule);
        }
        Log::debug(sprintf('Rule #%d is not strict rule.', $rule->id));

        return $this->fireNonStrictRule($rule);
    }

    /**
     * @param Rule       $rule
     * @param Collection $collection
     *
     * @throws FireflyException
     */
    private function processResults(Rule $rule, Collection $collection): void
    {
        Log::debug(sprintf('SearchRuleEngine:: Going to process %d results.', $collection->count()));
        /** @var array $group */
        foreach ($collection as $group) {
            $this->processTransactionGroup($rule, $group);
        }
    }

    /**
     * @param Rule  $rule
     * @param array $group
     *
     * @throws FireflyException
     */
    private function processTransactionGroup(Rule $rule, array $group): void
    {
        Log::debug(sprintf('SearchRuleEngine:: Will now execute actions on transaction group #%d', $group['id']));
        /** @var array $transaction */
        foreach ($group['transactions'] as $transaction) {
            $this->processTransactionJournal($rule, $transaction);
        }
    }

    /**
     * @param Rule  $rule
     * @param array $transaction
     *
     * @throws FireflyException
     */
    private function processTransactionJournal(Rule $rule, array $transaction): void
    {
        Log::debug(sprintf('SearchRuleEngine:: Will now execute actions on transaction journal #%d', $transaction['transaction_journal_id']));
        /** @var RuleAction $ruleAction */
        foreach ($rule->ruleActions as $ruleAction) {
            $break = $this->processRuleAction($ruleAction, $transaction);
            if (true === $break) {
                break;
            }
        }
    }

    /**
     * @param RuleAction $ruleAction
     * @param array      $transaction
     *
     * @return bool
     * @throws FireflyException
     */
    private function processRuleAction(RuleAction $ruleAction, array $transaction): bool
    {
        Log::debug(sprintf('Executing rule action "%s" with value "%s"', $ruleAction->action_type, $ruleAction->action_value));
        $actionClass = ActionFactory::getAction($ruleAction);
        $result      = $actionClass->actOnArray($transaction);
        $journalId   = $transaction['transaction_journal_id'] ?? 0;
        if (true === $result) {
            $this->resultCount[$journalId] = isset($this->resultCount[$journalId]) ? $this->resultCount[$journalId]++ : 1;
            Log::debug(
                sprintf(
                    'Action "%s" on journal #%d was executed, so count a result. Updated transaction journal count is now %d.',
                    $ruleAction->action_type,
                    $transaction['transaction_journal_id'] ?? 0,
                    count($this->resultCount),
                )
            );
        }
        if (false === $result) {
            Log::debug(sprintf('Action "%s" reports NO changes were made.', $ruleAction->action_type));
        }

        // pick up from the action if it actually acted or not:


        if ($ruleAction->stop_processing) {
            Log::debug(sprintf('Rule action "%s" asks to break, so break!', $ruleAction->action_type));

            return true;
        }

        return false;
    }

    /**
     * Return true if the rule is fired (the collection is larger than zero).
     *
     * @param Rule $rule
     *
     * @return bool
     * @throws FireflyException
     */
    private function fireStrictRule(Rule $rule): bool
    {
        Log::debug(sprintf('SearchRuleEngine::fireStrictRule(%d)!', $rule->id));
        $collection = $this->findStrictRule($rule);

        $this->processResults($rule, $collection);
        Log::debug(sprintf('SearchRuleEngine:: done processing strict rule #%d', $rule->id));

        $result = $collection->count() > 0;
        if (true === $result) {
            Log::debug(sprintf('SearchRuleEngine:: rule #%d was triggered (on %d transaction(s)).', $rule->id, $collection->count()));

            return true;
        }
        Log::debug(sprintf('SearchRuleEngine:: rule #%d was not triggered (on %d transaction(s)).', $rule->id, $collection->count()));

        return false;
    }

    /**
     * Return true if the rule is fired (the collection is larger than zero).
     *
     * @param Rule $rule
     *
     * @return bool
     * @throws FireflyException
     */
    private function fireNonStrictRule(Rule $rule): bool
    {
        Log::debug(sprintf('SearchRuleEngine::fireNonStrictRule(%d)!', $rule->id));
        $collection = $this->findNonStrictRule($rule);

        $this->processResults($rule, $collection);
        Log::debug(sprintf('SearchRuleEngine:: done processing non-strict rule #%d', $rule->id));

        return $collection->count() > 0;
    }

    /**
     * Finds the transactions a strict rule will execute on.
     *
     * @param Rule $rule
     *
     * @return Collection
     */
    private function findStrictRule(Rule $rule): Collection
    {
        Log::debug(sprintf('Now in findStrictRule(#%d)', $rule->id ?? 0));
        $searchArray = [];
        /** @var RuleTrigger $ruleTrigger */
        foreach ($rule->ruleTriggers as $ruleTrigger) {
            // if needs no context, value is different:
            $needsContext = config(sprintf('firefly.search.operators.%s.needs_context', $ruleTrigger->trigger_type)) ?? true;
            if (false === $needsContext) {
                Log::debug(sprintf('SearchRuleEngine:: add a rule trigger: %s:true', $ruleTrigger->trigger_type));
                $searchArray[$ruleTrigger->trigger_type][] = 'true';
            }
            if (true === $needsContext) {
                Log::debug(sprintf('SearchRuleEngine:: add a rule trigger: %s:"%s"', $ruleTrigger->trigger_type, $ruleTrigger->trigger_value));
                $searchArray[$ruleTrigger->trigger_type][] = sprintf('"%s"', $ruleTrigger->trigger_value);
            }
        }

        // add local operators:
        foreach ($this->operators as $operator) {
            Log::debug(sprintf('SearchRuleEngine:: add local added operator: %s:"%s"', $operator['type'], $operator['value']));
            $searchArray[$operator['type']][] = sprintf('"%s"', $operator['value']);
        }
        $date = today(config('app.timezone'));
        if ($this->hasSpecificJournalTrigger($searchArray)) {
            $date = $this->setDateFromJournalTrigger($searchArray);
        }


        // build and run the search engine.
        $searchEngine = app(SearchInterface::class);
        $searchEngine->setUser($this->user);
        $searchEngine->setPage(1);
        $searchEngine->setLimit(31337);
        $searchEngine->setDate($date);

        foreach ($searchArray as $type => $searches) {
            foreach ($searches as $value) {
                $searchEngine->parseQuery(sprintf('%s:%s', $type, $value));
            }
        }

        $result = $searchEngine->searchTransactions();

        return $result->getCollection();
    }

    /**
     * @param Rule $rule
     *
     * @return Collection
     */
    private function findNonStrictRule(Rule $rule): Collection
    {
        // start a search query for individual each trigger:
        $total = new Collection;
        $count = 0;
        /** @var RuleTrigger $ruleTrigger */
        foreach ($rule->ruleTriggers as $ruleTrigger) {
            if ('user_action' === $ruleTrigger->trigger_type) {
                Log::debug('Skip trigger type.');
                continue;
            }
            $searchArray  = [];
            $needsContext = config(sprintf('firefly.search.operators.%s.needs_context', $ruleTrigger->trigger_type)) ?? true;
            if (false === $needsContext) {
                Log::debug(sprintf('SearchRuleEngine:: non strict, will search for: %s:true', $ruleTrigger->trigger_type));
                $searchArray[$ruleTrigger->trigger_type] = 'true';
            }
            if (true === $needsContext) {
                Log::debug(sprintf('SearchRuleEngine:: non strict, will search for: %s:"%s"', $ruleTrigger->trigger_type, $ruleTrigger->trigger_value));
                $searchArray[$ruleTrigger->trigger_type] = sprintf('"%s"', $ruleTrigger->trigger_value);
            }

            // then, add local operators as well:
            foreach ($this->operators as $operator) {
                Log::debug(sprintf('SearchRuleEngine:: add local added operator: %s:"%s"', $operator['type'], $operator['value']));
                $searchArray[$operator['type']] = sprintf('"%s"', $operator['value']);
            }

            // build and run the search engine.
            $searchEngine = app(SearchInterface::class);
            $searchEngine->setUser($this->user);
            $searchEngine->setPage(1);
            $searchEngine->setLimit(31337);

            foreach ($searchArray as $type => $value) {
                $searchEngine->parseQuery(sprintf('%s:%s', $type, $value));
            }

            $result     = $searchEngine->searchTransactions();
            $collection = $result->getCollection();
            Log::debug(sprintf('Found in this run, %d transactions', $collection->count()));
            $total = $total->merge($collection);
            Log::debug(sprintf('Total collection is now %d transactions', $total->count()));
            $count++;
        }
        Log::debug(sprintf('Total collection is now %d transactions', $total->count()));
        Log::debug(sprintf('Done running %d trigger(s)', $count));

        // make collection unique
        $unique = $total->unique(
            function (array $group) {
                $str = '';
                foreach ($group['transactions'] as $transaction) {
                    $str = sprintf('%s%d', $str, $transaction['transaction_journal_id']);
                }
                $key = sprintf('%d%s', $group['id'], $str);
                Log::debug(sprintf('Return key: %s ', $key));

                return $key;
            }
        );

        Log::debug(sprintf('SearchRuleEngine:: Found %d transactions using search engine.', $unique->count()));

        return $unique;
    }

    /**
     * Search in the triggers of this particular search and if it contains
     * one search operator for "journal_id" it means the date ranges
     * in the search may need to be updated.
     *
     * @param array $array
     *
     * @return bool
     */
    private function hasSpecificJournalTrigger(array $array): bool
    {
        Log::debug('Now in hasSpecificJournalTrigger.');
        $journalTrigger = false;
        $dateTrigger    = false;
        foreach ($array as $triggerName => $values) {
            if ('journal_id' === $triggerName) {
                if (is_array($values) && 1 === count($values)) {
                    Log::debug('Found a journal_id trigger with 1 journal, true.');
                    $journalTrigger = true;
                }
            }
            if (in_array($triggerName, ['date_is', 'date', 'on', 'date_before', 'before', 'date_after', 'after'], true)) {
                Log::debug('Found a date related trigger, set to true.');
                $dateTrigger = true;
            }
        }
        $result = $journalTrigger && $dateTrigger;
        Log::debug(sprintf('Result of hasSpecificJournalTrigger is %s.', var_export($result, true)));

        return $result;
    }

    /**
     * @param RuleGroup $group
     *
     * @return bool
     */
    private function fireGroup(RuleGroup $group): bool
    {
        $all = false;
        Log::debug(sprintf('Going to fire group #%d with %d rule(s)', $group->id, $group->rules->count()));
        /** @var  $rule */
        foreach ($group->rules as $rule) {
            Log::debug(sprintf('Going to fire rule #%d from group #%d', $rule->id, $group->id));
            $result = $this->fireRule($rule);
            if (true === $result) {
                $all = true;
            }
            if (true === $result && true === $rule->stop_processing) {
                Log::debug(sprintf('The rule was triggered and rule->stop_processing = true, so group #%d will stop processing further rules.', $group->id));

                return true;
            }
        }

        return $all;
    }

    /**
     * @param array $array
     *
     * @return Carbon
     */
    private function setDateFromJournalTrigger(array $array): Carbon
    {
        Log::debug('Now in setDateFromJournalTrigger()');
        $journalId = 0;
        foreach ($array as $triggerName => $values) {
            if ('journal_id' === $triggerName) {
                if (is_array($values) && 1 === count($values)) {
                    $journalId = (int)trim(($values[0] ?? '"0"'), '"'); // follows format "123".
                    Log::debug(sprintf('Found journal ID #%d', $journalId));
                }
            }
        }
        if (0 !== $journalId) {
            $repository = app(JournalRepositoryInterface::class);
            $repository->setUser($this->user);
            $journal    = $repository->findNull($journalId);
            if (null !== $journal) {
                $date = $journal->date;
                Log::debug(sprintf('Found journal #%d with date %s.', $journal->id, $journal->date->format('Y-m-d')));

                return $date;
            }
        }
        Log::debug('Found no journal, return default date.');

        return today(config('app.timezone'));
    }
}

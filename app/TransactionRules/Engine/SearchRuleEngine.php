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
use FireflyIII\Models\Note;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Search\SearchInterface;
use FireflyIII\TransactionRules\Factory\ActionFactory;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class SearchRuleEngine
 */
class SearchRuleEngine implements RuleEngineInterface
{
    private readonly Collection $groups;
    private array      $operators;
    private bool       $refreshTriggers;
    private array      $resultCount;
    private readonly Collection $rules;
    private User       $user;

    public function __construct()
    {
        $this->rules           = new Collection();
        $this->groups          = new Collection();
        $this->operators       = [];
        $this->resultCount     = [];

        // always collect the triggers from the database, unless indicated otherwise.
        $this->refreshTriggers = true;
    }

    public function addOperator(array $operator): void
    {
        app('log')->debug('Add extra operator: ', $operator);
        $this->operators[] = $operator;
    }

    public function find(): Collection
    {
        app('log')->debug('SearchRuleEngine::find()');
        $collection = new Collection();
        foreach ($this->rules as $rule) {
            $found      = new Collection();
            if (true === $rule->strict) {
                $found = $this->findStrictRule($rule);
            }
            if (false === $rule->strict) {
                $found = $this->findNonStrictRule($rule);
            }
            $collection = $collection->merge($found);
        }
        $result     = $collection->unique();
        app('log')->debug(sprintf('SearchRuleEngine::find() returns %d unique transactions.', $result->count()));

        return $result;
    }

    /**
     * Finds the transactions a strict rule will execute on.
     */
    private function findStrictRule(Rule $rule): Collection
    {
        app('log')->debug(sprintf('Now in findStrictRule(#%d)', $rule->id ?? 0));
        $searchArray  = [];
        $triggers     = [];
        if ($this->refreshTriggers) {
            $triggers = $rule->ruleTriggers()->orderBy('order', 'ASC')->get();
        }
        if (!$this->refreshTriggers) {
            $triggers = $rule->ruleTriggers;
        }

        /** @var RuleTrigger $ruleTrigger */
        foreach ($triggers as $ruleTrigger) {
            if (false === $ruleTrigger->active) {
                continue;
            }
            $contextSearch = $ruleTrigger->trigger_type;
            if (str_starts_with((string) $ruleTrigger->trigger_type, '-')) {
                $contextSearch = substr((string) $ruleTrigger->trigger_type, 1);
            }

            // if the trigger needs no context, value is different:
            $needsContext  = (bool) (config(sprintf('search.operators.%s.needs_context', $contextSearch)) ?? true);
            if (false === $needsContext) {
                app('log')->debug(sprintf('SearchRuleEngine:: add a rule trigger (no context): %s:true', $ruleTrigger->trigger_type));
                $searchArray[$ruleTrigger->trigger_type][] = 'true';
            }
            if (true === $needsContext) {
                app('log')->debug(sprintf('SearchRuleEngine:: add a rule trigger (context): %s:"%s"', $ruleTrigger->trigger_type, $ruleTrigger->trigger_value));
                $searchArray[$ruleTrigger->trigger_type][] = sprintf('"%s"', $ruleTrigger->trigger_value);
            }
        }

        // add local operators:
        foreach ($this->operators as $operator) {
            app('log')->debug(sprintf('SearchRuleEngine:: add local added operator: %s:"%s"', $operator['type'], $operator['value']));
            $searchArray[$operator['type']][] = sprintf('"%s"', $operator['value']);
        }
        $date         = today(config('app.timezone'));
        if ($this->hasSpecificJournalTrigger($searchArray)) {
            $date = $this->setDateFromJournalTrigger($searchArray);
        }

        // build and run the search engine.
        $searchEngine = app(SearchInterface::class);
        $searchEngine->setUser($this->user);
        $searchEngine->setPage(1);
        $searchEngine->setLimit(31337);
        $searchEngine->setDate($date);
        app('log')->debug('Search array', $searchArray);
        foreach ($searchArray as $type => $searches) {
            foreach ($searches as $value) {
                $query = sprintf('%s:%s', $type, $value);
                app('log')->debug(sprintf('SearchRuleEngine:: add query "%s"', $query));
                $searchEngine->parseQuery($query);
            }
        }

        $result       = $searchEngine->searchTransactions();

        return $result->getCollection();
    }

    /**
     * Search in the triggers of this particular search and if it contains
     * one search operator for "journal_id" it means the date ranges
     * in the search may need to be updated.
     */
    private function hasSpecificJournalTrigger(array $array): bool
    {
        app('log')->debug('Now in hasSpecificJournalTrigger.');
        $journalTrigger = false;
        $dateTrigger    = false;
        foreach ($array as $triggerName => $values) {
            if ('journal_id' === $triggerName && is_array($values) && 1 === count($values)) {
                app('log')->debug('Found a journal_id trigger with 1 journal, true.');
                $journalTrigger = true;
            }
            if (in_array($triggerName, ['date_is', 'date', 'on', 'date_before', 'before', 'date_after', 'after'], true)) {
                app('log')->debug('Found a date related trigger, set to true.');
                $dateTrigger = true;
            }
        }
        $result         = $journalTrigger && $dateTrigger;
        app('log')->debug(sprintf('Result of hasSpecificJournalTrigger is %s.', var_export($result, true)));

        return $result;
    }

    private function setDateFromJournalTrigger(array $array): Carbon
    {
        app('log')->debug('Now in setDateFromJournalTrigger()');
        $journalId = 0;
        foreach ($array as $triggerName => $values) {
            if ('journal_id' === $triggerName && is_array($values) && 1 === count($values)) {
                $journalId = (int) trim($values[0] ?? '"0"', '"'); // follows format "123".
                app('log')->debug(sprintf('Found journal ID #%d', $journalId));
            }
        }
        if (0 !== $journalId) {
            $repository = app(JournalRepositoryInterface::class);
            $repository->setUser($this->user);
            $journal    = $repository->find($journalId);
            if (null !== $journal) {
                $date = $journal->date;
                app('log')->debug(sprintf('Found journal #%d with date %s.', $journal->id, $journal->date->format('Y-m-d')));

                return $date;
            }
        }
        app('log')->debug('Found no journal, return default date.');

        return today(config('app.timezone'));
    }

    public function setUser(User $user): void
    {
        $this->user      = $user;
        $this->operators = [];
    }

    private function findNonStrictRule(Rule $rule): Collection
    {
        app('log')->debug(sprintf('findNonStrictRule(#%d)', $rule->id));
        // start a search query for individual each trigger:
        $total    = new Collection();
        $count    = 0;
        $triggers = [];
        if ($this->refreshTriggers) {
            app('log')->debug('Will refresh triggers.');
            $triggers = $rule->ruleTriggers()->orderBy('order', 'ASC')->get();
        }
        if (!$this->refreshTriggers) {
            app('log')->debug('Will not refresh triggers.');
            $triggers = $rule->ruleTriggers;
        }
        app('log')->debug(sprintf('Will run %d trigger(s).', count($triggers)));

        /** @var RuleTrigger $ruleTrigger */
        foreach ($triggers as $ruleTrigger) {
            app('log')->debug(sprintf('Now at rule trigger #%d: %s:"%s" (%s).', $ruleTrigger->id, $ruleTrigger->trigger_type, $ruleTrigger->trigger_value, var_export($ruleTrigger->stop_processing, true)));
            if (false === $ruleTrigger->active) {
                app('log')->debug('Trigger is not active, continue.');

                continue;
            }
            if ('user_action' === $ruleTrigger->trigger_type) {
                app('log')->debug('Skip trigger type. continue.');

                continue;
            }
            $searchArray  = [];
            $needsContext = config(sprintf('search.operators.%s.needs_context', $ruleTrigger->trigger_type)) ?? true;
            if (false === $needsContext) {
                app('log')->debug(sprintf('SearchRuleEngine:: non strict, will search for: %s:true', $ruleTrigger->trigger_type));
                $searchArray[$ruleTrigger->trigger_type] = 'true';
            }
            if (true === $needsContext) {
                app('log')->debug(sprintf('SearchRuleEngine:: non strict, will search for: %s:"%s"', $ruleTrigger->trigger_type, $ruleTrigger->trigger_value));
                $searchArray[$ruleTrigger->trigger_type] = sprintf('"%s"', $ruleTrigger->trigger_value);
            }

            // then, add local operators as well:
            foreach ($this->operators as $operator) {
                app('log')->debug(sprintf('SearchRuleEngine:: add local added operator: %s:"%s"', $operator['type'], $operator['value']));
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

            $result       = $searchEngine->searchTransactions();
            $collection   = $result->getCollection();
            app('log')->debug(sprintf('Found in this run, %d transactions', $collection->count()));
            $total        = $total->merge($collection);
            app('log')->debug(sprintf('Total collection is now %d transactions', $total->count()));
            ++$count;
            // if trigger says stop processing, do so.
            if (true === $ruleTrigger->stop_processing && $result->count() > 0) {
                app('log')->debug('The trigger says to stop processing, so stop processing other triggers.');

                break;
            }
        }
        app('log')->debug(sprintf('Total collection is now %d transactions', $total->count()));
        app('log')->debug(sprintf('Done running %d trigger(s)', $count));

        // make collection unique
        $unique   = $total->unique(
            static function (array $group) {
                $str = '';
                foreach ($group['transactions'] as $transaction) {
                    $str = sprintf('%s%d', $str, $transaction['transaction_journal_id']);
                }

                return sprintf('%d%s', $group['id'], $str);
                // app('log')->debug(sprintf('Return key: %s ', $key));
            }
        );

        app('log')->debug(sprintf('SearchRuleEngine:: Found %d transactions using search engine.', $unique->count()));

        return $unique;
    }

    /**
     * @throws FireflyException
     */
    public function fire(): void
    {
        $this->resultCount = [];
        app('log')->debug('SearchRuleEngine::fire()!');

        // if rules and no rule groups, file each rule separately.
        if (0 !== $this->rules->count()) {
            app('log')->debug(sprintf('SearchRuleEngine:: found %d rule(s) to fire.', $this->rules->count()));

            /** @var Rule $rule */
            foreach ($this->rules as $rule) {
                $result = $this->fireRule($rule);
                if (true === $result && true === $rule->stop_processing) {
                    app('log')->debug(sprintf('Rule #%d has triggered and executed, but calls to stop processing. Since not in the context of a group, do not stop.', $rule->id));
                }
                if (false === $result && true === $rule->stop_processing) {
                    app('log')->debug(sprintf('Rule #%d has triggered and changed nothing, but calls to stop processing. Do not stop.', $rule->id));
                }
            }
            app('log')->debug('SearchRuleEngine:: done processing all rules!');

            return;
        }
        if (0 !== $this->groups->count()) {
            app('log')->debug(sprintf('SearchRuleEngine:: found %d rule group(s) to fire.', $this->groups->count()));

            // fire each group:
            /** @var RuleGroup $group */
            foreach ($this->groups as $group) {
                $this->fireGroup($group);
            }
        }
        app('log')->debug('SearchRuleEngine:: done processing all rules!');
    }

    /**
     * Returns true if the rule has been triggered.
     *
     * @throws FireflyException
     */
    private function fireRule(Rule $rule): bool
    {
        app('log')->debug(sprintf('Now going to fire rule #%d', $rule->id));
        if (false === $rule->active) {
            app('log')->debug(sprintf('Rule #%d is not active!', $rule->id));

            return false;
        }
        if (true === $rule->strict) {
            app('log')->debug(sprintf('Rule #%d is a strict rule.', $rule->id));

            return $this->fireStrictRule($rule);
        }
        app('log')->debug(sprintf('Rule #%d is not strict rule.', $rule->id));

        return $this->fireNonStrictRule($rule);
    }

    /**
     * Return true if the rule is fired (the collection is larger than zero).
     *
     * @throws FireflyException
     */
    private function fireStrictRule(Rule $rule): bool
    {
        app('log')->debug(sprintf('SearchRuleEngine::fireStrictRule(%d)!', $rule->id));
        $collection = $this->findStrictRule($rule);

        $this->processResults($rule, $collection);
        app('log')->debug(sprintf('SearchRuleEngine:: done processing strict rule #%d', $rule->id));

        $result     = $collection->count() > 0;
        if (true === $result) {
            app('log')->debug(sprintf('SearchRuleEngine:: rule #%d was triggered (on %d transaction(s)).', $rule->id, $collection->count()));

            return true;
        }
        app('log')->debug(sprintf('SearchRuleEngine:: rule #%d was not triggered (on %d transaction(s)).', $rule->id, $collection->count()));

        return false;
    }

    /**
     * @throws FireflyException
     */
    private function processResults(Rule $rule, Collection $collection): void
    {
        app('log')->debug(sprintf('SearchRuleEngine:: Going to process %d results.', $collection->count()));

        /** @var array $group */
        foreach ($collection as $group) {
            $this->processTransactionGroup($rule, $group);
        }
    }

    /**
     * @throws FireflyException
     */
    private function processTransactionGroup(Rule $rule, array $group): void
    {
        app('log')->debug(sprintf('SearchRuleEngine:: Will now execute actions on transaction group #%d', $group['id']));

        /** @var array $transaction */
        foreach ($group['transactions'] as $transaction) {
            $this->processTransactionJournal($rule, $transaction);
        }
    }

    /**
     * @throws FireflyException
     */
    private function processTransactionJournal(Rule $rule, array $transaction): void
    {
        app('log')->debug(sprintf('SearchRuleEngine:: Will now execute actions on transaction journal #%d', $transaction['transaction_journal_id']));
        $actions = $rule->ruleActions()->orderBy('order', 'ASC')->get();

        /** @var RuleAction $ruleAction */
        foreach ($actions as $ruleAction) {
            if (false === $ruleAction->active) {
                continue;
            }
            $break = $this->processRuleAction($ruleAction, $transaction);
            if (true === $break) {
                break;
            }
        }
    }

    /**
     * @throws FireflyException
     */
    private function processRuleAction(RuleAction $ruleAction, array $transaction): bool
    {
        app('log')->debug(sprintf('Executing rule action "%s" with value "%s"', $ruleAction->action_type, $ruleAction->action_value));
        $transaction = $this->addNotes($transaction);
        $actionClass = ActionFactory::getAction($ruleAction);
        $result      = $actionClass->actOnArray($transaction);
        $journalId   = $transaction['transaction_journal_id'] ?? 0;
        if (true === $result) {
            $this->resultCount[$journalId] = array_key_exists($journalId, $this->resultCount) ? $this->resultCount[$journalId]++ : 1;
            app('log')->debug(
                sprintf(
                    'Action "%s" on journal #%d was executed, so count a result. Updated transaction journal count is now %d.',
                    $ruleAction->action_type,
                    $transaction['transaction_journal_id'] ?? 0,
                    count($this->resultCount),
                )
            );
        }
        if (false === $result) {
            app('log')->debug(sprintf('Action "%s" reports NO changes were made.', $ruleAction->action_type));
        }

        // pick up from the action if it actually acted or not:
        if (true === $ruleAction->stop_processing && true === $result) {
            app('log')->debug(sprintf('Rule action "%s" reports changes AND asks to break, so break!', $ruleAction->action_type));

            return true;
        }
        if (true === $ruleAction->stop_processing && false === $result) {
            app('log')->debug(sprintf('Rule action "%s" reports NO changes AND asks to break, but we wont break!', $ruleAction->action_type));
        }

        return false;
    }

    private function addNotes(array $transaction): array
    {
        $transaction['notes'] = '';
        $dbNote               = Note::where('noteable_id', (int) $transaction['transaction_journal_id'])->where('noteable_type', TransactionJournal::class)->first(['notes.*']);
        if (null !== $dbNote) {
            $transaction['notes'] = $dbNote->text;
        }
        Log::debug(sprintf('Notes of journal #%d filled in.', $transaction['transaction_journal_id']));

        return $transaction;
    }

    /**
     * Return true if the rule is fired (the collection is larger than zero).
     *
     * @throws FireflyException
     */
    private function fireNonStrictRule(Rule $rule): bool
    {
        app('log')->debug(sprintf('SearchRuleEngine::fireNonStrictRule(%d)!', $rule->id));
        $collection = $this->findNonStrictRule($rule);

        $this->processResults($rule, $collection);
        app('log')->debug(sprintf('SearchRuleEngine:: done processing non-strict rule #%d', $rule->id));

        return $collection->count() > 0;
    }

    /**
     * @throws FireflyException
     */
    private function fireGroup(RuleGroup $group): void
    {
        app('log')->debug(sprintf('Going to fire group #%d with %d rule(s)', $group->id, $group->rules->count()));

        /** @var Rule $rule */
        foreach ($group->rules as $rule) {
            app('log')->debug(sprintf('Going to fire rule #%d from group #%d', $rule->id, $group->id));
            $result = $this->fireRule($rule);
            if (true === $result && true === $rule->stop_processing) {
                app('log')->debug(sprintf('The rule was triggered and rule->stop_processing = true, so group #%d will stop processing further rules.', $group->id));

                return;
            }
        }
    }

    /**
     * Return the number of changed transactions from the previous "fire" action.
     */
    public function getResults(): int
    {
        return count($this->resultCount);
    }

    public function setRefreshTriggers(bool $refreshTriggers): void
    {
        $this->refreshTriggers = $refreshTriggers;
    }

    public function setRuleGroups(Collection $ruleGroups): void
    {
        app('log')->debug(__METHOD__);
        foreach ($ruleGroups as $group) {
            if ($group instanceof RuleGroup) {
                app('log')->debug(sprintf('Adding a rule group to the SearchRuleEngine: #%d ("%s")', $group->id, $group->title));
                $this->groups->push($group);
            }
        }
    }

    public function setRules(Collection $rules): void
    {
        app('log')->debug(__METHOD__);
        foreach ($rules as $rule) {
            if ($rule instanceof Rule) {
                app('log')->debug(sprintf('Adding a rule to the SearchRuleEngine: #%d ("%s")', $rule->id, $rule->title));
                $this->rules->push($rule);
            }
        }
    }
}

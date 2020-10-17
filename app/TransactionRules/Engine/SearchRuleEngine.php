<?php
declare(strict_types=1);
/*
 * SearchRuleEngine.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\TransactionRules\Engine;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
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

    public function __construct()
    {
        $this->rules     = new Collection;
        $this->operators = [];
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
        die(__METHOD__);
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
        Log::debug('SearchRuleEngine::fire()!');
        foreach ($this->rules as $rule) {
            $this->fireRule($rule);
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
        $collection = $collection->unique();

        return $collection;
    }

    /**
     * @param Rule $rule
     * @throws FireflyException
     */
    private function fireRule(Rule $rule): void
    {
        if (true === $rule->strict) {
            $this->fireStrictRule($rule);
            return;
        }
        $this->fireNonStrictRule($rule);
    }

    /**
     * @param Rule       $rule
     * @param Collection $collection
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
     * @return bool
     * @throws FireflyException
     */
    private function processRuleAction(RuleAction $ruleAction, array $transaction): bool
    {
        Log::debug(sprintf('Executing rule action "%s" with value "%s"', $ruleAction->action_type, $ruleAction->action_value));
        $actionClass = ActionFactory::getAction($ruleAction);
        $actionClass->actOnArray($transaction);
        if ($ruleAction->stop_processing) {
            Log::debug(sprintf('Rule action "%s" asks to break, so break!', $ruleAction->action_value));
            return true;
        }
        return false;
    }

    /**
     * @param Rule $rule
     * @throws FireflyException
     */
    private function fireStrictRule(Rule $rule): void
    {
        Log::debug(sprintf('SearchRuleEngine::fireStrictRule(%d)!', $rule->id));
        $collection = $this->findStrictRule($rule);

        $this->processResults($rule, $collection);
        Log::debug(sprintf('SearchRuleEngine:: done processing strict rule #%d', $rule->id));
    }

    /**
     * @param Rule $rule
     * @throws FireflyException
     */
    private function fireNonStrictRule(Rule $rule): void
    {
        Log::debug(sprintf('SearchRuleEngine::fireNonStrictRule(%d)!', $rule->id));
        $collection = $this->findNonStrictRule($rule);

        $this->processResults($rule, $collection);
        Log::debug(sprintf('SearchRuleEngine:: done processing non-strict rule #%d', $rule->id));
    }

    /**
     * @param Rule $rule
     * @return Collection
     */
    private function findStrictRule(Rule $rule): Collection
    {
        $searchArray = [];
        /** @var RuleTrigger $ruleTrigger */
        foreach ($rule->ruleTriggers as $ruleTrigger) {
            // if needs no context, value is different:
            $needsContext = config(sprintf('firefly.search.operators.%s.needs_context', $ruleTrigger->trigger_type)) ?? true;
            if (false === $needsContext) {
                Log::debug(sprintf('SearchRuleEngine:: add a rule trigger: %s:true', $ruleTrigger->trigger_type));
                $searchArray[$ruleTrigger->trigger_type] = 'true';
            }
            if (true === $needsContext) {
                Log::debug(sprintf('SearchRuleEngine:: add a rule trigger: %s:"%s"', $ruleTrigger->trigger_type, $ruleTrigger->trigger_value));
                $searchArray[$ruleTrigger->trigger_type] = sprintf('"%s"', $ruleTrigger->trigger_value);
            }
        }

        // add local operators:
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
        return $result->getCollection();
    }

    /**
     * @param Rule $rule
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
        $unique = $total->unique(function (array $group) {
            $str = '';
            foreach ($group['transactions'] as $transaction) {
                $str = sprintf('%s%d', $str, $transaction['transaction_journal_id']);
            }
            $key = sprintf('%d%s', $group['id'], $str);
            Log::debug(sprintf('Return key: %s ', $key));
            return $key;
        });

        Log::debug(sprintf('SearchRuleEngine:: Found %d transactions using search engine.', $unique->count()));
        return $unique;
    }
}
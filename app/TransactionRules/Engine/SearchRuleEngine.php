<?php
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
        Log::debug('Add operator: ', $operator);
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
     * @param Rule $rule
     * @throws FireflyException
     */
    private function fireRule(Rule $rule): void
    {
        Log::debug(sprintf('SearchRuleEngine::fireRule(%d)!', $rule->id));
        $searchArray = [];
        /** @var RuleTrigger $ruleTrigger */
        foreach ($rule->ruleTriggers as $ruleTrigger) {
            Log::debug(sprintf('SearchRuleEngine:: add a rule trigger: %s:"%s"', $ruleTrigger->trigger_type, $ruleTrigger->trigger_value));
            $searchArray[$ruleTrigger->trigger_type] = sprintf('"%s"', $ruleTrigger->trigger_value);
        }

        // add local operators:
        foreach ($this->operators as $operator) {
            Log::debug(sprintf('SearchRuleEngine:: add local added operator: %s:"%s"', $operator['type'], $operator['value']));
            $searchArray[$operator['type']] = sprintf('"%s"', $operator['value']);
        }
        $toJoin = [];
        foreach ($searchArray as $type => $value) {
            $toJoin[] = sprintf('%s:%s', $type, $value);
        }

        $searchQuery = join(' ', $toJoin);
        Log::debug(sprintf('SearchRuleEngine:: Search query for rule #%d ("%s") = %s', $rule->id, $rule->title, $searchQuery));

        // build and run the search engine.
        $searchEngine = app(SearchInterface::class);
        $searchEngine->setUser($this->user);
        $searchEngine->setPage(1);
        $searchEngine->setLimit(31337);
        $searchEngine->parseQuery($searchQuery);

        $result     = $searchEngine->searchTransactions();
        $collection = $result->getCollection();
        Log::debug(sprintf('SearchRuleEngine:: Found %d transactions using search engine with query "%s".', $collection->count(), $searchQuery));

        $this->processResults($rule, $collection);
        Log::debug(sprintf('SearchRuleEngine:: done processing rule #%d', $rule->id));
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
}
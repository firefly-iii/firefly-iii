<?php
/**
 * Processor.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\TransactionRules;

use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\ActionInterface;
use FireflyIII\TransactionRules\Factory\ActionFactory;
use FireflyIII\TransactionRules\Factory\TriggerFactory;
use FireflyIII\TransactionRules\Triggers\AbstractTrigger;
use Illuminate\Support\Collection;
use Log;

/**
 * Class Processor
 *
 * @package FireflyIII\TransactionRules
 */
final class Processor
{
    /** @var  Collection */
    public $actions;
    /** @var  TransactionJournal */
    public $journal;
    /** @var  Rule */
    public $rule;
    /** @var Collection */
    public $triggers;
    /** @var int */
    protected $foundTriggers = 0;

    /**
     * Processor constructor.
     *
     */
    private function __construct()
    {
        $this->triggers = new Collection;
        $this->actions  = new Collection;
    }

    /**
     * This method will make a Processor that will process each transaction journal using the triggers
     * and actions found in the given Rule.
     *
     * @param Rule $rule
     *
     * @param bool $includeActions
     *
     * @return Processor
     */
    public static function make(Rule $rule, $includeActions = true)
    {
        Log::debug(sprintf('Making new rule from Rule %d', $rule->id));
        $self       = new self;
        $self->rule = $rule;
        $triggerSet = $rule->ruleTriggers()->orderBy('order', 'ASC')->get();
        /** @var RuleTrigger $trigger */
        foreach ($triggerSet as $trigger) {
            Log::debug(sprintf('Push trigger %d', $trigger->id));
            $self->triggers->push(TriggerFactory::getTrigger($trigger));
        }
        if ($includeActions) {
            $self->actions = $rule->ruleActions()->orderBy('order', 'ASC')->get();
        }

        return $self;
    }

    /**
     * This method will make a Processor that will process each transaction journal using the given
     * trigger (singular!). It can only report if the transaction journal was hit by the given trigger
     * and will not be able to act on it using actions.
     *
     * @param string $triggerName
     * @param string $triggerValue
     *
     * @return Processor
     */
    public static function makeFromString(string $triggerName, string $triggerValue)
    {
        Log::debug(sprintf('Processor::makeFromString("%s", "%s")', $triggerName, $triggerValue));
        $self    = new self;
        $trigger = TriggerFactory::makeTriggerFromStrings($triggerName, $triggerValue, false);
        $self->triggers->push($trigger);

        return $self;
    }

    /**
     * This method will make a Processor that will process each transaction journal using the given
     * triggers. It can only report if the transaction journal was hit by the given triggers
     * and will not be able to act on it using actions.
     *
     * The given triggers must be in the following format:
     *
     * [type => xx, value => yy, stopProcessing => bool], [type => xx, value => yy, stopProcessing => bool],
     *
     * @param array $triggers
     *
     * @return Processor
     */
    public static function makeFromStringArray(array $triggers)
    {
        $self = new self;
        foreach ($triggers as $entry) {
            $entry['value'] = is_null($entry['value']) ? '' : $entry['value'];
            $trigger        = TriggerFactory::makeTriggerFromStrings($entry['type'], $entry['value'], $entry['stopProcessing']);
            $self->triggers->push($trigger);
        }

        return $self;
    }

    /**
     * @return int
     */
    public function getFoundTriggers(): int
    {
        return $this->foundTriggers;
    }

    /**
     * @param int $foundTriggers
     */
    public function setFoundTriggers(int $foundTriggers)
    {
        $this->foundTriggers = $foundTriggers;
    }

    /**
     *
     * @return \FireflyIII\Models\Rule
     */
    public function getRule(): Rule
    {
        return $this->rule;
    }

    /**
     * This method will scan the given transaction journal and check if it matches the triggers found in the Processor
     * If so, it will also attempt to run the given actions on the journal. It returns a bool indicating if the transaction journal
     * matches all of the triggers (regardless of whether the Processor could act on it).
     *
     * @param Transaction $transaction
     *
     * @return bool
     */
    public function handleTransaction(Transaction $transaction): bool
    {
        Log::debug(sprintf('handleTransactionJournal for journal #%d (transaction #%d)', $transaction->transaction_journal_id, $transaction->id));

        // grab the actual journal.
        $journal       = $transaction->transactionJournal()->first();
        $this->journal = $journal;
        // get all triggers:
        $triggered = $this->triggered();
        if ($triggered) {
            Log::debug('Rule is triggered, go to actions.');
            if ($this->actions->count() > 0) {
                Log::debug('Has more than zero actions.');
                $this->actions();
            }
            if ($this->actions->count() === 0) {
                Log::info('Rule has no actions!');
            }

            return true;
        }

        return false;

    }

    /**
     * This method will scan the given transaction journal and check if it matches the triggers found in the Processor
     * If so, it will also attempt to run the given actions on the journal. It returns a bool indicating if the transaction journal
     * matches all of the triggers (regardless of whether the Processor could act on it).
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function handleTransactionJournal(TransactionJournal $journal): bool
    {
        Log::debug(sprintf('handleTransactionJournal for journal %d', $journal->id));
        $this->journal = $journal;
        // get all triggers:
        $triggered = $this->triggered();
        if ($triggered) {
            if ($this->actions->count() > 0) {
                $this->actions();
            }

            return true;
        }

        return false;

    }

    /**
     * @return bool
     */
    private function actions()
    {
        /**
         * @var int        $index
         * @var RuleAction $action
         */
        foreach ($this->actions as $action) {
            /** @var ActionInterface $actionClass */
            $actionClass = ActionFactory::getAction($action);
            Log::debug(sprintf('Fire action %s on journal #%d', get_class($actionClass), $this->journal->id));
            $actionClass->act($this->journal);
            if ($action->stop_processing) {
                Log::debug('Stop processing now and break.');
                break;
            }

        }

        return true;
    }

    /**
     * Method to check whether the current transaction would be triggered
     * by the given list of triggers
     *
     * @return bool
     */
    private function triggered(): bool
    {
        Log::debug('start of Processor::triggered()');
        $foundTriggers = $this->getFoundTriggers();
        $hitTriggers   = 0;
        Log::debug(sprintf('Found triggers starts at %d', $foundTriggers));
        /** @var AbstractTrigger $trigger */
        foreach ($this->triggers as $trigger) {
            $foundTriggers++;
            Log::debug(sprintf('Now checking trigger %s with value %s', get_class($trigger), $trigger->getTriggerValue()));
            /** @var AbstractTrigger $trigger */
            if ($trigger->triggered($this->journal)) {
                Log::debug('Is a match!');
                $hitTriggers++;
            }
            if ($trigger->stopProcessing) {
                Log::debug('Stop processing this trigger and break.');
                break;
            }

        }
        $result = ($hitTriggers === $foundTriggers && $foundTriggers > 0);
        Log::debug('Result of triggered()', ['hitTriggers' => $hitTriggers, 'foundTriggers' => $foundTriggers, 'result' => $result]);

        return $result;

    }


}

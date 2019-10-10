<?php
/**
 * Processor.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\TransactionRules;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\ActionInterface;
use FireflyIII\TransactionRules\Factory\ActionFactory;
use FireflyIII\TransactionRules\Factory\TriggerFactory;
use FireflyIII\TransactionRules\Triggers\AbstractTrigger;
use FireflyIII\TransactionRules\Triggers\UserAction;
use Illuminate\Support\Collection;
use Log;

/**
 * Class Processor.
 */
class Processor
{
    /** @var Collection Actions to exectute */
    public $actions;
    /** @var TransactionJournal Journal to run them on */
    public $journal;
    /** @var Rule Rule that applies */
    public $rule;
    /** @var Collection All triggers */
    public $triggers;
    /** @var int Found triggers */
    private $foundTriggers = 0;
    /** @var bool */
    private $strict = true;

    /**
     * Processor constructor.
     */
    public function __construct()
    {
        $this->triggers = new Collection;
        $this->actions  = new Collection;
    }

    /**
     * Return found triggers
     *
     * @return int
     */
    public function getFoundTriggers(): int
    {
        return $this->foundTriggers;
    }

    /**
     * Set found triggers
     *
     * @param int $foundTriggers
     */
    public function setFoundTriggers(int $foundTriggers): void
    {
        $this->foundTriggers = $foundTriggers;
    }

    /**
     * Returns the rule
     *
     * @return Rule
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
     * @param array $journal
     *
     * @return bool
     * @throws FireflyException
     */
    public function handleJournalArray(array $journal): bool
    {

        Log::debug(sprintf('handleJournalArray for journal #%d (group #%d)', $journal['transaction_journal_id'], $journal['transaction_group_id']));

        // grab the actual journal.
        $this->journal = TransactionJournal::find($journal['transaction_journal_id']);
        // get all triggers:
        $triggered = $this->triggered();
        if ($triggered) {
            Log::debug('Rule is triggered, go to actions.');
            if ($this->actions->count() > 0) {
                Log::debug('Has more than zero actions.');
                $this->actions();
            }
            if (0 === $this->actions->count()) {
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
     * @param Transaction $transaction
     *
     * @return bool
     * @throws FireflyException
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
            if (0 === $this->actions->count()) {
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
     * @throws FireflyException
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
    public function isStrict(): bool
    {
        return $this->strict;
    }

    /**
     * @param bool $strict
     */
    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    /**
     * This method will make a Processor that will process each transaction journal using the triggers
     * and actions found in the given Rule.
     *
     * @param Rule $rule
     * @param bool $includeActions
     *
     * @throws FireflyException
     */
    public function make(Rule $rule, bool $includeActions = null): void
    {
        $includeActions = $includeActions ?? true;
        Log::debug(sprintf('Making new rule from Rule %d', $rule->id));
        Log::debug(sprintf('Rule is strict: %s', var_export($rule->strict, true)));
        $this->rule   = $rule;
        $this->strict = $rule->strict;
        $triggerSet   = $rule->ruleTriggers()->orderBy('order', 'ASC')->get();
        /** @var RuleTrigger $trigger */
        foreach ($triggerSet as $trigger) {
            Log::debug(sprintf('Push trigger %d', $trigger->id));
            $this->triggers->push(TriggerFactory::getTrigger($trigger));
        }
        if (true === $includeActions) {
            $this->actions = $rule->ruleActions()->orderBy('order', 'ASC')->get();
        }
    }

    /**
     * This method will make a Processor that will process each transaction journal using the given
     * trigger (singular!). It can only report if the transaction journal was hit by the given trigger
     * and will not be able to act on it using actions.
     *
     * @param string $triggerName
     * @param string $triggerValue
     *
     * @throws FireflyException
     */
    public function makeFromString(string $triggerName, string $triggerValue): void
    {
        Log::debug(sprintf('Processor::makeFromString("%s", "%s")', $triggerName, $triggerValue));
        $trigger = TriggerFactory::makeTriggerFromStrings($triggerName, $triggerValue, false);
        $this->triggers->push($trigger);
    }

    /**
     * This method will make a Processor that will process each transaction journal using the given
     * triggers. It can only report if the transaction journal was hit by the given triggers
     * and will not be able to act on it using actions.
     *
     * The given triggers must be in the following format:
     *
     * [type => xx, value => yy, stop_processing => bool], [type => xx, value => yy, stop_processing => bool],
     *
     * @param array $triggers
     *
     * @throws FireflyException
     */
    public function makeFromStringArray(array $triggers): void
    {
        foreach ($triggers as $entry) {
            $entry['value'] = $entry['value'] ?? '';
            $trigger        = TriggerFactory::makeTriggerFromStrings($entry['type'], $entry['value'], $entry['stop_processing']);
            $this->triggers->push($trigger);
        }

    }

    /**
     * Run the actions
     *
     * @return void
     * @throws FireflyException
     */
    private function actions(): void
    {
        /**
         * @var int
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
    }

    /**
     * Method to check whether the current transaction would be triggered
     * by the given list of triggers.
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
            ++$foundTriggers;
            Log::debug(sprintf('Now checking trigger %s with value %s', get_class($trigger), $trigger->getTriggerValue()));
            /** @var AbstractTrigger $trigger */
            if ($trigger->triggered($this->journal)) {
                Log::debug('Is a match!');
                ++$hitTriggers;
                // is non-strict? then return true!
                if (!$this->strict && UserAction::class !== get_class($trigger)) {
                    Log::debug('Rule is set as non-strict, return true!');

                    return true;
                }
                if (!$this->strict && UserAction::class === get_class($trigger)) {
                    Log::debug('Rule is set as non-strict, but action was "user-action". Will not return true.');
                }
            }
            if ($trigger->stopProcessing) {
                Log::debug('Stop processing this rule and break off all triggers.');
                break;
            }
        }
        $result = ($hitTriggers === $foundTriggers && $foundTriggers > 0);
        Log::debug('Result of triggered()', ['hitTriggers' => $hitTriggers, 'foundTriggers' => $foundTriggers, 'result' => $result]);

        return $result;
    }
}

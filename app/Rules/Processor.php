<?php
declare(strict_types = 1);
/**
 * Processor.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules;

use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Rules\Actions\ActionInterface;
use FireflyIII\Rules\Factory\ActionFactory;
use FireflyIII\Rules\Factory\TriggerFactory;
use FireflyIII\Rules\Triggers\AbstractTrigger;
use Illuminate\Support\Collection;
use Log;

/**
 * Class Processor
 *
 * @package FireflyIII\Rules
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
     * @return Processor
     */
    public static function make(Rule $rule)
    {
        $self       = new self;
        $self->rule = $rule;

        $triggerSet = $rule->ruleTriggers()->orderBy('order', 'ASC')->get();
        /** @var RuleTrigger $trigger */
        foreach ($triggerSet as $trigger) {
            $self->triggers->push(TriggerFactory::getTrigger($trigger));
        }
        $self->actions = $rule->ruleActions()->orderBy('order', 'ASC')->get();

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
            $trigger = TriggerFactory::makeTriggerFromStrings($entry['type'], $entry['value'], $entry['stopProcessing']);
            $self->triggers->push($trigger);
        }

        return $self;
    }

    /**
     *
     * @return \FireflyIII\Models\Rule
     */
    public function getRule()
    {
        return $this->rule;
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
    public function handleTransactionJournal(TransactionJournal $journal)
    {
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
            $actionClass->act($this->journal);
            if ($action->stop_processing) {
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
    private function triggered()
    {
        $foundTriggers = 0;
        $hitTriggers   = 0;
        /** @var RuleTrigger $trigger */
        foreach ($this->triggers as $trigger) {
            $foundTriggers++;

            /** @var AbstractTrigger $trigger */
            if ($trigger->triggered($this->journal)) {
                $hitTriggers++;
            }
            if ($trigger->stopProcessing) {
                break;
            }

        }
        Log::debug('Total: ' . $foundTriggers . ' found triggers. ' . $hitTriggers . ' triggers were hit.');

        return ($hitTriggers == $foundTriggers && $foundTriggers > 0);

    }


}

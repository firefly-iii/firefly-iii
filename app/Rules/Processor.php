<?php
declare(strict_types = 1);
/**
 * Processor.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules;

use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Rules\Actions\ActionFactory;
use FireflyIII\Rules\Actions\ActionInterface;
use FireflyIII\Rules\Triggers\AbstractTrigger;
use FireflyIII\Rules\Triggers\TriggerFactory;
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
    private $actions;
    /** @var  TransactionJournal */
    private $journal;
    /** @var  Rule */
    private $rule;
    /** @var Collection */
    private $triggers;

    /**
     * Processor constructor.
     *
     */
    private function __construct()
    {
        $this->triggers = new Collection;
        $this->actions = new Collection;
    }

    /**
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

        return ($hitTriggers == $foundTriggers);

    }


}

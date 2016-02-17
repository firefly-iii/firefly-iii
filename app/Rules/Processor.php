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
use FireflyIII\Rules\Triggers\TriggerFactory;
use FireflyIII\Rules\Triggers\TriggerInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class Processor
 *
 * @package FireflyIII\Rules
 */
class Processor
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

    }

    /**
     * @param Rule $rule
     *
     * @return Processor
     */
    public static function make(Rule $rule)
    {
        $self           = new self;
        $self->rule     = $rule;
        $self->triggers = $rule->ruleTriggers()->orderBy('order', 'ASC')->get();
        $self->actions  = $rule->ruleActions()->orderBy('order', 'ASC')->get();

        return $self;
    }

    /**
     * @param TransactionJournal $journal
     */
    public function handleTransactionJournal(TransactionJournal $journal)
    {
        $this->journal = $journal;
        // get all triggers:
        $triggered = $this->triggered();
        if ($triggered) {
            Log::debug('Rule #' . $this->rule->id . ' was triggered. Now process each action.');
            $this->actions();
        }

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
            $actionClass = ActionFactory::getAction($action, $this->journal);
            $actionClass->act();
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

            /** @var TriggerInterface $triggerObject */
            $triggerObject = TriggerFactory::getTrigger($trigger);
            // no need to keep pushing the journal around!
            if ($triggerObject->triggered($this->journal)) {
                $hitTriggers++;
            }
            if ($trigger->stop_processing) {
                break;
            }

        }
        Log::debug('Total: ' . $foundTriggers . ' found triggers. ' . $hitTriggers . ' triggers were hit.');

        return ($hitTriggers == $foundTriggers);

    }


}

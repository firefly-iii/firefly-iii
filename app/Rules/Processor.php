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
use FireflyIII\Rules\Actions\ActionInterface;
use FireflyIII\Rules\Triggers\TriggerInterface;
use FireflyIII\Rules\Triggers\TriggerFactory;
use FireflyIII\Support\Domain;
use Log;

/**
 * Class Processor
 *
 * @package FireflyIII\Rules
 */
class Processor
{
    /** @var  TransactionJournal */
    protected $journal;
    /** @var  Rule */
    protected $rule;
    /** @var array */
    private $actionTypes = [];
    /** @var array */
    private $triggerTypes = [];

    /**
     * Processor constructor.
     *
     * @param Rule               $rule
     * @param TransactionJournal $journal
     */
    public function __construct(Rule $rule, TransactionJournal $journal)
    {
        $this->rule         = $rule;
        $this->journal      = $journal;
        $this->triggerTypes = Domain::getRuleTriggers();
        $this->actionTypes  = Domain::getRuleActions();
    }

    /**
     * @return TransactionJournal
     */
    public function getJournal()
    {
        return $this->journal;
    }

    /**
     * @param TransactionJournal $journal
     */
    public function setJournal($journal)
    {
        $this->journal = $journal;
    }

    /**
     * @return Rule
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param Rule $rule
     */
    public function setRule($rule)
    {
        $this->rule = $rule;
    }

    public function handle()
    {
        // get all triggers:
        $triggered = $this->triggered();
        if ($triggered) {
            Log::debug('Rule #' . $this->rule->id . ' was triggered. Now process each action.');
            $this->actions();
        }

    }

    /**
     * Checks whether the current transaction is triggered by the current rule
     * @return boolean
     */
    public function isTriggered() {
        return $this->triggered();
    }
    
    /**
     * Checks whether the current transaction is triggered by the list of given triggers
     * @return boolean
     */
    public function isTriggeredBy(array $triggers) {
        return $this->triggeredBy($triggers);
    }
    
    /**
     * @return bool
     */
    protected function actions()
    {
        /**
         * @var int        $index
         * @var RuleAction $action
         */
        foreach ($this->rule->ruleActions()->orderBy('order', 'ASC')->get() as $action) {
            $type  = $action->action_type;
            $class = $this->actionTypes[$type];
            Log::debug('Action #' . $action->id . ' for rule #' . $action->rule_id . ' (' . $type . ')');
            if (!class_exists($class)) {
                abort(500, 'Could not instantiate class for rule action type "' . $type . '" (' . $class . ').');
            }
            /** @var ActionInterface $actionClass */
            $actionClass = new $class($action, $this->journal);
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
     * @return bool
     */    
    protected function triggeredBy($triggers) {
        $foundTriggers = 0;
        $hitTriggers   = 0;
        /** @var RuleTrigger $trigger */
        foreach ($triggers as $trigger) {
            $foundTriggers++;
            
            /** @var TriggerInterface $triggerClass */
            $triggerClass = TriggerFactory::getTrigger($trigger, $this->journal);
            if ($triggerClass->triggered()) {
                $hitTriggers++;
            }
            if ($trigger->stop_processing) {
                break;
            }
        
        }
        Log::debug('Total: ' . $foundTriggers . ' found triggers. ' . $hitTriggers . ' triggers were hit.');
        
        return ($hitTriggers == $foundTriggers);
        
    }
    /**
     * Checks whether the current transaction is triggered by the current rule
     * @return bool
     */
    protected function triggered()
    {
        return $this->triggeredBy($this->rule->ruleTriggers()->orderBy('order', 'ASC')->get());
    }


}

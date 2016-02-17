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
use FireflyIII\Rules\Actions\ActionFactory;
use FireflyIII\Rules\Triggers\TriggerInterface;
use FireflyIII\Rules\Triggers\TriggerFactory;
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
     * @return bool
     */
    protected function actions()
    {
        /**
         * @var int        $index
         * @var RuleAction $action
         */
        foreach ($this->rule->ruleActions()->orderBy('order', 'ASC')->get() as $action) {
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
     * @return bool
     */
    protected function triggered()
    {
        $foundTriggers = 0;
        $hitTriggers   = 0;
        /** @var RuleTrigger $trigger */
        foreach ($this->rule->ruleTriggers()->orderBy('order', 'ASC')->get() as $trigger) {
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


}

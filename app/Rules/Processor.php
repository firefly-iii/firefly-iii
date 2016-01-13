<?php
/**
 * Processor.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules;

use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Rules\Triggers\TriggerInterface;
use FireflyIII\Support\Domain;
use Log;

/**
 * Class Processor
 *
 * @package FireflyIII\Rules
 */
class Processor
{
    /** @var  Rule */
    protected $rule;

    /** @var  TransactionJournal */
    protected $journal;

    private $triggerTypes = [];

    /**
     * Processor constructor.
     */
    public function __construct(Rule $rule, TransactionJournal $journal)
    {
        $this->rule         = $rule;
        $this->journal      = $journal;
        $this->triggerTypes = Domain::getRuleTriggers();
    }

    public function handle()
    {
        // get all triggers:
        $triggered = $this->triggered();
        if ($triggered) {
            Log::debug('Rule #' . $this->rule->id . ' was triggered. Now process each action.');
        }

    }

    /**
     * TODO stop when stop_processing is present.
     *
     * @return bool
     */
    protected function triggered()
    {
        $foundTriggers = 0;
        $hitTriggers   = 0;
        /** @var RuleTrigger $trigger */
        foreach ($this->rule->ruleTriggers()->orderBy('order', 'ASC')->get() as $index => $trigger) {
            $foundTriggers++;
            $type  = $trigger->trigger_type;
            $class = $this->triggerTypes[$type];
            Log::debug('Trigger #' . $trigger->id . ' for rule #' . $trigger->rule_id . ' (' . $type . ')');
            if (!class_exists($class)) {
                abort(500, 'Could not instantiate class for rule trigger type "' . $type . '" (' . $class . ').');
            }
            /** @var TriggerInterface $triggerClass */
            $triggerClass = new $class($trigger, $this->journal);
            if ($triggerClass->triggered()) {
                $hitTriggers++;
            }

        }
        Log::debug('Total: ' . $foundTriggers . ' found triggers. ' . $hitTriggers . ' triggers were hit.');

        return ($hitTriggers == $foundTriggers);

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


}
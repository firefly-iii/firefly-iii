<?php
/**
 * Processor.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules;

use Exception;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Domain;

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

        }

    }

    /**
     * @return bool
     */
    protected function triggered()
    {
        /** @var RuleTrigger $trigger */
        foreach ($this->rule->ruleTriggers as $trigger) {
            $type  = $trigger->trigger_type;
            $class = $this->triggerTypes[$type];
            if (!class_exists($class)) {
                abort(500, 'Could not instantiate class for rule trigger type "' . $type . '" ('.$class.').');
            }
        }


        return true;

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
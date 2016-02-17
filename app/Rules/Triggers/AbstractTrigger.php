<?php
/**
 * AbstractTrigger.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules\Triggers;

use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionJournal;

/**
 * This class will be magical!
 *
 * Class AbstractTrigger
 *
 * @package FireflyIII\Rules\Triggers
 */
class AbstractTrigger
{
    /** @var  string */
    protected $checkValue;
    /** @var  TransactionJournal */
    protected $journal;
    /** @var RuleTrigger */
    protected $trigger;
    /** @var  string */
    protected $triggerValue;

    /**
     * AbstractTrigger constructor.
     */
    private function __construct()
    {

    }

    /**
     * @param RuleTrigger $trigger
     *
     * @return AbstractTrigger
     */
    public static function makeFromTrigger(RuleTrigger $trigger)
    {
        $self               = new self;
        $self->trigger      = $trigger;
        $self->triggerValue = $trigger->trigger_value;

        return $self;
    }

    /**
     * @param RuleTrigger        $trigger
     * @param TransactionJournal $journal
     */
    public static function makeFromTriggerAndJournal(RuleTrigger $trigger, TransactionJournal $journal)
    {
        $self               = new self;
        $self->trigger      = $trigger;
        $self->triggerValue = $trigger->trigger_value;
        $self->journal      = $journal;
    }

    /**
     * @param string $triggerValue
     *
     * @return AbstractTrigger
     */
    public static function makeFromTriggerValue(string $triggerValue)
    {
        $self               = new static;
        $self->triggerValue = $triggerValue;

        return $self;
    }


}
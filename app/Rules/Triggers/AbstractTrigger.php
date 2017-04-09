<?php
/**
 * AbstractTrigger.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

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
    /** @var  bool */
    public $stopProcessing;
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
     * @param string $triggerValue
     * @param bool   $stopProcessing
     *
     * @return static
     */
    public static function makeFromStrings(string $triggerValue, bool $stopProcessing)
    {
        $self                 = new static;
        $self->triggerValue   = $triggerValue;
        $self->stopProcessing = $stopProcessing;

        return $self;
    }

    /**
     * @param RuleTrigger $trigger
     *
     * @return AbstractTrigger
     */
    public static function makeFromTrigger(RuleTrigger $trigger)
    {
        $self                 = new static;
        $self->trigger        = $trigger;
        $self->triggerValue   = $trigger->trigger_value;
        $self->stopProcessing = $trigger->stop_processing;

        return $self;
    }


    /**
     * @param RuleTrigger        $trigger
     * @param TransactionJournal $journal
     */
    public static function makeFromTriggerAndJournal(RuleTrigger $trigger, TransactionJournal $journal)
    {
        $self                 = new static;
        $self->trigger        = $trigger;
        $self->triggerValue   = $trigger->trigger_value;
        $self->stopProcessing = $trigger->stop_processing;
        $self->journal        = $journal;
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

    /**
     * @return RuleTrigger
     */
    public function getTrigger(): RuleTrigger
    {
        return $this->trigger;
    }

    /**
     * @return string
     */
    public function getTriggerValue(): string
    {
        return $this->triggerValue;
    }


}

<?php
/**
 * AbstractTrigger.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\TransactionRules\Triggers;

use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionJournal;

/**
 * This class will be magical!
 *
 * Class AbstractTrigger
 *
 * @package FireflyIII\TransactionRules\Triggers
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
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * @codeCoverageIgnore
     *
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
     * @codeCoverageIgnore
     *
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
     * @codeCoverageIgnore
     *
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
     * @codeCoverageIgnore
     * @return RuleTrigger
     */
    public function getTrigger(): RuleTrigger
    {
        return $this->trigger;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getTriggerValue(): string
    {
        return $this->triggerValue;
    }
}

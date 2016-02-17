<?php
declare(strict_types = 1);
/**
 * DescriptionIs.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules\Triggers;

use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class DescriptionIs
 *
 * @package FireflyIII\Rules\Triggers
 */
class DescriptionIs implements TriggerInterface
{
    /** @var TransactionJournal */
    protected $journal;
    /** @var RuleTrigger */
    protected $trigger;

    /**
     * TriggerInterface constructor.
     *
     * @param RuleTrigger        $trigger
     * @param TransactionJournal $journal
     */
    public function __construct(RuleTrigger $trigger, TransactionJournal $journal)
    {
        $this->trigger = $trigger;
        $this->journal = $journal;
    }

    /**
     * @{inheritdoc}
     *
     * @see TriggerInterface::matchesAnything
     *
     * @return bool
     */
    public function matchesAnything()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function triggered()
    {
        $description = strtolower($this->journal->description);
        $search      = strtolower($this->trigger->trigger_value);

        if ($description == $search) {
            Log::debug('"' . $description . '" equals "' . $search . '" exactly. Return true.');

            return true;
        }
        Log::debug('"' . $description . '" does not equal "' . $search . '". Return false.');

        return false;

    }

}

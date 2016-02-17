<?php
declare(strict_types = 1);
/**
 * DescriptionContains.php
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
 * Class DescriptionContains
 *
 * @package FireflyIII\Rules\Triggers
 */
class DescriptionContains implements TriggerInterface
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
        return $this->trigger->trigger_value === "";
    }

    /**
     * @return bool
     */
    public function triggered()
    {
        $search = strtolower($this->trigger->trigger_value);
        $source = strtolower($this->journal->description);

        $strpos = strpos($source, $search);
        if (!($strpos === false)) {
            // found something
            Log::debug('"' . $source . '" contains the text "' . $search . '". Return true.');

            return true;
        }

        // found nothing.
        Log::debug('"' . $source . '" does not contain the text "' . $search . '". Return false.');

        return false;

    }
}

<?php
declare(strict_types = 1);
/**
 * FromAccountContains.php
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
 * Class FromAccountContains
 *
 * @package FireflyIII\Rules\Triggers
 */
class FromAccountContains implements TriggerInterface
{
    /** @var RuleTrigger */
    protected $trigger;

    /** @var TransactionJournal */
    protected $journal;


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
     * @return bool
     */
    public function triggered()
    {
        $fromAccountName = strtolower($this->journal->source_account->name);
        $search          = strtolower($this->trigger->trigger_value);
        $strpos          = strpos($fromAccountName, $search);

        if (!($strpos === false)) {
            // found something
            Log::debug('"' . $fromAccountName . '" contains the text "' . $search . '". Return true.');

            return true;
        }

        // found nothing.
        Log::debug('"' . $fromAccountName . '" does not contain the text "' . $search . '". Return false.');

        return false;

    }
    
    /**
     * Checks whether this trigger will match all transactions
     * This happens when the trigger_value is empty
     * @return bool
     */
    public function matchesAnything() {
        return $this->trigger->trigger_value === "";
    }
    
}

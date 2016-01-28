<?php
/**
 * ToAccountEnds.php
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
 * Class ToAccountEnds
 *
 * @package FireflyIII\Rules\Triggers
 */
class ToAccountEnds implements TriggerInterface
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
        $toAccountName       = strtolower($this->journal->destination_account->name);
        $toAccountNameLength = strlen($toAccountName);
        $search              = strtolower($this->trigger->trigger_value);
        $searchLength        = strlen($search);

        // if the string to search for is longer than the account name,
        // shorten the search string.
        if ($searchLength > $toAccountNameLength) {
            Log::debug('Search string "' . $search . '" (' . $searchLength . ') is longer than "' . $toAccountName . '" (' . $toAccountNameLength . '). ');
            $search       = substr($search, ($toAccountNameLength * -1));
            $searchLength = strlen($search);
            Log::debug('Search string is now "' . $search . '" (' . $searchLength . ') instead.');
        }


        $part = substr($toAccountName, $searchLength * -1);

        if ($part == $search) {
            Log::debug('"' . $toAccountName . '" ends with "' . $search . '". Return true.');

            return true;
        }
        Log::debug('"' . $toAccountName . '" does not end with "' . $search . '". Return false.');

        return false;

    }
}

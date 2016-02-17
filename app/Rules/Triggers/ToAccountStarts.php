<?php
declare(strict_types = 1);
/**
 * ToAccountStarts.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules\Triggers;

use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class ToAccountStarts
 *
 * @package FireflyIII\Rules\Triggers
 */
class ToAccountStarts extends AbstractTrigger implements TriggerInterface
{

    /**
     * A trigger is said to "match anything", or match any given transaction,
     * when the trigger value is very vague or has no restrictions. Easy examples
     * are the "AmountMore"-trigger combined with an amount of 0: any given transaction
     * has an amount of more than zero! Other examples are all the "Description"-triggers
     * which have hard time handling empty trigger values such as "" or "*" (wild cards).
     *
     * If the user tries to create such a trigger, this method MUST return true so Firefly III
     * can stop the storing / updating the trigger. If the trigger is in any way restrictive
     * (even if it will still include 99.9% of the users transactions), this method MUST return
     * false.
     *
     * @param null $value
     *
     * @return bool
     */
    public static function willMatchEverything($value = null)
    {
        if (!is_null($value)) {
            return strval($value) === "";
        }

        return true;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function triggered(TransactionJournal $journal)
    {
        $toAccountName = strtolower($journal->destination_account->name);
        $search        = strtolower($this->trigger->trigger_value);

        $part = substr($toAccountName, 0, strlen($search));

        if ($part == $search) {
            Log::debug('"' . $toAccountName . '" starts with "' . $search . '". Return true.');

            return true;
        }
        Log::debug('"' . $toAccountName . '" does not start with "' . $search . '". Return false.');

        return false;

    }
}

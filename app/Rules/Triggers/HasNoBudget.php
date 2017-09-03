<?php
/**
 * HasNoBudget.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Rules\Triggers;

use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class HasNoBudget
 *
 * @package FireflyIII\Rules\Triggers
 */
final class HasNoBudget extends AbstractTrigger implements TriggerInterface
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
        return false;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function triggered(TransactionJournal $journal): bool
    {
        $count = $journal->budgets()->count();
        if ($count === 0) {
            Log::debug(sprintf('RuleTrigger HasNoBudget for journal #%d: count is %d, return true.', $journal->id, $count));

            return true;
        }
        Log::debug(sprintf('RuleTrigger HasNoBudget for journal #%d: count is %d, return false.', $journal->id, $count));

        return false;

    }
}

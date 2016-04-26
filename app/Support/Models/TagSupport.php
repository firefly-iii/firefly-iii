<?php
declare(strict_types = 1);

namespace FireflyIII\Support\Models;

use FireflyIII\Models\Tag;
use Illuminate\Database\Eloquent\Model;

class TagSupport extends Model
{
    /**
     * Can a tag become an advance payment?
     *
     * @param Tag $tag
     *
     * @return bool
     */
    public static function tagAllowAdvance(Tag $tag): bool
    {
        /*
                * If this tag is a balancing act, and it contains transfers, it cannot be
                * changes to an advancePayment.
                */

        if ($tag->tagMode == 'balancingAct' || $tag->tagMode == 'nothing') {
            foreach ($tag->transactionjournals as $journal) {
                if ($journal->isTransfer()) {
                    return false;
                }
            }
        }

        /*
         * If this tag contains more than one expenses, it cannot become an advance payment.
         */
        $count = 0;
        foreach ($tag->transactionjournals as $journal) {
            if ($journal->isWithdrawal()) {
                $count++;
            }
        }
        if ($count > 1) {
            return false;
        }

        return true;
    }

    /**
     * Can a tag become a balancing act?
     *
     * @param Tag $tag
     *
     * @return bool
     */
    public static function tagAllowBalancing(Tag $tag): bool
    {
        /*
         * If has more than two transactions already, cannot become a balancing act:
         */
        if ($tag->transactionjournals->count() > 2) {
            return false;
        }

        /*
         * If any transaction is a deposit, cannot become a balancing act.
         */
        foreach ($tag->transactionjournals as $journal) {
            if ($journal->isDeposit()) {
                return false;
            }
        }

        return true;

    }

}
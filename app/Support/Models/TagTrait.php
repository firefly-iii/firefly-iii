<?php
/**
 * TagTrait.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Models;

use FireflyIII\Models\Tag;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TagSupport
 *
 * @package FireflyIII\Support\Models
 */
trait TagTrait
{
    /**
     * Can a tag become an advance payment?
     *
     * @return bool
     */
    public function tagAllowAdvance(): bool
    {
        /*
         * If this tag is a balancing act, and it contains transfers, it cannot be
         * changes to an advancePayment.
         */

        if ($this->tagMode == 'balancingAct' || $this->tagMode == 'nothing') {
            foreach ($this->transactionjournals as $journal) {
                if ($journal->isTransfer()) {
                    return false;
                }
            }
        }

        /*
         * If this tag contains more than one expenses, it cannot become an advance payment.
         */
        $count = 0;
        foreach ($this->transactionjournals as $journal) {
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
     * @return bool
     */
    public function tagAllowBalancing(): bool
    {
        /*
         * If has more than two transactions already, cannot become a balancing act:
         */
        if ($this->transactionjournals->count() > 2) {
            return false;
        }

        /*
         * If any transaction is a deposit, cannot become a balancing act.
         */
        foreach ($this->transactionjournals as $journal) {
            if ($journal->isDeposit()) {
                return false;
            }
        }

        return true;

    }

}

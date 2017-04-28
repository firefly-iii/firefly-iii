<?php
/**
 * TransferFilter.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Filter;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;
use Steam;

/**
 * Class TransferFilter
 *
 * This filter removes any transfers that are in the collection twice (from A to B and from B to A).
 *
 * @package FireflyIII\Helpers\Filter
 */
class TransferFilter implements FilterInterface
{
    /**
     * @param Collection $set
     *
     * @return Collection
     */
    public function filter(Collection $set): Collection
    {
        $count = [];
        $new   = new Collection;
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            if ($transaction->transaction_type_type !== TransactionType::TRANSFER) {
                $new->push($transaction);
                continue;
            }
            // make property string:
            $journalId  = $transaction->transaction_journal_id;
            $amount     = Steam::positive($transaction->transaction_amount);
            $accountIds = [intval($transaction->account_id), intval($transaction->opposing_account_id)];
            sort($accountIds);
            $key = $journalId . '-' . join(',', $accountIds) . '-' . $amount;
            if (!isset($count[$key])) {
                // not yet counted? add to new set and count it:
                $new->push($transaction);
                $count[$key] = 1;
            }
        }

        return $new;
    }
}
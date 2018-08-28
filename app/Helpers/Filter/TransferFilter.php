<?php
/**
 * TransferFilter.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Helpers\Filter;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;

/**
 * Class TransferFilter.
 *
 * This filter removes any transfers that are in the collection twice (from A to B and from B to A).
 *
 * @codeCoverageIgnore
 */
class TransferFilter implements FilterInterface
{
    /**
     * See class transaction.
     *
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
            if (TransactionType::TRANSFER !== $transaction->transaction_type_type) {
                $new->push($transaction);
                continue;
            }
            // make property string:
            $journalId      = $transaction->transaction_journal_id;
            $amount         = app('steam')->positive($transaction->transaction_amount);
            $accountIds     = [(int)$transaction->account_id, (int)$transaction->opposing_account_id];
            $transactionIds = [$transaction->id, (int)$transaction->opposing_id];
            sort($accountIds);
            sort($transactionIds);
            $key = $journalId . '-' . implode(',', $transactionIds) . '-' . implode(',', $accountIds) . '-' . $amount;
            if (!isset($count[$key])) {
                // not yet counted? add to new set and count it:
                $new->push($transaction);
                $count[$key] = 1;
            }
        }

        return $new;
    }
}

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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
            $journalId      = $transaction->transaction_journal_id;
            $amount         = Steam::positive($transaction->transaction_amount);
            $accountIds     = [intval($transaction->account_id), intval($transaction->opposing_account_id)];
            $transactionIds = [$transaction->id, intval($transaction->opposing_id)];
            sort($accountIds);
            sort($transactionIds);
            $key = $journalId . '-' . join(',', $transactionIds) . '-' . join(',', $accountIds) . '-' . $amount;
            if (!isset($count[$key])) {
                // not yet counted? add to new set and count it:
                $new->push($transaction);
                $count[$key] = 1;
            }
        }

        return $new;
    }
}

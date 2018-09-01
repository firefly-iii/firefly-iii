<?php
/**
 * SplitIndicatorFilter.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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


use DB;
use FireflyIII\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Class SplitIndicatorFilter
 *
 * @codeCoverageIgnore
 */
class SplitIndicatorFilter implements FilterInterface
{

    /**
     * Adds a property if the journal is a split one.
     *
     * @param Collection $set
     *
     * @return Collection
     */
    public function filter(Collection $set): Collection
    {
        // grab journal ID's:
        $ids = $set->pluck('journal_id')->toArray();

        $result  = DB::table('transactions')
                     ->whereNull('deleted_at')->whereIn('transaction_journal_id', $ids)
                     ->groupBy('transaction_journal_id')->get(['transaction_journal_id', DB::raw('COUNT(*) as number')]);
        $counter = [];
        foreach ($result as $row) {
            $counter[$row->transaction_journal_id] = $row->number;
        }
        $set->each(
            function (Transaction $transaction) use ($counter) {
                $id                    = (int)$transaction->journal_id;
                $count                 = (int)($counter[$id] ?? 0.0);
                $transaction->is_split = false;
                if ($count > 2) {
                    $transaction->is_split = true;
                }
            }
        );

        return $set;
    }
}

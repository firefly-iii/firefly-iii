<?php
/**
 * CountAttachmentsFilter.php
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
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;

/**
 * Class CountAttachmentsFilter
 * @codeCoverageIgnore
 */
class CountAttachmentsFilter implements FilterInterface
{

    /**
     * Adds the number of transactions to each given transaction.
     *
     * @param Collection $set
     *
     * @return Collection
     */
    public function filter(Collection $set): Collection
    {
        // grab journal ID's:
        $ids = $set->pluck('journal_id')->toArray();

        $result  = DB::table('attachments')
                     ->whereNull('deleted_at')
                     ->whereIn('attachable_id', $ids)
                     ->where('attachable_type', TransactionJournal::class)
                     ->groupBy('attachable_id')->get(['attachable_id', DB::raw('COUNT(*) as number')]);
        $counter = [];
        foreach ($result as $row) {
            $counter[$row->attachable_id] = $row->number;
        }
        $set->each(
            function (Transaction $transaction) use ($counter) {
                $id                           = (int)$transaction->journal_id;
                $count                        = (int)($counter[$id] ?? 0.0);
                $transaction->attachmentCount = $count;
            }
        );

        return $set;
    }
}

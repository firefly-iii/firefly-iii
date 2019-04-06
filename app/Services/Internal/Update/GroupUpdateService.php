<?php
/**
 * GroupUpdateService.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Services\Internal\Update;

use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class GroupUpdateService
 */
class GroupUpdateService
{
    /**
     * Update a transaction group.
     *
     * @param TransactionGroup $transactionGroup
     * @param array            $data
     *
     * @return TransactionGroup
     */
    public function update(TransactionGroup $transactionGroup, array $data): TransactionGroup
    {
        Log::debug('Now in group update service');
        $transactions = $data['transactions'] ?? [];
        // update group name.
        if (array_key_exists('group_title', $data)) {
            Log::debug(sprintf('Update transaction group #%d title.', $transactionGroup->id));
            $transactionGroup->title = $data['group_title'];
            $transactionGroup->save();
        }
        if (1 === count($transactions) && 1 === $transactionGroup->transactionJournals()->count()) {
            /** @var TransactionJournal $first */
            $first = $transactionGroup->transactionJournals()->first();
            Log::debug(sprintf('Will now update journal #%d (only journal in group #%d)', $first->id, $transactionGroup->id));
            $this->updateTransactionJournal($transactionGroup, $first, reset($transactions));
            $transactionGroup->refresh();

            return $transactionGroup;
        }
        die('cannot update split');

        app('preferences')->mark();
    }

    /**
     * Update single journal.
     *
     * @param TransactionGroup   $transactionGroup
     * @param TransactionJournal $journal
     * @param array              $data
     */
    private function updateTransactionJournal(TransactionGroup $transactionGroup, TransactionJournal $journal, array $data): void
    {
        /** @var JournalUpdateService $updateService */
        $updateService = app(JournalUpdateService::class);
        $updateService->setTransactionGroup($transactionGroup);
        $updateService->setTransactionJournal($journal);
        $updateService->setData($data);
        $updateService->update();
    }

}
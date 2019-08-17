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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use Log;

/**
 * Class GroupUpdateService
 * TODO test.
 */
class GroupUpdateService
{
    /**
     * Update a transaction group.
     *
     * @param TransactionGroup $transactionGroup
     * @param array $data
     *
     * @return TransactionGroup
     * @throws FireflyException
     */
    public function update(TransactionGroup $transactionGroup, array $data): TransactionGroup
    {
        Log::debug('Now in group update service', $data);
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
            app('preferences')->mark();

            return $transactionGroup;
        }

        Log::debug('Going to update split group.');

        $existing = $transactionGroup->transactionJournals->pluck('id')->toArray();
        $updated  = [];

        /**
         * @var int $index
         * @var array $transaction
         */
        foreach ($transactions as $index => $transaction) {
            Log::debug(sprintf('Now at #%d of %d', ($index + 1), count($transactions)), $transaction);
            $journalId = (int)($transaction['transaction_journal_id'] ?? 0);
            /** @var TransactionJournal $journal */
            $journal = $transactionGroup->transactionJournals()->find($journalId);
            if (null === $journal) {
                Log::debug('This entry has no existing journal: make a new split.');
                // force the transaction type on the transaction data.
                // by plucking it from another journal in the group:
                if (!isset($transaction['type'])) {
                    Log::debug('No transaction type is indicated.');
                    /** @var TransactionJournal $randomJournal */
                    $randomJournal = $transactionGroup->transactionJournals()->inRandomOrder()->with(['transactionType'])->first();
                    if (null !== $randomJournal) {
                        $transaction['type'] = $randomJournal->transactionType->type;
                        Log::debug(sprintf('Transaction type set to %s.', $transaction['type']));
                    }
                }
                Log::debug('Call createTransactionJournal');
                $this->createTransactionJournal($transactionGroup, $transaction);
                Log::debug('Done calling createTransactionJournal');
            }
            if (null !== $journal) {
                Log::debug('Call updateTransactionJournal');
                $this->updateTransactionJournal($transactionGroup, $journal, $transaction);
                $updated[] = $journal->id;
                Log::debug('Done calling updateTransactionJournal');
            }
        }
        $result = array_diff($existing, $updated);
        if (count($result) > 0) {
            /** @var string $deletedId */
            foreach ($result as $deletedId) {
                $journal = $transactionGroup->transactionJournals()->find((int)$deletedId);
                /** @var JournalDestroyService $service */
                $service = app(JournalDestroyService::class);
                $service->destroy($journal);
            }
        }

        app('preferences')->mark();

        return $transactionGroup;
    }

    /**
     * @param TransactionGroup $transactionGroup
     * @param array $data
     * @throws FireflyException
     */
    private function createTransactionJournal(TransactionGroup $transactionGroup, array $data): void
    {

        $submission = [
            'transactions' => [
                $data,
            ],
        ];
        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($transactionGroup->user);
        try {
            $collection = $factory->create($submission);
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new FireflyException(sprintf('Could not create new transaction journal: %s', $e->getMessage()));
        }
        $collection->each(function (TransactionJournal $journal) use ($transactionGroup) {
            $transactionGroup->transactionJournals()->save($journal);
        });
    }

    /**
     * Update single journal.
     *
     * @param TransactionGroup $transactionGroup
     * @param TransactionJournal $journal
     * @param array $data
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

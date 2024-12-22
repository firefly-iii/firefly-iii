<?php

/**
 * GroupUpdateService.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Internal\Update;

use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;

/**
 * Class GroupUpdateService
 */
class GroupUpdateService
{
    /**
     * Update a transaction group.
     *
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    public function update(TransactionGroup $transactionGroup, array $data): TransactionGroup
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        app('log')->debug('Now in group update service', $data);

        /** @var array $transactions */
        $transactions = $data['transactions'] ?? [];
        // update group name.
        if (array_key_exists('group_title', $data)) {
            app('log')->debug(sprintf('Update transaction group #%d title.', $transactionGroup->id));
            $oldTitle                = $transactionGroup->title;
            $transactionGroup->title = $data['group_title'];
            $transactionGroup->save();
            event(
                new TriggeredAuditLog(
                    $transactionGroup->user,
                    $transactionGroup,
                    'update_group_title',
                    $oldTitle,
                    $data['group_title']
                )
            );
        }

        if (0 === count($transactions)) {
            app('log')->debug('No transactions submitted, do nothing.');

            return $transactionGroup;
        }

        if (1 === count($transactions) && 1 === $transactionGroup->transactionJournals()->count()) {
            /** @var TransactionJournal $first */
            $first = $transactionGroup->transactionJournals()->first();
            app('log')->debug(
                sprintf('Will now update journal #%d (only journal in group #%d)', $first->id, $transactionGroup->id)
            );
            $this->updateTransactionJournal($transactionGroup, $first, reset($transactions));
            $transactionGroup->touch();
            $transactionGroup->refresh();
            app('preferences')->mark();

            return $transactionGroup;
        }

        app('log')->debug('Going to update split group.');

        $existing     = $transactionGroup->transactionJournals->pluck('id')->toArray();
        $updated      = $this->updateTransactions($transactionGroup, $transactions);
        app('log')->debug('Array of updated IDs: ', $updated);

        if (0 === count($updated)) {
            app('log')->error('There were no transactions updated or created. Will not delete anything.');
            $transactionGroup->touch();
            $transactionGroup->refresh();
            app('preferences')->mark();

            return $transactionGroup;
        }

        $result       = array_diff($existing, $updated);
        app('log')->debug('Result of DIFF: ', $result);
        if (count($result) > 0) {
            /** @var string $deletedId */
            foreach ($result as $deletedId) {
                /** @var TransactionJournal $journal */
                $journal = $transactionGroup->transactionJournals()->find((int) $deletedId);

                /** @var JournalDestroyService $service */
                $service = app(JournalDestroyService::class);
                $service->destroy($journal);
            }
        }

        app('preferences')->mark();
        $transactionGroup->touch();
        $transactionGroup->refresh();

        return $transactionGroup;
    }

    /**
     * Update single journal.
     */
    private function updateTransactionJournal(
        TransactionGroup   $transactionGroup,
        TransactionJournal $journal,
        array              $data
    ): void {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        if (0 === count($data)) {
            return;
        }
        if (1 === count($data) && array_key_exists('transaction_journal_id', $data)) {
            return;
        }

        /** @var JournalUpdateService $updateService */
        $updateService = app(JournalUpdateService::class);
        $updateService->setTransactionGroup($transactionGroup);
        $updateService->setTransactionJournal($journal);
        $updateService->setData($data);
        $updateService->update();
    }

    /**
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    private function updateTransactions(TransactionGroup $transactionGroup, array $transactions): array
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        // updated or created transaction journals:
        $updated = [];

        /**
         * @var int   $index
         * @var array $transaction
         */
        foreach ($transactions as $index => $transaction) {
            app('log')->debug(sprintf('Now at #%d of %d', $index + 1, count($transactions)), $transaction);
            $journalId = (int) ($transaction['transaction_journal_id'] ?? 0);

            /** @var null|TransactionJournal $journal */
            $journal   = $transactionGroup->transactionJournals()->find($journalId);
            if (null === $journal) {
                app('log')->debug('This entry has no existing journal: make a new split.');
                // force the transaction type on the transaction data.
                // by plucking it from another journal in the group:
                if (!array_key_exists('type', $transaction)) {
                    app('log')->debug('No transaction type is indicated.');

                    /** @var null|TransactionJournal $randomJournal */
                    $randomJournal = $transactionGroup->transactionJournals()->inRandomOrder()->with(
                        ['transactionType']
                    )->first();
                    if (null !== $randomJournal) {
                        $transaction['type'] = $randomJournal->transactionType->type;
                        app('log')->debug(sprintf('Transaction type set to %s.', $transaction['type']));
                    }
                }
                app('log')->debug('Call createTransactionJournal');
                $newJournal = $this->createTransactionJournal($transactionGroup, $transaction);
                app('log')->debug('Done calling createTransactionJournal');
                if (null !== $newJournal) {
                    $updated[] = $newJournal->id;
                }
                if (null === $newJournal) {
                    app('log')->error('createTransactionJournal returned NULL, indicating something went wrong.');
                }
            }
            if (null !== $journal) {
                app('log')->debug('Call updateTransactionJournal');
                $this->updateTransactionJournal($transactionGroup, $journal, $transaction);
                $updated[] = $journal->id;
                app('log')->debug('Done calling updateTransactionJournal');
            }
        }

        return $updated;
    }

    /**
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    private function createTransactionJournal(TransactionGroup $transactionGroup, array $data): ?TransactionJournal
    {
        $submission = [
            'transactions' => [
                $data,
            ],
        ];

        /** @var TransactionJournalFactory $factory */
        $factory    = app(TransactionJournalFactory::class);
        $factory->setUser($transactionGroup->user);

        try {
            $collection = $factory->create($submission);
        } catch (FireflyException $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());

            throw new FireflyException(
                sprintf('Could not create new transaction journal: %s', $e->getMessage()),
                0,
                $e
            );
        }
        $collection->each(
            static function (TransactionJournal $journal) use ($transactionGroup): void {
                $transactionGroup->transactionJournals()->save($journal);
            }
        );
        if (0 === $collection->count()) {
            return null;
        }

        return $collection->first();
    }
}

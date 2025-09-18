<?php

/**
 * SafeGroupUpdateService.php
 * Copyright (c) 2024 james@firefly-iii.org
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
use FireflyIII\Services\Internal\Support\TransactionServiceTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class SafeGroupUpdateService
 * 
 * Provides safe transaction group updates with proper database transactions
 * and rollback support.
 */
class SafeGroupUpdateService
{
    use TransactionServiceTrait;
    
    /**
     * Update a transaction group with full transaction support.
     *
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    public function update(TransactionGroup $transactionGroup, array $data): TransactionGroup
    {
        Log::debug(sprintf('Now in %s with full transaction support', __METHOD__));
        Log::debug('Update data:', $data);

        try {
            return $this->executeInTransaction(function () use ($transactionGroup, $data) {
                // Lock the transaction group for update to prevent concurrent modifications
                $transactionGroup = TransactionGroup::lockForUpdate()->findOrFail($transactionGroup->id);
                
                /** @var array $transactions */
                $transactions = $data['transactions'] ?? [];
                
                // Update group title if provided
                if (array_key_exists('group_title', $data)) {
                    $this->updateGroupTitle($transactionGroup, $data['group_title']);
                }

                if (0 === count($transactions)) {
                    Log::debug('No transactions submitted, returning updated group.');
                    return $transactionGroup;
                }

                // Handle single transaction update
                if (1 === count($transactions) && 1 === $transactionGroup->transactionJournals()->count()) {
                    return $this->handleSingleTransactionUpdate($transactionGroup, reset($transactions));
                }

                // Handle split transaction update
                return $this->handleSplitTransactionUpdate($transactionGroup, $transactions);
            });
        } catch (DuplicateTransactionException $e) {
            Log::warning('Duplicate transaction detected during update: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update transaction group: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw new FireflyException(
                'Failed to update transaction group: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Update the group title with audit logging.
     */
    private function updateGroupTitle(TransactionGroup $transactionGroup, string $newTitle): void
    {
        Log::debug(sprintf('Updating transaction group #%d title', $transactionGroup->id));
        
        $oldTitle = $transactionGroup->title;
        $transactionGroup->title = $newTitle;
        $transactionGroup->save();
        
        event(new TriggeredAuditLog(
            $transactionGroup->user,
            $transactionGroup,
            'update_group_title',
            $oldTitle,
            $newTitle
        ));
    }

    /**
     * Handle update of a single transaction in the group.
     */
    private function handleSingleTransactionUpdate(
        TransactionGroup $transactionGroup,
        array $transactionData
    ): TransactionGroup {
        /** @var TransactionJournal $journal */
        $journal = $transactionGroup->transactionJournals()->lockForUpdate()->first();
        
        Log::debug(sprintf(
            'Updating single journal #%d in group #%d',
            $journal->id,
            $transactionGroup->id
        ));
        
        $this->updateTransactionJournal($transactionGroup, $journal, $transactionData);
        
        $transactionGroup->touch();
        $transactionGroup->refresh();
        app('preferences')->mark();
        
        return $transactionGroup;
    }

    /**
     * Handle update of split transactions in the group.
     */
    private function handleSplitTransactionUpdate(
        TransactionGroup $transactionGroup,
        array $transactions
    ): TransactionGroup {
        Log::debug('Updating split transaction group');
        
        // Get existing journal IDs with lock
        $existing = $transactionGroup->transactionJournals()
            ->lockForUpdate()
            ->pluck('id')
            ->toArray();
        
        // Process updates and track which journals were updated
        $updated = $this->processTransactionUpdates($transactionGroup, $transactions);
        
        Log::debug('Existing journal IDs: ', $existing);
        Log::debug('Updated journal IDs: ', $updated);
        
        if (0 === count($updated)) {
            throw new FireflyException('No transactions were updated or created');
        }
        
        // Delete journals that were not in the update
        $this->deleteRemovedJournals($transactionGroup, $existing, $updated);
        
        $transactionGroup->touch();
        $transactionGroup->refresh();
        app('preferences')->mark();
        
        return $transactionGroup;
    }

    /**
     * Process transaction updates and return updated journal IDs.
     *
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    private function processTransactionUpdates(
        TransactionGroup $transactionGroup,
        array $transactions
    ): array {
        $updated = [];
        
        foreach ($transactions as $index => $transaction) {
            Log::debug(sprintf('Processing transaction %d/%d', $index + 1, count($transactions)));
            
            $journalId = (int) ($transaction['transaction_journal_id'] ?? 0);
            
            if ($journalId > 0) {
                // Update existing journal
                $journal = $transactionGroup->transactionJournals()
                    ->lockForUpdate()
                    ->find($journalId);
                    
                if (null !== $journal) {
                    $this->updateTransactionJournal($transactionGroup, $journal, $transaction);
                    $updated[] = $journal->id;
                } else {
                    Log::warning(sprintf('Journal #%d not found in group #%d', $journalId, $transactionGroup->id));
                }
            } else {
                // Create new journal
                $newJournal = $this->createNewJournalInGroup($transactionGroup, $transaction);
                if ($newJournal instanceof TransactionJournal) {
                    $updated[] = $newJournal->id;
                }
            }
        }
        
        return $updated;
    }

    /**
     * Create a new journal in the transaction group.
     *
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    private function createNewJournalInGroup(
        TransactionGroup $transactionGroup,
        array $transaction
    ): ?TransactionJournal {
        Log::debug('Creating new journal in group');
        
        // Ensure transaction type is set
        if (!array_key_exists('type', $transaction)) {
            $transaction['type'] = $this->inferTransactionType($transactionGroup);
        }
        
        $submission = [
            'transactions' => [$transaction],
        ];
        
        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($transactionGroup->user);
        $factory->setUserGroup($transactionGroup->userGroup);
        
        try {
            $collection = $factory->create($submission);
        } catch (FireflyException $e) {
            Log::error('Failed to create journal: ' . $e->getMessage());
            throw new FireflyException(
                'Could not create new transaction journal: ' . $e->getMessage(),
                0,
                $e
            );
        }
        
        if (0 === $collection->count()) {
            return null;
        }
        
        $journal = $collection->first();
        $transactionGroup->transactionJournals()->save($journal);
        
        return $journal;
    }

    /**
     * Infer transaction type from existing journals in the group.
     */
    private function inferTransactionType(TransactionGroup $transactionGroup): string
    {
        /** @var TransactionJournal|null $existingJournal */
        $existingJournal = $transactionGroup->transactionJournals()
            ->with(['transactionType'])
            ->first();
            
        if (null !== $existingJournal) {
            return $existingJournal->transactionType->type;
        }
        
        // Default to withdrawal if no journals exist
        return 'withdrawal';
    }

    /**
     * Update a single transaction journal.
     */
    private function updateTransactionJournal(
        TransactionGroup $transactionGroup,
        TransactionJournal $journal,
        array $data
    ): void {
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
     * Delete journals that were removed from the group.
     */
    private function deleteRemovedJournals(
        TransactionGroup $transactionGroup,
        array $existingIds,
        array $updatedIds
    ): void {
        $toDelete = array_diff($existingIds, $updatedIds);
        
        if (count($toDelete) > 0) {
            Log::debug('Deleting removed journals: ', $toDelete);
            
            /** @var JournalDestroyService $destroyService */
            $destroyService = app(JournalDestroyService::class);
            
            foreach ($toDelete as $journalId) {
                $journal = $transactionGroup->transactionJournals()->find((int) $journalId);
                if (null !== $journal) {
                    $destroyService->destroy($journal);
                }
            }
        }
    }
}
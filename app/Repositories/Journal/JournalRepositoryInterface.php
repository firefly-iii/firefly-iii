<?php
/**
 * JournalRepositoryInterface.php
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

namespace FireflyIII\Repositories\Journal;

use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Interface JournalRepositoryInterface.
 */
interface JournalRepositoryInterface
{
    /**
     * @param TransactionJournal $journal
     * @param TransactionType    $type
     * @param Account            $source
     * @param Account            $destination
     *
     * @return MessageBag
     */
    public function convert(TransactionJournal $journal, TransactionType $type, Account $source, Account $destination): MessageBag;

    /**
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public function countTransactions(TransactionJournal $journal): int;

    /**
     * Deletes a journal.
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function delete(TransactionJournal $journal): bool;

    /**
     * Find a specific journal.
     *
     * @param int $journalId
     *
     * @return TransactionJournal
     */
    public function find(int $journalId): TransactionJournal;

    /**
     * @param Transaction $transaction
     *
     * @return Transaction|null
     */
    public function findOpposingTransaction(Transaction $transaction): ?Transaction;

    /**
     * @param int $transactionid
     *
     * @return Transaction|null
     */
    public function findTransaction(int $transactionid): ?Transaction;

    /**
     * Get users very first transaction journal.
     *
     * @return TransactionJournal
     */
    public function first(): TransactionJournal;

    /**
     * @return Collection
     */
    public function getTransactionTypes(): Collection;

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function isTransfer(TransactionJournal $journal): bool;

    /**
     * @param Transaction $transaction
     *
     * @return bool
     */
    public function reconcile(Transaction $transaction): bool;

    /**
     * @param TransactionJournal $journal
     * @param int                $order
     *
     * @return bool
     */
    public function setOrder(TransactionJournal $journal, int $order): bool;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function store(array $data): TransactionJournal;

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    public function update(TransactionJournal $journal, array $data): TransactionJournal;

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    public function updateSplitJournal(TransactionJournal $journal, array $data): TransactionJournal;
}

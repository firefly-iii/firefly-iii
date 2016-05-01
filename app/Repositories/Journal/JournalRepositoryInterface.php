<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Journal;

use Carbon\Carbon;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface JournalRepositoryInterface
 *
 * @package FireflyIII\Repositories\Journal
 */
interface JournalRepositoryInterface
{
    /**
     * Deletes a journal.
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function delete(TransactionJournal $journal): bool;

    /**
     * Find a specific journal
     *
     * @param int $journalId
     *
     * @return TransactionJournal
     */
    public function find(int $journalId) : TransactionJournal;

    /**
     * Get users very first transaction journal
     *
     * @return TransactionJournal
     */
    public function first(): TransactionJournal;

    /**
     * Returns the amount in the account before the specified transaction took place.
     *
     * @deprecated
     *
     * @param TransactionJournal $journal
     * @param Transaction        $transaction
     *
     * @return string
     */
    public function getAmountBefore(TransactionJournal $journal, Transaction $transaction): string;

    /**
     * Returns a page of a specific type(s) of journal.
     *
     * @param array $types
     * @param int   $page
     * @param int   $pageSize
     *
     * @return LengthAwarePaginator
     */
    public function getJournals(array $types, int $page, int $pageSize = 50): LengthAwarePaginator;

    /**
     * Returns a collection of ALL journals, given a specific account and a date range.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function getJournalsInRange(Collection $accounts, Carbon $start, Carbon $end): Collection;

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

}

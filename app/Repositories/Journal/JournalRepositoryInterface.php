<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Journal;

use Carbon\Carbon;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
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
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function delete(TransactionJournal $journal): bool;

    /**
     * Get users first transaction journal
     *
     * @return TransactionJournal
     */
    public function first(): TransactionJournal;

    /**
     * @param TransactionJournal $journal
     * @param Transaction        $transaction
     *
     * @return string
     */
    public function getAmountBefore(TransactionJournal $journal, Transaction $transaction): string;

    /**
     * @param array $types
     * @param int   $offset
     * @param int   $count
     *
     * @return Collection
     */
    public function getCollectionOfTypes(array $types, int $offset, int $count):Collection;

    /**
     * @param TransactionType $dbType
     *
     * @return Collection
     */
    public function getJournalsOfType(TransactionType $dbType): Collection;

    /**
     * @param array $types
     * @param int   $page
     * @param int   $pageSize
     *
     * @return LengthAwarePaginator
     */
    public function getJournalsOfTypes(array $types, int $page, int $pageSize = 50): LengthAwarePaginator;

    /**
     * @param string $type
     *
     * @return TransactionType
     */
    public function getTransactionType(string $type): TransactionType;

    /**
     * @param  int   $journalId
     * @param Carbon $date
     *
     * @return TransactionJournal
     */
    public function getWithDate(int $journalId, Carbon $date): TransactionJournal;

    /**
     * @param TransactionJournal $journal
     * @param array              $array
     *
     * @return void

    /**
     *
     * @param TransactionJournal $journal
     * @param array              $array
     *
     * @return bool
     */
    public function saveTags(TransactionJournal $journal, array $array): bool;

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
     * @param array              $array
     *
     * @return bool
     */
    public function updateTags(TransactionJournal $journal, array $array): bool;
}

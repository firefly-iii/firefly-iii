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
    public function delete(TransactionJournal $journal);

    /**
     * Get users first transaction journal
     *
     * @return TransactionJournal
     */
    public function first();

    /**
     * @param TransactionJournal $journal
     * @param Transaction        $transaction
     *
     * @return float
     */
    public function getAmountBefore(TransactionJournal $journal, Transaction $transaction);

    /**
     * @param TransactionType $dbType
     *
     * @return Collection
     */
    public function getJournalsOfType(TransactionType $dbType);

    /**
     * @param array $types
     * @param int   $offset
     * @param int   $page
     *
     * @return LengthAwarePaginator
     */
    public function getJournalsOfTypes(array $types, int $offset, int $page);

    /**
     * @param string $type
     *
     * @return TransactionType
     */
    public function getTransactionType(string $type);

    /**
     * @param  int   $journalId
     * @param Carbon $date
     *
     * @return TransactionJournal
     */
    public function getWithDate(int $journalId, Carbon $date);

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
     * @return void
     */
    public function saveTags(TransactionJournal $journal, array $array);

    /**
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function store(array $data);

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return mixed
     */
    public function update(TransactionJournal $journal, array $data);

    /**
     * @param TransactionJournal $journal
     * @param array              $array
     *
     * @return mixed
     */
    public function updateTags(TransactionJournal $journal, array $array);
}

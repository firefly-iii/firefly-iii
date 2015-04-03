<?php

namespace FireflyIII\Repositories\Journal;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Interface JournalRepositoryInterface
 *
 * @package FireflyIII\Repositories\Journal
 */
interface JournalRepositoryInterface
{
    /**
     *
     * Get the account_id, which is the asset account that paid for the transaction.
     *
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public function getAssetAccount(TransactionJournal $journal);

    /**
     * @param string             $query
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function searchRelated($query, TransactionJournal $journal);

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
     * Get users first transaction journal
     * @return TransactionJournal
     */
    public function first();
}

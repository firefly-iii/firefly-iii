<?php

namespace FireflyIII\Repositories\Journal;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;

/**
 * Interface JournalRepositoryInterface
 *
 * @package FireflyIII\Repositories\Journal
 */
interface JournalRepositoryInterface
{
    /**
     * Get users first transaction journal
     *
     * @return TransactionJournal
     */
    public function first();

    /**
     * @param TransactionType $dbType
     *
     * @return Collection
     */
    public function getJournalsOfType(TransactionType $dbType);

    /**
     * @param $type
     *
     * @return TransactionType
     */
    public function getTransactionType($type);

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

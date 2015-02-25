<?php

namespace FireflyIII\Repositories\Journal;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;

/**
 * Interface JournalRepositoryInterface
 *
 * @package FireflyIII\Repositories\Journal
 */
interface JournalRepositoryInterface
{
    /**
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function store(array $data);

    /**
     * @param string              $query
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function searchRelated($query, TransactionJournal $journal);
}
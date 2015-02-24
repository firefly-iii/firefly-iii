<?php

namespace FireflyIII\Repositories\Journal;

use FireflyIII\Models\TransactionJournal;

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

}
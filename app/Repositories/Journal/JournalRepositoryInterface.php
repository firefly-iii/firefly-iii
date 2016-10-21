<?php
/**
 * JournalRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

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
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function store(array $data): TransactionJournal;

    /**
     * Store journal only, uncompleted, with attachments if necessary.
     *
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function storeJournal(array $data): TransactionJournal;

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

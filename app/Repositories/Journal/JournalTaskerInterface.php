<?php
/**
 * JournalTaskerInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\Journal;


use Carbon\Carbon;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface JournalTaskerInterface
 *
 * @package FireflyIII\Repositories\Journal
 */
interface JournalTaskerInterface
{
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
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getPiggyBankEvents(TransactionJournal $journal): Collection;

    /**
     * Get an overview of the transactions of a journal, tailored to the view
     * that shows a transaction (transaction/show/xx).
     *
     * @param TransactionJournal $journal
     *
     * @return array
     */
    public function getTransactionsOverview(TransactionJournal $journal): array;
}

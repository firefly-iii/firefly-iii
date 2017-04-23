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

declare(strict_types=1);

namespace FireflyIII\Repositories\Journal;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface JournalTaskerInterface
 *
 * @package FireflyIII\Repositories\Journal
 */
interface JournalTaskerInterface
{
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

    /**
     * @param User $user
     */
    public function setUser(User $user);
}

<?php
/**
 * AccountRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;

/**
 * Interface AccountRepositoryInterface
 *
 * @package FireflyIII\Repositories\Account
 */
interface AccountRepositoryInterface
{

    /**
     * Moved here from account CRUD.
     *
     * @param array $types
     *
     * @return int
     */
    public function count(array $types): int;

    /**
     * Moved here from account CRUD.
     *
     * @param Account $account
     * @param Account $moveTo
     *
     * @return bool
     */
    public function destroy(Account $account, Account $moveTo): bool;

    /**
     * Returns the transaction from a journal that is related to a given account. Since a journal generally only contains
     * two transactions, this will return one of the two. This method fails horribly when the journal has more than two transactions,
     * but luckily it isn't used for such folly.
     *
     * @param TransactionJournal $journal
     * @param Account            $account
     *
     * @return Transaction
     * @throws FireflyException
     */
    public function getFirstTransaction(TransactionJournal $journal, Account $account): Transaction;

    /**
     * Returns the date of the very last transaction in this account.
     *
     * @param Account $account
     *
     * @return Carbon
     */
    public function newestJournalDate(Account $account): Carbon;

    /**
     * Returns the date of the very first transaction in this account.
     *
     * @param Account $account
     *
     * @return Carbon
     */
    public function oldestJournalDate(Account $account): Carbon;

    /**
     *
     * @param Account $account
     *
     * @return TransactionJournal
     */
    public function openingBalanceTransaction(Account $account) : TransactionJournal;

}

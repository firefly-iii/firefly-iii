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
     * This method will call AccountRepositoryInterface::journalsInPeriod and get all withdrawaks made from the given $accounts,
     * as well as the transfers that move away from those $accounts. This is a slightly sharper selection
     * than made by journalsInPeriod itself.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @see AccountRepositoryInterface::journalsInPeriod
     *
     * @return Collection
     */
    public function expensesInPeriod(Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * @param Account $account
     *
     * @return Carbon
     */
    public function firstUseDate(Account $account): Carbon;

    /**
     * @param TransactionJournal $journal
     * @param Account            $account
     *
     * @return Transaction
     */
    public function getFirstTransaction(TransactionJournal $journal, Account $account): Transaction;

    /**
     * Get the accounts of a user that have piggy banks connected to them.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getPiggyBankAccounts(Carbon $start, Carbon $end): Collection;

    /**
     * Get savings accounts.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getSavingsAccounts(Carbon $start, Carbon $end): Collection;

    /**
     * This method will call AccountRepositoryInterface::journalsInPeriod and get all deposits made to the given $accounts,
     * as well as the transfers that move to to those $accounts. This is a slightly sharper selection
     * than made by journalsInPeriod itself.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @see AccountRepositoryInterface::journalsInPeriod
     *
     * @return Collection
     */
    public function incomesInPeriod(Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * @param Collection $accounts
     * @param array      $types
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriod(Collection $accounts, array $types, Carbon $start, Carbon $end): Collection;

    /**
     *
     * @param Account $account
     * @param Carbon  $date
     *
     * @return string
     */
    public function leftOnAccount(Account $account, Carbon $date): string;

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

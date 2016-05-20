<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
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
     * @param array $types
     *
     * @return int
     */
    public function countAccounts(array $types): int;

    /**
     * This method is almost the same as ::earnedInPeriod, but only works for revenue accounts
     * instead of the implied asset accounts for ::earnedInPeriod. ::earnedInPeriod will tell you
     * how much money was earned by the given asset accounts. This method will tell you how much money
     * these given revenue accounts sent. Ie. how much money was made FROM these revenue accounts.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedFromInPeriod(Collection $accounts, Carbon $start, Carbon $end): string;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriod(Collection $accounts, Carbon $start, Carbon $end): string;

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
     * Gets all the accounts by ID, for a given set.
     *
     * @param array $ids
     *
     * @return \Illuminate\Support\Collection
     */
    public function get(array $ids): Collection;

    /**
     * @param array $accountIds
     *
     * @return Collection
     */
    public function getAccountsById(array $accountIds): Collection;

    /**
     * @param array $types
     *
     * @return Collection
     */
    public function getAccountsByType(array $types): Collection;

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

    /**
     * This method is almost the same as ::spentInPeriod, but only works for expense accounts
     * instead of the implied asset accounts for ::spentInPeriod. ::spentInPeriod will tell you
     * how much money was spent by the given asset accounts. This method will tell you how much money
     * these given expense accounts received. Ie. how much money was spent AT these expense accounts.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentAtInPeriod(Collection $accounts, Carbon $start, Carbon $end): string;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $accounts, Carbon $start, Carbon $end): string;
    
}

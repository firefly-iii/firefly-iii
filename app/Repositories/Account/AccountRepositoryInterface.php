<?php

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Pagination\LengthAwarePaginator;
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
    public function countAccounts(array $types);

    /**
     * @param Account $account
     *
     * @return boolean
     */
    public function destroy(Account $account);

    /**
     * @param array $types
     *
     * @return mixed
     */
    public function getAccounts(array $types);

    /**
     * @param TransactionJournal $journal
     * @param Account            $account
     *
     * @return Transaction
     */
    public function getFirstTransaction(TransactionJournal $journal, Account $account);

    /**
     * @return Collection
     */
    public function getCreditCards();

    /**
     * Get the accounts of a user that have piggy banks connected to them.
     *
     * @return Collection
     */
    public function getPiggyBankAccounts();


    /**
     * Get all transfers TO this account in this range.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return Collection
     */
    public function getTransfersInRange(Account $account, Carbon $start, Carbon $end);

    /**
     * @param Preference $preference
     *
     * @return Collection
     */
    public function getFrontpageAccounts(Preference $preference);

    /**
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return mixed
     */
    public function getFrontpageTransactions(Account $account, Carbon $start, Carbon $end);

    /**
     * @param Account $account
     * @param         $page
     *
     * @return LengthAwarePaginator
     */
    public function getJournals(Account $account, $page);

    /**
     * @param Account $account
     *
     * @return Carbon|null
     */
    public function getLastActivity(Account $account);

    /**
     * @return float
     */
    public function sumOfEverything();

    /**
     * Get savings accounts and the balance difference in the period.
     *
     * @return Collection
     */
    public function getSavingsAccounts();

    /**
     * @param Account $account
     * @param Carbon  $date
     *
     * @return float
     */
    public function leftOnAccount(Account $account, Carbon $date);

    /**
     * @param Account $account
     *
     * @return TransactionJournal|null
     */
    public function openingBalanceTransaction(Account $account);

    /**
     * @param array $data
     *
     * @return Account
     */
    public function store(array $data);

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     */
    public function update(Account $account, array $data);
}

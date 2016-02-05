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
     * @param Account $moveTo
     *
     * @return boolean
     */
    public function destroy(Account $account, Account $moveTo = null);

    /**
     * @param int $accountId
     *
     * @deprecated
     *
     * @return Account
     */
    public function find(int $accountId);

    /**
     * Gets all the accounts by ID, for a given set.
     *
     * @param array $ids
     *
     * @return Collection
     */
    public function get(array $ids);

    /**
     * @param array $types
     *
     * @return Collection
     */
    public function getAccounts(array $types);

    /**
     * This method returns the users credit cards, along with some basic information about the
     * balance they have on their CC. To be used in the JSON boxes on the front page that say
     * how many bills there are still left to pay. The balance will be saved in field "balance".
     *
     * To get the balance, the field "date" is necessary.
     *
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getCreditCards(Carbon $date);

    /**
     * @param TransactionJournal $journal
     * @param Account            $account
     *
     * @return Transaction
     */
    public function getFirstTransaction(TransactionJournal $journal, Account $account);

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
     * Get the accounts of a user that have piggy banks connected to them.
     *
     * @return Collection
     */
    public function getPiggyBankAccounts();

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
     * @return string
     */
    public function sumOfEverything();

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     */
    public function update(Account $account, array $data);
}

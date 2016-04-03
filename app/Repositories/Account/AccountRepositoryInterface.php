<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
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
     * Returns a list of transactions TO the $account, not including transfers
     * and/or expenses in the $accounts list.
     *
     * @param Account    $account
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function getExpensesByDestination(Account $account, Collection $accounts, Carbon $start, Carbon $end);

    /**
     * @param array $types
     *
     * @return int
     */
    public function countAccounts(array $types): int;

    /**
     * @param Account $account
     * @param Account $moveTo
     *
     * @return boolean
     */
    public function destroy(Account $account, Account $moveTo): bool;

    /**
     * @param int $accountId
     *
     * @return Account
     */
    public function find(int $accountId): Account;

    /**
     * Gets all the accounts by ID, for a given set.
     *
     * @param array $ids
     *
     * @return \Illuminate\Support\Collection
     */
    public function get(array $ids): Collection;

    /**
     * @param array $types
     *
     * @return Collection
     */
    public function getAccounts(array $types): Collection;

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
    public function getCreditCards(Carbon $date): Collection;

    /**
     * @param TransactionJournal $journal
     * @param Account            $account
     *
     * @return Transaction
     */
    public function getFirstTransaction(TransactionJournal $journal, Account $account): Transaction;

    /**
     * @param Preference $preference
     *
     * @return Collection
     */
    public function getFrontpageAccounts(Preference $preference): Collection;

    /**
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return Collection
     */
    public function getFrontpageTransactions(Account $account, Carbon $start, Carbon $end): Collection;

    /**
     * @param Account $account
     * @param         $page
     *
     * @return LengthAwarePaginator
     */
    public function getJournals(Account $account, $page): LengthAwarePaginator;

    /**
     * Get the accounts of a user that have piggy banks connected to them.
     *
     * @return Collection
     */
    public function getPiggyBankAccounts(): Collection;

    /**
     * Get savings accounts and the balance difference in the period.
     *
     * @return Collection
     */
    public function getSavingsAccounts() : Collection;

    /**
     * @param Account $account
     * @param Carbon  $date
     *
     * @return string
     */
    public function leftOnAccount(Account $account, Carbon $date): string;

    /**
     * @param Account $account
     *
     * @return TransactionJournal
     */
    public function openingBalanceTransaction(Account $account) : TransactionJournal;

    /**
     * @param array $data
     *
     * @return Account
     */
    public function store(array $data) : Account;

    /**
     * @param $account
     * @param $name
     * @param $value
     *
     * @return AccountMeta
     */
    public function storeMeta($account, $name, $value): AccountMeta;

    /**
     * @return string
     */
    public function sumOfEverything() : string;

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     */
    public function update(Account $account, array $data): Account;
}

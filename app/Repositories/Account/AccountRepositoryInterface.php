<?php

namespace FireflyIII\Repositories\Account;

use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\Preference;
use Illuminate\Support\Collection;
use Carbon\Carbon;
/**
 * Interface AccountRepositoryInterface
 *
 * @package FireflyIII\Repositories\Account
 */
interface AccountRepositoryInterface
{
    /**
     * @param Account $account
     *
     * @return boolean
     */
    public function destroy(Account $account);

    /**
     * @return int
     */
    public function countAssetAccounts();

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
     * @param string  $range
     *
     * @return mixed
     */
    public function getJournals(Account $account, $page);

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

    /**
     * @param Account $account
     *
     * @return float
     */
    public function leftOnAccount(Account $account);

    /**
     * Get savings accounts and the balance difference in the period.
     *
     * @return Collection
     */
    public function getSavingsAccounts();
}
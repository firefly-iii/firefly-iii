<?php

namespace FireflyIII\Repositories\Account;

use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionJournal;

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
     * @param Account $account
     * @param int     $page
     * @param string  $range
     *
     * @return mixed
     */
    public function getJournals(Account $account, $page, $range = 'session');

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
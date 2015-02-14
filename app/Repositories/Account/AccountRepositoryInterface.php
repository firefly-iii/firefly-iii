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
     * @param array $data
     *
     * @return Account
     */
    public function store(array $data);

    /**
     * @param Account $account
     *
     * @return boolean
     */
    public function destroy(Account $account);

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
     * @return TransactionJournal|null
     */
    public function openingBalanceTransaction(Account $account);


}
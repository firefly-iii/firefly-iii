<?php
declare(strict_types = 1);

/**
 * AccountCrudInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Crud\Account;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use Illuminate\Support\Collection;

/**
 * Interface AccountCrudInterface
 *
 * @package FireflyIII\Crud\Account
 */
interface AccountCrudInterface
{
    /**
     * @param Account $account
     * @param Account $moveTo
     *
     * @return bool
     */
    public function destroy(Account $account, Account $moveTo): bool;

    /**
     * @param int $accountId
     *
     * @return Account
     */
    public function find(int $accountId): Account;

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
    public function storeMeta(Account $account, string $name, $value): AccountMeta;


    /**
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     */
    public function update(Account $account, array $data): Account;
}
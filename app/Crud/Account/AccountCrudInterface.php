<?php
/**
 * AccountCrudInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

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
     * @param int $accountId
     *
     * @return Account
     */
    public function find(int $accountId): Account;

    /**
     * @param string $number
     * @param array  $types
     *
     * @return Account
     */
    public function findByAccountNumber(string $number, array $types): Account;

    /**
     * @param string $iban
     * @param array  $types
     *
     * @return Account
     */
    public function findByIban(string $iban, array $types): Account;

    /**
     * @param string $name
     * @param array  $types
     *
     * @return Account
     */
    public function findByName(string $name, array $types): Account;

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
     * @param array $types
     *
     * @return Collection
     */
    public function getActiveAccountsByType(array $types): Collection;

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

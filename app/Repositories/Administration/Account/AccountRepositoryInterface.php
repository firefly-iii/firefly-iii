<?php


/*
 * AccountRepositoryInterface.php
 * Copyright (c) 2023 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\Administration\Account;

use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Support\Collection;

/**
 * Interface AccountRepositoryInterface
 */
interface AccountRepositoryInterface
{
    /**
     * @param int $accountId
     *
     * @return Account|null
     */
    public function find(int $accountId): ?Account;

    /**
     * @param Account $account
     *
     * @return TransactionCurrency|null
     */
    public function getAccountCurrency(Account $account): ?TransactionCurrency;

    /**
     * @param array $accountIds
     *
     * @return Collection
     */
    public function getAccountsById(array $accountIds): Collection;

    /**
     * @param array      $types
     * @param array|null $sort
     *
     * @return Collection
     */
    public function getAccountsByType(array $types, ?array $sort = []): Collection;

    /**
     * @param array $types
     *
     * @return Collection
     */
    public function getActiveAccountsByType(array $types): Collection;

    /**
     * Return meta value for account. Null if not found.
     *
     * @param Account $account
     * @param string  $field
     *
     * @return null|string
     */
    public function getMetaValue(Account $account, string $field): ?string;

    /**
     * @param string $query
     * @param array  $types
     * @param int    $limit
     *
     * @return Collection
     */
    public function searchAccount(string $query, array $types, int $limit): Collection;

}

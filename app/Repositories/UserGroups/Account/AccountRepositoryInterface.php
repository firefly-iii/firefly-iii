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

namespace FireflyIII\Repositories\UserGroups\Account;

use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface AccountRepositoryInterface
 */
interface AccountRepositoryInterface
{
    public function find(int $accountId): ?Account;

    public function findByAccountNumber(string $number, array $types): ?Account;

    public function findByIbanNull(string $iban, array $types): ?Account;

    public function findByName(string $name, array $types): ?Account;

    public function getAccountCurrency(Account $account): ?TransactionCurrency;

    public function getAccountsById(array $accountIds): Collection;

    public function getAccountsByType(array $types, ?array $sort = []): Collection;

    public function getActiveAccountsByType(array $types): Collection;

    /**
     * Return meta value for account. Null if not found.
     */
    public function getMetaValue(Account $account, string $field): ?string;

    public function searchAccount(string $query, array $types, int $limit): Collection;

    public function setUser(User $user): void;

    public function setUserGroup(UserGroup $userGroup): void;
}

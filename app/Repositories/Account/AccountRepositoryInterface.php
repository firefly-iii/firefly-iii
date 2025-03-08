<?php

/**
 * AccountRepositoryInterface.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Location;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface AccountRepositoryInterface.
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 */
interface AccountRepositoryInterface
{
    /**
     * Moved here from account CRUD.
     */
    public function count(array $types): int;

    /**
     * Moved here from account CRUD.
     */
    public function destroy(Account $account, ?Account $moveTo): bool;

    /**
     * Find account with same name OR same IBAN or both, but not the same type or ID.
     */
    public function expandWithDoubles(Collection $accounts): Collection;

    public function find(int $accountId): ?Account;

    public function findByAccountNumber(string $number, array $types): ?Account;

    public function findByIbanNull(string $iban, array $types): ?Account;

    public function findByName(string $name, array $types): ?Account;

    public function getAccountCurrency(Account $account): ?TransactionCurrency;

    /**
     * Return account type or null if not found.
     */
    public function getAccountTypeByType(string $type): ?AccountType;

    public function getAccountsById(array $accountIds): Collection;

    /**
     * @param array<int, int|string> $types
     */
    public function getAccountsByType(array $types, ?array $sort = []): Collection;

    public function getActiveAccountsByType(array $types): Collection;

    public function getAttachments(Account $account): Collection;

    public function getCashAccount(): Account;

    public function getCreditTransactionGroup(Account $account): ?TransactionGroup;

    public function getInactiveAccountsByType(array $types): Collection;

    /**
     * Get account location, if any.
     */
    public function getLocation(Account $account): ?Location;

    /**
     * Return meta value for account. Null if not found.
     */
    public function getMetaValue(Account $account, string $field): ?string;

    /**
     * Get note text or null.
     */
    public function getNoteText(Account $account): ?string;

    public function getOpeningBalance(Account $account): ?TransactionJournal;

    /**
     * Returns the amount of the opening balance for this account.
     */
    public function getOpeningBalanceAmount(Account $account, bool $convertToNative): ?string;

    /**
     * Return date of opening balance as string or null.
     */
    public function getOpeningBalanceDate(Account $account): ?string;

    public function getOpeningBalanceGroup(Account $account): ?TransactionGroup;

    public function getPiggyBanks(Account $account): Collection;

    /**
     * Find or create the opposing reconciliation account.
     */
    public function getReconciliation(Account $account): ?Account;

    public function getUsedCurrencies(Account $account): Collection;

    public function isLiability(Account $account): bool;

    public function maxOrder(string $type): int;

    /**
     * Returns the date of the very first transaction in this account.
     */
    public function oldestJournal(Account $account): ?TransactionJournal;

    /**
     * Returns the date of the very first transaction in this account.
     */
    public function oldestJournalDate(Account $account): ?Carbon;

    /**
     * Reset order types of the mentioned accounts.
     */
    public function resetAccountOrder(): void;

    public function searchAccount(string $query, array $types, int $limit): Collection;

    public function searchAccountNr(string $query, array $types, int $limit): Collection;

    public function store(array $data): Account;

    public function update(Account $account, array $data): Account;
}

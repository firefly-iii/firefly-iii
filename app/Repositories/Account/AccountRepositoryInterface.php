<?php
/**
 * AccountRepositoryInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Support\Collection;


/**
 * Interface AccountRepositoryInterface.
 */
interface AccountRepositoryInterface
{

    /**
     * @param Account $account
     *
     * @return TransactionJournal|null
     *
     */
    public function getOpeningBalance(Account $account): ?TransactionJournal;

    /**
     * @param Account $account
     * @return TransactionGroup|null
     */
    public function getOpeningBalanceGroup(Account $account): ?TransactionGroup;

    /**
     * Moved here from account CRUD.
     *
     * @param array $types
     *
     * @return int
     */
    public function count(array $types): int;

    /**
     * Moved here from account CRUD.
     *
     * @param Account $account
     * @param Account|null $moveTo
     *
     * @return bool
     */
    public function destroy(Account $account, ?Account $moveTo): bool;

    /**
     * Find by account number. Is used.
     *
     * @param string $number
     * @param array $types
     *
     * @return Account|null
     */
    public function findByAccountNumber(string $number, array $types): ?Account;

    /**
     * @param string $iban
     * @param array $types
     *
     * @return Account|null
     */
    public function findByIbanNull(string $iban, array $types): ?Account;

    /**
     * @param string $name
     * @param array $types
     *
     * @return Account|null
     */
    public function findByName(string $name, array $types): ?Account;

    /**
     * @param int $accountId
     *
     * @return Account|null
     */
    public function findNull(int $accountId): ?Account;

    /**
     * @param Account $account
     *
     * @return TransactionCurrency|null
     */
    public function getAccountCurrency(Account $account): ?TransactionCurrency;

    /**
     * @param Account $account
     *
     * @return string
     */
    public function getAccountType(Account $account): string;

    /**
     * Return account type or null if not found.
     *
     * @param string $type
     *
     * @return AccountType|null
     */
    public function getAccountTypeByType(string $type): ?AccountType;

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
     * @return Account
     */
    public function getCashAccount(): Account;

    /**
     * Return meta value for account. Null if not found.
     *
     * @param Account $account
     * @param string $field
     *
     * @return null|string
     */
    public function getMetaValue(Account $account, string $field): ?string;

    /**
     * Get note text or null.
     *
     * @param Account $account
     *
     * @return null|string
     */
    public function getNoteText(Account $account): ?string;

    /**
     * Returns the amount of the opening balance for this account.
     *
     * @param Account $account
     *
     * @return string
     */
    public function getOpeningBalanceAmount(Account $account): ?string;

    /**
     * Return date of opening balance as string or null.
     *
     * @param Account $account
     *
     * @return null|string
     */
    public function getOpeningBalanceDate(Account $account): ?string;

    /**
     * @param Account $account
     *
     * @return Collection
     */
    public function getPiggyBanks(Account $account): Collection;

    /**
     * Find or create the opposing reconciliation account.
     *
     * @param Account $account
     *
     * @return Account|null
     */
    public function getReconciliation(Account $account): ?Account;


    /**
     * @param Account $account
     *
     * @return bool
     */
    public function isLiability(Account $account): bool;


    /**
     * Returns the date of the very first transaction in this account.
     *
     * @param Account $account
     *
     * @return TransactionJournal|null
     */
    public function oldestJournal(Account $account): ?TransactionJournal;

    /**
     * Returns the date of the very first transaction in this account.
     *
     * @param Account $account
     *
     * @return Carbon|null
     */
    public function oldestJournalDate(Account $account): ?Carbon;

    /**
     * @param string $query
     * @param array $types
     *
     * @return Collection
     */
    public function searchAccount(string $query, array $types): Collection;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param array $data
     *
     * @return Account
     */
    public function store(array $data): Account;

    /**
     * @param Account $account
     * @param array $data
     *
     * @return Account
     */
    public function update(Account $account, array $data): Account;
}

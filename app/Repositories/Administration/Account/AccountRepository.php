<?php


/*
 * AccountRepository.php
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
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Repositories\Administration\AdministrationTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Class AccountRepository
 */
class AccountRepository implements AccountRepositoryInterface
{
    use AdministrationTrait;

    /**
     * @param Account $account
     *
     * @return TransactionCurrency|null
     */
    public function getAccountCurrency(Account $account): ?TransactionCurrency
    {
        $type = $account->accountType->type;
        $list = config('firefly.valid_currency_account_types');

        // return null if not in this list.
        if (!in_array($type, $list, true)) {
            return null;
        }
        $currencyId = (int)$this->getMetaValue($account, 'currency_id');
        if ($currencyId > 0) {
            return TransactionCurrency::find($currencyId);
        }

        return null;
    }

    /**
     * Return meta value for account. Null if not found.
     *
     * @param Account $account
     * @param string  $field
     *
     * @return null|string
     */
    public function getMetaValue(Account $account, string $field): ?string
    {
        $result = $account->accountMeta->filter(
            function (AccountMeta $meta) use ($field) {
                return strtolower($meta->name) === strtolower($field);
            }
        );
        if (0 === $result->count()) {
            return null;
        }
        if (1 === $result->count()) {
            return (string)$result->first()->data;
        }

        return null;
    }

    /**
     * @param int $accountId
     *
     * @return Account|null
     */
    public function find(int $accountId): ?Account
    {
        $account = $this->user->accounts()->find($accountId);
        if (null === $account) {
            $account = $this->userGroup->accounts()->find($accountId);
        }
        return $account;
    }

    /**
     * @param array $accountIds
     *
     * @return Collection
     */
    public function getAccountsById(array $accountIds): Collection
    {
        $query = $this->userGroup->accounts();

        if (0 !== count($accountIds)) {
            $query->whereIn('accounts.id', $accountIds);
        }
        $query->orderBy('accounts.order', 'ASC');
        $query->orderBy('accounts.active', 'DESC');
        $query->orderBy('accounts.name', 'ASC');

        return $query->get(['accounts.*']);
    }

    /**
     * @inheritDoc
     */
    public function getAccountsByType(array $types, ?array $sort = []): Collection
    {
        $res   = array_intersect([AccountType::ASSET, AccountType::MORTGAGE, AccountType::LOAN, AccountType::DEBT], $types);
        $query = $this->userGroup->accounts();
        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }

        // add sort parameters. At this point they're filtered to allowed fields to sort by:
        if (0 !== count($sort)) {
            foreach ($sort as $param) {
                $query->orderBy($param[0], $param[1]);
            }
        }

        if (0 === count($sort)) {
            if (0 !== count($res)) {
                $query->orderBy('accounts.order', 'ASC');
            }
            $query->orderBy('accounts.active', 'DESC');
            $query->orderBy('accounts.name', 'ASC');
        }
        return $query->get(['accounts.*']);
    }

    /**
     * @param array $types
     *
     * @return Collection
     */
    public function getActiveAccountsByType(array $types): Collection
    {
        $query = $this->userGroup->accounts();
        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }
        $query->where('active', true);
        $query->orderBy('accounts.account_type_id', 'ASC');
        $query->orderBy('accounts.order', 'ASC');
        $query->orderBy('accounts.name', 'ASC');

        return $query->get(['accounts.*']);
    }

    /**
     * @inheritDoc
     */
    public function searchAccount(string $query, array $types, int $limit): Collection
    {
        // search by group, not by user
        $dbQuery = $this->userGroup->accounts()
                                   ->where('active', true)
                                   ->orderBy('accounts.order', 'ASC')
                                   ->orderBy('accounts.account_type_id', 'ASC')
                                   ->orderBy('accounts.name', 'ASC')
                                   ->with(['accountType']);
        if ('' !== $query) {
            // split query on spaces just in case:
            $parts = explode(' ', $query);
            foreach ($parts as $part) {
                $search = sprintf('%%%s%%', $part);
                $dbQuery->where('name', 'LIKE', $search);
            }
        }
        if (0 !== count($types)) {
            $dbQuery->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $dbQuery->whereIn('account_types.type', $types);
        }

        return $dbQuery->take($limit)->get(['accounts.*']);
    }
}

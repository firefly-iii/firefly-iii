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

namespace FireflyIII\Repositories\UserGroups\Account;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;

/**
 * Class AccountRepository
 */
class AccountRepository implements AccountRepositoryInterface
{
    use UserGroupTrait;

    public function findByAccountNumber(string $number, array $types): ?Account
    {
        $dbQuery = $this->userGroup
            ->accounts()
            ->leftJoin('account_meta', 'accounts.id', '=', 'account_meta.account_id')
            ->where('accounts.active', true)
            ->where(
                static function (EloquentBuilder $q1) use ($number): void { // @phpstan-ignore-line
                    $json = json_encode($number);
                    $q1->where('account_meta.name', '=', 'account_number');
                    $q1->where('account_meta.data', '=', $json);
                }
            )
        ;

        if (0 !== count($types)) {
            $dbQuery->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $dbQuery->whereIn('account_types.type', $types);
        }

        // @var Account|null
        return $dbQuery->first(['accounts.*']);
    }

    public function findByIbanNull(string $iban, array $types): ?Account
    {
        $query = $this->userGroup->accounts()->where('iban', '!=', '')->whereNotNull('iban');

        if (0 !== count($types)) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $query->whereIn('account_types.type', $types);
        }

        // @var Account|null
        return $query->where('iban', $iban)->first(['accounts.*']);
    }

    public function findByName(string $name, array $types): ?Account
    {
        $query = $this->userGroup->accounts();

        if (0 !== count($types)) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $query->whereIn('account_types.type', $types);
        }
        app('log')->debug(sprintf('Searching for account named "%s" (of user #%d) of the following type(s)', $name, $this->user->id), ['types' => $types]);

        $query->where('accounts.name', $name);

        /** @var null|Account $account */
        $account = $query->first(['accounts.*']);
        if (null === $account) {
            app('log')->debug(sprintf('There is no account with name "%s" of types', $name), $types);

            return null;
        }
        app('log')->debug(sprintf('Found #%d (%s) with type id %d', $account->id, $account->name, $account->account_type_id));

        return $account;
    }

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
     */
    public function getMetaValue(Account $account, string $field): ?string
    {
        $result = $account->accountMeta->filter(
            static function (AccountMeta $meta) use ($field) {
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

    public function find(int $accountId): ?Account
    {
        $account = $this->user->accounts()->find($accountId);
        if (null === $account) {
            $account = $this->userGroup->accounts()->find($accountId);
        }

        return $account;
    }

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

    public function searchAccount(string $query, array $types, int $limit): Collection
    {
        // search by group, not by user
        $dbQuery = $this->userGroup->accounts()
            ->where('active', true)
            ->orderBy('accounts.order', 'ASC')
            ->orderBy('accounts.account_type_id', 'ASC')
            ->orderBy('accounts.name', 'ASC')
            ->with(['accountType'])
        ;
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

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
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Services\Internal\Update\AccountUpdateService;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class AccountRepository
 */
class AccountRepository implements AccountRepositoryInterface
{
    use UserGroupTrait;

    #[\Override]
    public function countAccounts(array $types): int
    {
        $query = $this->userGroup->accounts();
        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }

        return $query->count();
    }

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
        $iban  = Steam::filterSpaces($iban);
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
        $query   = $this->userGroup->accounts();

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
        $type       = $account->accountType->type;
        $list       = config('firefly.valid_currency_account_types');

        // return null if not in this list.
        if (!in_array($type, $list, true)) {
            return null;
        }
        $currencyId = (int) $this->getMetaValue($account, 'currency_id');
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
            return (string) $result->first()->data;
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

    #[\Override]
    public function getAccountsInOrder(array $types, array $sort, int $startRow, int $endRow): Collection
    {
        $query = $this->userGroup->accounts();
        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }
        $query->skip($startRow);
        $query->take($endRow - $startRow);

        // add sort parameters. At this point they're filtered to allowed fields to sort by:
        if (0 !== count($sort)) {
            foreach ($sort as $label => $direction) {
                $query->orderBy(sprintf('accounts.%s', $label), $direction);
            }
        }

        if (0 === count($sort)) {
            $query->orderBy('accounts.order', 'ASC');
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

    public function resetAccountOrder(): void
    {
        $sets = [
            [AccountType::DEFAULT, AccountType::ASSET],
            [AccountType::LOAN, AccountType::DEBT, AccountType::CREDITCARD, AccountType::MORTGAGE],
        ];
        foreach ($sets as $set) {
            $list  = $this->getAccountsByType($set);
            $index = 1;
            foreach ($list as $account) {
                if (false === $account->active) {
                    $account->order = 0;

                    continue;
                }
                if ($index !== (int) $account->order) {
                    app('log')->debug(sprintf('Account #%d ("%s"): order should %d be but is %d.', $account->id, $account->name, $index, $account->order));
                    $account->order = $index;
                    $account->save();
                }
                ++$index;
            }
        }
        // reset the rest to zero.
        $all  = [AccountType::DEFAULT, AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::CREDITCARD, AccountType::MORTGAGE];
        $this->user->accounts()->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
            ->whereNotIn('account_types.type', $all)
            ->update(['order' => 0])
        ;
    }

    public function getAccountsByType(array $types, ?array $sort = [], ?array $filters = []): Collection
    {
        $sortable        = ['name', 'active']; // TODO yes this is a duplicate array.
        $res             = array_intersect([AccountType::ASSET, AccountType::MORTGAGE, AccountType::LOAN, AccountType::DEBT], $types);
        $query           = $this->userGroup->accounts();
        if (0 !== count($types)) {
            $query->accountTypeIn($types);
        }

        // process filters
        // TODO this should be repeatable, it feels like a hack when you do it here.
        // TODO some fields cannot be filtered using the query, and a second filter must be applied on the collection.
        foreach ($filters as $column => $value) {
            // filter on NULL values
            if (null === $value) {
                continue;
            }
            if ('active' === $column) {
                $query->where('accounts.active', $value);
            }
            if ('name' === $column) {
                $query->where('accounts.name', 'LIKE', sprintf('%%%s%%', $value));
            }
        }

        // add sort parameters. At this point they're filtered to allowed fields to sort by:
        $hasActiveColumn = array_key_exists('active', $sort);
        if (count($sort) > 0) {
            if (false === $hasActiveColumn) {
                $query->orderBy('accounts.active', 'DESC');
            }
            foreach ($sort as $column => $direction) {
                if (in_array($column, $sortable, true)) {
                    $query->orderBy(sprintf('accounts.%s', $column), $direction);
                }
            }
        }

        if (0 === count($sort)) {
            if (0 !== count($res)) {
                $query->orderBy('accounts.active', 'DESC');
            }
            $query->orderBy('accounts.order', 'ASC');
            $query->orderBy('accounts.name', 'ASC');
        }

        return $query->get(['accounts.*']);
    }

    public function searchAccount(array $query, array $types, int $limit): Collection
    {
        // search by group, not by user
        $dbQuery = $this->userGroup->accounts()
            ->where('active', true)
            ->orderBy('accounts.order', 'ASC')
            ->orderBy('accounts.account_type_id', 'ASC')
            ->orderBy('accounts.name', 'ASC')
            ->with(['accountType'])
        ;
        if (count($query) > 0) {
            // split query on spaces just in case:
            $dbQuery->where(function (EloquentBuilder $q) use ($query): void {
                foreach ($query as $line) {
                    $parts = explode(' ', $line);
                    foreach ($parts as $part) {
                        $search = sprintf('%%%s%%', $part);
                        $q->orWhere('name', 'LIKE', $search);
                    }
                }
            });
        }
        if (0 !== count($types)) {
            $dbQuery->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $dbQuery->whereIn('account_types.type', $types);
        }

        return $dbQuery->take($limit)->get(['accounts.*']);
    }

    #[\Override]
    public function update(Account $account, array $data): Account
    {
        /** @var AccountUpdateService $service */
        $service = app(AccountUpdateService::class);

        return $service->update($account, $data);
    }

    #[\Override]
    public function getMetaValues(Collection $accounts, array $fields): Collection
    {
        $query = AccountMeta::whereIn('account_id', $accounts->pluck('id')->toArray());
        if (count($fields) > 0) {
            $query->whereIn('name', $fields);
        }

        return $query->get(['account_meta.id', 'account_meta.account_id', 'account_meta.name', 'account_meta.data']);
    }

    #[\Override]
    public function getAccountTypes(Collection $accounts): Collection
    {
        return AccountType::leftJoin('accounts', 'accounts.account_type_id', '=', 'account_types.id')
            ->whereIn('accounts.id', $accounts->pluck('id')->toArray())
            ->get(['accounts.id', 'account_types.type'])
        ;
    }

    #[\Override]
    public function getLastActivity(Collection $accounts): array
    {
        return Transaction::whereIn('account_id', $accounts->pluck('id')->toArray())
            ->leftJoin('transaction_journals', 'transaction_journals.id', 'transactions.transaction_journal_id')
            ->groupBy('transactions.account_id')
            ->get(['transactions.account_id', DB::raw('MAX(transaction_journals.date) as date_max')])->toArray() // @phpstan-ignore-line
        ;
    }

    #[\Override]
    public function getObjectGroups(Collection $accounts): array
    {
        $groupIds = [];
        $return   = [];
        $set      = DB::table('object_groupables')->where('object_groupable_type', Account::class)
            ->whereIn('object_groupable_id', $accounts->pluck('id')->toArray())->get()
        ;

        /** @var \stdClass $row */
        foreach ($set as $row) {
            $groupIds[] = $row->object_group_id;
        }
        $groupIds = array_unique($groupIds);
        $groups   = ObjectGroup::whereIn('id', $groupIds)->get();

        /** @var \stdClass $row */
        foreach ($set as $row) {
            if (!array_key_exists($row->object_groupable_id, $return)) {
                /** @var null|ObjectGroup $group */
                $group = $groups->firstWhere('id', '=', $row->object_group_id);
                if (null !== $group) {
                    $return[$row->object_groupable_id] = ['title' => $group->title, 'order' => $group->order, 'id' => $group->id];
                }
            }
        }

        return $return;
    }

    #[\Override]
    public function getAccountBalances(Account $account): Collection
    {
        return $account->accountBalances;
    }
}

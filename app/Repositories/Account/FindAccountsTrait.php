<?php
/**
 * FindAccountsTrait.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Account;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Log;

/**
 * @property User $user
 *
 * Trait FindAccountsTrait
 */
trait FindAccountsTrait
{
    /**
     * @param $accountId
     *
     * @return Account
     */
    public function find(int $accountId): Account
    {
        $account = $this->user->accounts()->find($accountId);
        if (null === $account) {
            return new Account;
        }

        return $account;
    }

    /**
     * @param string $number
     * @param array  $types
     *
     * @return Account
     */
    public function findByAccountNumber(string $number, array $types): Account
    {
        $query = $this->user->accounts()
                            ->leftJoin('account_meta', 'account_meta.account_id', '=', 'accounts.id')
                            ->where('account_meta.name', 'accountNumber')
                            ->where('account_meta.data', json_encode($number));

        if (count($types) > 0) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $query->whereIn('account_types.type', $types);
        }

        /** @var Collection $accounts */
        $accounts = $query->get(['accounts.*']);
        if ($accounts->count() > 0) {
            return $accounts->first();
        }

        return new Account;
    }

    /**
     * @param string $iban
     * @param array  $types
     *
     * @return Account
     */
    public function findByIban(string $iban, array $types): Account
    {
        $query = $this->user->accounts()->where('iban', '!=', '')->whereNotNull('iban');

        if (count($types) > 0) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $query->whereIn('account_types.type', $types);
        }

        $accounts = $query->get(['accounts.*']);
        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->iban === $iban) {
                return $account;
            }
        }

        return new Account;
    }

    /**
     * @param string $name
     * @param array  $types
     *
     * @return Account
     */
    public function findByName(string $name, array $types): Account
    {
        $query = $this->user->accounts();

        if (count($types) > 0) {
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id');
            $query->whereIn('account_types.type', $types);
        }
        Log::debug(sprintf('Searching for account named "%s" (of user #%d) of the following type(s)', $name, $this->user->id), ['types' => $types]);

        $accounts = $query->get(['accounts.*']);
        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->name === $name) {
                Log::debug(sprintf('Found #%d (%s) with type id %d', $account->id, $account->name, $account->account_type_id));

                return $account;
            }
        }
        Log::debug(sprintf('There is no account with name "%s" or types', $name), $types);

        return new Account;
    }

    /**
     * @param array $accountIds
     *
     * @return Collection
     */
    public function getAccountsById(array $accountIds): Collection
    {
        /** @var Collection $result */
        $query = $this->user->accounts();

        if (count($accountIds) > 0) {
            $query->whereIn('accounts.id', $accountIds);
        }

        $result = $query->get(['accounts.*']);
        $result = $result->sortBy(
            function (Account $account) {
                return strtolower($account->name);
            }
        );

        return $result;
    }

    /**
     * @param array $types
     *
     * @return Collection
     */
    public function getAccountsByType(array $types): Collection
    {
        /** @var Collection $result */
        $query = $this->user->accounts();
        if (count($types) > 0) {
            $query->accountTypeIn($types);
        }

        $result = $query->get(['accounts.*']);
        $result = $result->sortBy(
            function (Account $account) {
                return strtolower($account->name);
            }
        );

        return $result;
    }

    /**
     * @param array $types
     *
     * @return Collection
     */
    public function getActiveAccountsByType(array $types): Collection
    {
        /** @var Collection $result */
        $query = $this->user->accounts()->with(
            ['accountmeta' => function (HasMany $query) {
                $query->where('name', 'accountRole');
            }]
        );
        if (count($types) > 0) {
            $query->accountTypeIn($types);
        }
        $query->where('active', 1);
        $result = $query->get(['accounts.*']);
        $result = $result->sortBy(
            function (Account $account) {
                return strtolower($account->name);
            }
        );

        return $result;
    }

    /**
     * @return Account
     */
    public function getCashAccount(): Account
    {
        $type            = AccountType::where('type', AccountType::CASH)->first();
        $account         = Account::firstOrCreateEncrypted(
            ['user_id' => $this->user->id, 'account_type_id' => $type->id, 'name' => 'Cash account']
        );
        $account->active = true;
        $account->save();

        return $account;
    }

    /**
     * @param Account $account
     *
     * @return Account|null
     * @throws FireflyException
     */
    public function getReconciliation(Account $account): ?Account
    {
        if ($account->accountType->type !== AccountType::ASSET) {
            throw new FireflyException(sprintf('%s is not an asset account.', $account->name));
        }
        $name     = $account->name . ' reconciliation';
        $type     = AccountType::where('type', AccountType::RECONCILIATION)->first();
        $accounts = $this->user->accounts()->where('account_type_id', $type->id)->get();
        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->name === $name) {
                return $account;
            }
        }
        // assume nothing was found. create it!
        $data    = [
            'accountType'    => 'reconcile',
            'name'           => $name,
            'iban'           => null,
            'virtualBalance' => null,
            'active'         => true,
        ];
        $account = $this->storeAccount($data);

        return $account;
    }
}

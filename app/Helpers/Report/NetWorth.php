<?php

/**
 * NetWorth.php
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

namespace FireflyIII\Helpers\Report;

use Deprecated;
use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * This class can handle both request with and without a user group and will return the appropriate repository when
 * necessary.
 *
 * Class NetWorth
 */
class NetWorth implements NetWorthInterface
{
    private AccountRepositoryInterface  $accountRepository;
    private CurrencyRepositoryInterface $currencyRepos;
    private User                        $user;             // @phpstan-ignore-line
    private ?UserGroup                  $userGroup = null; // @phpstan-ignore-line

    /**
     * This method collects the user's net worth in ALL the user's currencies
     * (1, 4 and 8) and also in the 'primary' currency for ease of use.
     *
     * The set of accounts has to be fed to it.
     *
     * @throws FireflyException
     */
    public function byAccounts(Collection $accounts, Carbon $date): array
    {
        // start in the past, end in the future? use $date
        $convertToPrimary = Amount::convertToPrimary();
        $ids              = implode(',', $accounts->pluck('id')->toArray());
        $cache            = new CacheProperties();
        $cache->addProperty($date);
        $cache->addProperty($convertToPrimary);
        $cache->addProperty('net-worth-by-accounts');
        $cache->addProperty($ids);
        if ($cache->has()) {
            return $cache->get();
        }
        Log::debug(sprintf('Now in byAccounts("%s", "%s")', $ids, $date->format('Y-m-d H:i:s')));
        $primary          = Amount::getPrimaryCurrency();
        $netWorth         = [];
        Log::debug(sprintf('NetWorth: accountsBalancesOptimized("%s")', $date->format('Y-m-d H:i:s')));
        $balances         = Steam::accountsBalancesOptimized($accounts, $date, null, $convertToPrimary);

        /** @var Account $account */
        foreach ($accounts as $account) {
            //            Log::debug(sprintf('Now at account #%d ("%s")', $account->id, $account->name));
            $currency                           = $this->accountRepository->getAccountCurrency($account) ?? $primary;
            $usePrimary                         = $convertToPrimary && $primary->id !== $currency->id;
            $currency                           = $usePrimary ? $primary : $currency;
            $currencyCode                       = $currency->code;
            $balance                            = '0';
            $primaryBalance                     = '0';
            if (array_key_exists($account->id, $balances)) {
                $balance        = $balances[$account->id]['balance'] ?? '0';
                $primaryBalance = $balances[$account->id]['pc_balance'] ?? '0';
            }
            //            Log::debug(sprintf('Balance is %s, primary balance is %s', $balance, $primaryBalance));
            // always subtract virtual balance again.
            $balance                            = '' !== (string) $account->virtual_balance ? bcsub($balance, (string) $account->virtual_balance) : $balance;
            $primaryBalance                     = '' !== (string) $account->native_virtual_balance ? bcsub($primaryBalance, (string) $account->native_virtual_balance) : $primaryBalance;
            $amountToUse                        = $usePrimary ? $primaryBalance : $balance;
            //            Log::debug(sprintf('Will use %s %s', $currencyCode, $amountToUse));

            $netWorth[$currencyCode] ??= [
                'balance'                 => '0',
                'currency_id'             => (string) $currency->id,
                'currency_code'           => $currency->code,
                'currency_name'           => $currency->name,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
            ];

            $netWorth[$currencyCode]['balance'] = bcadd((string) $amountToUse, $netWorth[$currencyCode]['balance']);
        }
        $cache->store($netWorth);

        return $netWorth;
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if (!$user instanceof User) {
            return;
        }
        $this->user = $user;
        $this->setUserGroup($user->userGroup);
    }

    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup         = $userGroup;
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $this->accountRepository->setUserGroup($userGroup);

        $this->currencyRepos     = app(CurrencyRepositoryInterface::class);
        $this->currencyRepos->setUserGroup($this->userGroup);
    }

    #[Deprecated]
    public function sumNetWorthByCurrency(Carbon $date): array
    {
        /**
         * Collect accounts
         */
        $accounts = $this->getAccounts();
        $return   = [];
        Log::debug(sprintf('SumNetWorth: accountsBalancesOptimized("%s")', $date->format('Y-m-d H:i:s')));
        $balances = Steam::accountsBalancesOptimized($accounts, $date);
        foreach ($accounts as $account) {
            $currency                     = $this->accountRepository->getAccountCurrency($account);
            $balance                      = $balances[$account->id]['balance'] ?? '0';

            // always subtract virtual balance.
            $virtualBalance               = $account->virtual_balance;
            if ('' !== $virtualBalance) {
                $balance = bcsub($balance, (string) $virtualBalance);
            }

            $return[$currency->id] ??= [
                'id'             => (string) $currency->id,
                'name'           => $currency->name,
                'symbol'         => $currency->symbol,
                'code'           => $currency->code,
                'decimal_places' => $currency->decimal_places,
                'sum'            => '0',
            ];
            $return[$currency->id]['sum'] = bcadd($return[$currency->id]['sum'], (string) $balance);
        }

        return $return;
    }

    private function getAccounts(): Collection
    {
        $accounts = $this->accountRepository->getAccountsByType(
            [AccountTypeEnum::ASSET->value, AccountTypeEnum::DEFAULT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value]
        );
        $filtered = new Collection();

        /** @var Account $account */
        foreach ($accounts as $account) {
            if (1 === (int) $this->accountRepository->getMetaValue($account, 'include_net_worth')) {
                $filtered->push($account);
            }
        }

        return $filtered;
    }
}

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

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface as AdminAccountRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
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
    private AccountRepositoryInterface      $accountRepository;
    private AdminAccountRepositoryInterface $adminAccountRepository;

    private CurrencyRepositoryInterface $currencyRepos;
    private User                        $user;
    private ?UserGroup                  $userGroup;

    /**
     * This method collects the user's net worth in ALL the user's currencies
     * (1, 4 and 8) and also in the 'native' currency for ease of use.
     *
     * The set of accounts has to be fed to it.
     *
     * @throws FireflyException
     */
    public function byAccounts(Collection $accounts, Carbon $date): array
    {
        // start in the past, end in the future? use $date
        $ids       = implode(',', $accounts->pluck('id')->toArray());
        $cache     = new CacheProperties();
        $cache->addProperty($date);
        $cache->addProperty('net-worth-by-accounts');
        $cache->addProperty($ids);
        if ($cache->has()) {
            return $cache->get();
        }
        app('log')->debug(sprintf('Now in byAccounts("%s", "%s")', $ids, $date->format('Y-m-d')));
        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        $default   = app('amount')->getDefaultCurrency();
        $converter = new ExchangeRateConverter();

        // default "native" currency has everything twice, for consistency.
        $netWorth  = [
            'native' => [
                'balance'                        => '0',
                'native_balance'                 => '0',
                'currency_id'                    => $default->id,
                'currency_code'                  => $default->code,
                'currency_name'                  => $default->name,
                'currency_symbol'                => $default->symbol,
                'currency_decimal_places'        => $default->decimal_places,
                'native_currency_id'             => $default->id,
                'native_currency_code'           => $default->code,
                'native_currency_name'           => $default->name,
                'native_currency_symbol'         => $default->symbol,
                'native_currency_decimal_places' => $default->decimal_places,
            ],
        ];
        $balances  = app('steam')->balancesByAccountsConverted($accounts, $date);

        /** @var Account $account */
        foreach ($accounts as $account) {
            app('log')->debug(sprintf('Now at account #%d ("%s")', $account->id, $account->name));
            $currency                                  = $this->getRepository()->getAccountCurrency($account);
            if (null === $currency) {
                $currency = app('amount')->getDefaultCurrency();
            }
            $currencyCode                              = $currency->code;
            $balance                                   = '0';
            $nativeBalance                             = '0';
            if (array_key_exists($account->id, $balances)) {
                $balance       = $balances[$account->id]['balance'] ?? '0';
                $nativeBalance = $balances[$account->id]['native_balance'] ?? '0';
            }
            app('log')->debug(sprintf('Balance is %s, native balance is %s', $balance, $nativeBalance));
            // always subtract virtual balance
            $virtualBalance                            = $account->virtual_balance;
            if ('' !== $virtualBalance) {
                $balance              = bcsub($balance, $virtualBalance);
                $nativeVirtualBalance = $converter->convert($default, $currency, $account->created_at, $virtualBalance);
                $nativeBalance        = bcsub($nativeBalance, $nativeVirtualBalance);
            }
            $netWorth[$currencyCode] ??= [
                'balance'                        => '0',
                'native_balance'                 => '0',
                'currency_id'                    => (string) $currency->id,
                'currency_code'                  => $currency->code,
                'currency_name'                  => $currency->name,
                'currency_symbol'                => $currency->symbol,
                'currency_decimal_places'        => $currency->decimal_places,
                'native_currency_id'             => (string) $default->id,
                'native_currency_code'           => $default->code,
                'native_currency_name'           => $default->name,
                'native_currency_symbol'         => $default->symbol,
                'native_currency_decimal_places' => $default->decimal_places,
            ];

            $netWorth[$currencyCode]['balance']        = bcadd($balance, $netWorth[$currencyCode]['balance']);
            $netWorth[$currencyCode]['native_balance'] = bcadd($nativeBalance, $netWorth[$currencyCode]['native_balance']);
            $netWorth['native']['balance']             = bcadd($nativeBalance, $netWorth['native']['balance']);
            $netWorth['native']['native_balance']      = bcadd($nativeBalance, $netWorth['native']['native_balance']);
        }
        $cache->store($netWorth);
        $converter->summarize();

        return $netWorth;
    }

    private function getRepository(): AccountRepositoryInterface|AdminAccountRepositoryInterface
    {
        if (null === $this->userGroup) {
            return $this->accountRepository;
        }

        return $this->adminAccountRepository;
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if (!$user instanceof User) {
            return;
        }
        $this->user              = $user;
        $this->userGroup         = null;

        // make repository:
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $this->accountRepository->setUser($this->user);

        $this->currencyRepos     = app(CurrencyRepositoryInterface::class);
        $this->currencyRepos->setUser($this->user);
    }

    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup              = $userGroup;
        $this->adminAccountRepository = app(AdminAccountRepositoryInterface::class);
        $this->adminAccountRepository->setUserGroup($userGroup);
    }

    /**
     * @deprecated
     */
    public function sumNetWorthByCurrency(Carbon $date): array
    {
        /**
         * Collect accounts
         */
        $accounts = $this->getAccounts();
        $return   = [];
        $balances = app('steam')->balancesByAccounts($accounts, $date);
        foreach ($accounts as $account) {
            $currency                     = $this->getRepository()->getAccountCurrency($account);
            $balance                      = $balances[$account->id] ?? '0';

            // always subtract virtual balance.
            $virtualBalance               = $account->virtual_balance;
            if ('' !== $virtualBalance) {
                $balance = bcsub($balance, $virtualBalance);
            }

            $return[$currency->id] ??= [
                'id'             => (string) $currency->id,
                'name'           => $currency->name,
                'symbol'         => $currency->symbol,
                'code'           => $currency->code,
                'decimal_places' => $currency->decimal_places,
                'sum'            => '0',
            ];
            $return[$currency->id]['sum'] = bcadd($return[$currency->id]['sum'], $balance);
        }

        return $return;
    }

    private function getAccounts(): Collection
    {
        $accounts = $this->getRepository()->getAccountsByType(
            [AccountType::ASSET, AccountType::DEFAULT, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]
        );
        $filtered = new Collection();

        /** @var Account $account */
        foreach ($accounts as $account) {
            if (1 === (int) $this->getRepository()->getMetaValue($account, 'include_net_worth')) {
                $filtered->push($account);
            }
        }

        return $filtered;
    }
}

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
use FireflyIII\Repositories\Administration\Account\AccountRepositoryInterface as AdminAccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use JsonException;

/**
 *
 * Class NetWorth
 */
class NetWorth implements NetWorthInterface
{
    private AccountRepositoryInterface      $accountRepository;
    private AdminAccountRepositoryInterface $adminAccountRepository;

    private CurrencyRepositoryInterface $currencyRepos;
    private User                        $user;
    private UserGroup                   $userGroup;

    /**
     * @param Collection $accounts
     * @param Carbon     $date
     *
     * @return array
     * @throws FireflyException
     */
    public function byAccounts(Collection $accounts, Carbon $date): array
    {
        // start in the past, end in the future? use $date
        $ids   = implode(',', $accounts->pluck('id')->toArray());
        $cache = new CacheProperties();
        $cache->addProperty($date);
        $cache->addProperty('net-worth-by-accounts');
        $cache->addProperty($ids);
        if ($cache->has()) {
            //return $cache->get();
        }
        app('log')->debug(sprintf('Now in byAccounts("%s", "%s")', $ids, $date->format('Y-m-d')));

        $default   = app('amount')->getDefaultCurrency();
        $converter = new ExchangeRateConverter();

        // default "native" currency has everything twice, for consistency.
        $netWorth = [
            'native' => [
                'balance'                 => '0',
                'native_balance'          => '0',
                'currency_id'             => (int)$default->id,
                'currency_code'           => $default->code,
                'currency_name'           => $default->name,
                'currency_symbol'         => $default->symbol,
                'currency_decimal_places' => (int)$default->decimal_places,
                'native_id'               => (int)$default->id,
                'native_code'             => $default->code,
                'native_name'             => $default->name,
                'native_symbol'           => $default->symbol,
                'native_decimal_places'   => (int)$default->decimal_places,
            ],
        ];
        $balances = app('steam')->balancesByAccountsConverted($accounts, $date);

        /** @var Account $account */
        foreach ($accounts as $account) {
            app('log')->debug(sprintf('Now at account #%d ("%s")', $account->id, $account->name));
            $currency      = $this->adminAccountRepository->getAccountCurrency($account);
            $currencyId    = (int)$currency->id;
            $balance       = '0';
            $nativeBalance = '0';
            if (array_key_exists((int)$account->id, $balances)) {
                $balance       = $balances[(int)$account->id]['balance'] ?? '0';
                $nativeBalance = $balances[(int)$account->id]['native_balance'] ?? '0';
            }
            app('log')->debug(sprintf('Balance is %s, native balance is %s', $balance, $nativeBalance));
            // always subtract virtual balance
            $virtualBalance = (string)$account->virtual_balance;
            if ('' !== $virtualBalance) {
                $balance              = bcsub($balance, $virtualBalance);
                $nativeVirtualBalance = $converter->convert($default, $currency, $account->created_at, $virtualBalance);
                $nativeBalance        = bcsub($nativeBalance, $nativeVirtualBalance);
            }
            $netWorth[$currencyId] = $netWorth[$currencyId] ?? [
                'balance'                 => '0',
                'native_balance'          => '0',
                'currency_id'             => $currencyId,
                'currency_code'           => $currency->code,
                'currency_name'           => $currency->name,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => (int)$currency->decimal_places,
                'native_id'               => (int)$default->id,
                'native_code'             => $default->code,
                'native_name'             => $default->name,
                'native_symbol'           => $default->symbol,
                'native_decimal_places'   => (int)$default->decimal_places,
            ];

            $netWorth[$currencyId]['balance']        = bcadd($balance, $netWorth[$currencyId]['balance']);
            $netWorth[$currencyId]['native_balance'] = bcadd($nativeBalance, $netWorth[$currencyId]['native_balance']);
            $netWorth['native']['balance']           = bcadd($nativeBalance, $netWorth['native']['balance']);
            $netWorth['native']['native_balance']    = bcadd($nativeBalance, $netWorth['native']['native_balance']);
        }
        $cache->store($netWorth);

        return $netWorth;
    }

    /**
     * Returns the user's net worth in an array with the following layout:
     *
     * -
     *  - currency: TransactionCurrency object
     *  - date: the current date
     *  - amount: the user's net worth in that currency.
     *
     * This repeats for each currency the user has transactions in.
     * Result of this method is cached.
     *
     * @param Collection $accounts
     * @param Carbon     $date
     *
     * @return array
     * @throws JsonException
     * @throws FireflyException
     * @deprecated
     */
    public function getNetWorthByCurrency(Collection $accounts, Carbon $date): array
    {
        // start in the past, end in the future? use $date
        $cache = new CacheProperties();
        $cache->addProperty($date);
        $cache->addProperty('net-worth-by-currency');
        $cache->addProperty(implode(',', $accounts->pluck('id')->toArray()));
        if ($cache->has()) {
            return $cache->get();
        }

        $netWorth = [];
        $result   = [];
        //        Log::debug(sprintf('Now in getNetWorthByCurrency(%s)', $date->format('Y-m-d')));

        // get default currency
        $default = app('amount')->getDefaultCurrencyByUser($this->user);

        // get all balances:
        $balances = app('steam')->balancesByAccounts($accounts, $date);

        // get the preferred currency for this account
        /** @var Account $account */
        foreach ($accounts as $account) {
            //            Log::debug(sprintf('Now at account #%d: "%s"', $account->id, $account->name));
            $currencyId = (int)$this->accountRepository->getMetaValue($account, 'currency_id');
            $currencyId = 0 === $currencyId ? $default->id : $currencyId;

            //            Log::debug(sprintf('Currency ID is #%d', $currencyId));

            // balance in array:
            $balance = $balances[$account->id] ?? '0';

            //Log::debug(sprintf('Balance for %s is %s', $date->format('Y-m-d'), $balance));

            // always subtract virtual balance.
            $virtualBalance = (string)$account->virtual_balance;
            if ('' !== $virtualBalance) {
                $balance = bcsub($balance, $virtualBalance);
            }

            //            Log::debug(sprintf('Balance corrected to %s because of virtual balance (%s)', $balance, $virtualBalance));

            if (!array_key_exists($currencyId, $netWorth)) {
                $netWorth[$currencyId] = '0';
            }
            $netWorth[$currencyId] = bcadd($balance, $netWorth[$currencyId]);
            //            Log::debug(sprintf('Total net worth for currency #%d is %s', $currencyId, $netWorth[$currencyId]));
        }
        ksort($netWorth);

        // loop results and add currency information:
        foreach ($netWorth as $currencyId => $balance) {
            $result[] = [
                'currency' => $this->currencyRepos->find($currencyId),
                'balance'  => $balance,
            ];
        }
        $cache->store($result);

        return $result;
    }

    /**
     * @param User|Authenticatable|null $user
     */
    public function setUser(User | Authenticatable | null $user): void
    {
        if (null === $user) {
            return;
        }
        $this->user = $user;

        // make repository:
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $this->accountRepository->setUser($this->user);

        $this->currencyRepos = app(CurrencyRepositoryInterface::class);
        $this->currencyRepos->setUser($this->user);
    }

    /**
     * @inheritDoc
     * @throws FireflyException
     */
    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup              = $userGroup;
        $this->adminAccountRepository = app(AdminAccountRepositoryInterface::class);
        $this->adminAccountRepository->setAdministrationId($userGroup->id);
    }

    /**
     * @inheritDoc
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
            $currency = $this->accountRepository->getAccountCurrency($account);
            $balance  = $balances[$account->id] ?? '0';

            // always subtract virtual balance.
            $virtualBalance = (string)$account->virtual_balance;
            if ('' !== $virtualBalance) {
                $balance = bcsub($balance, $virtualBalance);
            }

            $return[$currency->id]        = $return[$currency->id] ?? [
                'id'             => (string)$currency->id,
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

    /**
     * @return Collection
     */
    private function getAccounts(): Collection
    {
        $accounts = $this->accountRepository->getAccountsByType(
            [AccountType::ASSET, AccountType::DEFAULT, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]
        );
        $filtered = new Collection();
        /** @var Account $account */
        foreach ($accounts as $account) {
            if (1 === (int)$this->accountRepository->getMetaValue($account, 'include_net_worth')) {
                $filtered->push($account);
            }
        }
        return $filtered;
    }
}

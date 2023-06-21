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
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
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
    private AccountRepositoryInterface $accountRepository;

    private CurrencyRepositoryInterface $currencyRepos;
    private User                        $user;

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

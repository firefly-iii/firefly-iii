<?php

/**
 * BoxController.php
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

namespace FireflyIII\Http\Controllers\Json;

use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;

/**
 * Class BoxController.
 */
class BoxController extends Controller
{
    use DateCalculation;

    /**
     * Deprecated method, no longer in use.
     *
     * @deprecated
     */
    public function available(): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * Current total balance.
     */
    public function balance(CurrencyRepositoryInterface $repository): JsonResponse
    {
        // Cache result, return cache if present.
        /** @var Carbon $start */
        $start     = session('start', today(config('app.timezone'))->startOfMonth());

        /** @var Carbon $end */
        $end       = session('end', today(config('app.timezone'))->endOfMonth());
        $cache     = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($this->convertToNative);
        $cache->addProperty('box-balance');
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // prep some arrays:
        $incomes   = [];
        $expenses  = [];
        $sums      = [];
        $currency  = $this->defaultCurrency;

        // collect income of user:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)
            ->setTypes([TransactionTypeEnum::DEPOSIT->value])
        ;
        $set       = $collector->getExtractedJournals();

        /** @var array $journal */
        foreach ($set as $journal) {
            $currencyId           = $this->convertToNative && $this->defaultCurrency->id !== (int) $journal['currency_id'] ? $this->defaultCurrency->id : (int) $journal['currency_id'];
            $amount               = Amount::getAmountFromJournal($journal);
            $incomes[$currencyId] ??= '0';
            $incomes[$currencyId] = bcadd($incomes[$currencyId], app('steam')->positive($amount));
            $sums[$currencyId]    ??= '0';
            $sums[$currencyId]    = bcadd($sums[$currencyId], app('steam')->positive($amount));
        }

        // collect expenses
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)
            ->setTypes([TransactionTypeEnum::WITHDRAWAL->value])
        ;
        $set       = $collector->getExtractedJournals();

        /** @var array $journal */
        foreach ($set as $journal) {
            $currencyId            = $this->convertToNative ? $this->defaultCurrency->id : (int) $journal['currency_id'];
            $amount                = Amount::getAmountFromJournal($journal);
            $expenses[$currencyId] ??= '0';
            $expenses[$currencyId] = bcadd($expenses[$currencyId], $amount);
            $sums[$currencyId]     ??= '0';
            $sums[$currencyId]     = bcadd($sums[$currencyId], $amount);
        }

        // format amounts:
        $keys      = array_keys($sums);
        foreach ($keys as $currencyId) {
            $currency              = $repository->find($currencyId);
            $sums[$currencyId]     = app('amount')->formatAnything($currency, $sums[$currencyId], false);
            $incomes[$currencyId]  = app('amount')->formatAnything($currency, $incomes[$currencyId] ?? '0', false);
            $expenses[$currencyId] = app('amount')->formatAnything($currency, $expenses[$currencyId] ?? '0', false);
        }
        if (0 === count($sums)) {
            $currency                             = $this->defaultCurrency;
            $sums[$this->defaultCurrency->id]     = app('amount')->formatAnything($this->defaultCurrency, '0', false);
            $incomes[$this->defaultCurrency->id]  = app('amount')->formatAnything($this->defaultCurrency, '0', false);
            $expenses[$this->defaultCurrency->id] = app('amount')->formatAnything($this->defaultCurrency, '0', false);
        }

        $response  = [
            'incomes'   => $incomes,
            'expenses'  => $expenses,
            'sums'      => $sums,
            'size'      => count($sums),
            'preferred' => $currency->id,
        ];
        $cache->store($response);

        return response()->json($response);
    }

    /**
     * Total user net worth.
     */
    public function netWorth(): JsonResponse
    {
        $date              = today(config('app.timezone'))->endOfDay();

        // start and end in the future? use $end
        if ($this->notInSessionRange($date)) {
            /** @var Carbon $date */
            $date = session('end', today(config('app.timezone'))->endOfMonth());
        }

        /** @var NetWorthInterface $netWorthHelper */
        $netWorthHelper    = app(NetWorthInterface::class);
        $netWorthHelper->setUser(auth()->user());

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $allAccounts       = $accountRepository->getActiveAccountsByType(
            [AccountTypeEnum::DEFAULT->value, AccountTypeEnum::ASSET->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value]
        );
        app('log')->debug(sprintf('Found %d accounts.', $allAccounts->count()));

        // filter list on preference of being included.
        $filtered          = $allAccounts->filter(
            static function (Account $account) use ($accountRepository) {
                $includeNetWorth = $accountRepository->getMetaValue($account, 'include_net_worth');
                $result          = null === $includeNetWorth ? true : '1' === $includeNetWorth;
                if (false === $result) {
                    app('log')->debug(sprintf('Will not include "%s" in net worth charts.', $account->name));
                }

                return $result;
            }
        );

        $netWorthSet       = $netWorthHelper->byAccounts($filtered, $date);
        $return            = [];
        foreach ($netWorthSet as $key => $data) {
            if ('native' === $key) {
                continue;
            }
            $return[$data['currency_id']] = app('amount')->formatFlat($data['currency_symbol'], $data['currency_decimal_places'], $data['balance'], false);
        }
        $return            = [
            'net_worths' => array_values($return),
        ];

        return response()->json($return);
    }
}

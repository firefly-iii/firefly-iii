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
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;

/**
 * Class BoxController.
 */
class BoxController extends Controller
{
    use DateCalculation;

    /**
     * This box has three types of info to display:
     * 0) If the user has available amount this period and has overspent: overspent box.
     * 1) If the user has available amount this period and has NOT overspent: left to spend box.
     * 2) if the user has no available amount set this period: spent per day
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function available(): JsonResponse
    {
        app('log')->debug('Now in available()');

        /** @var OperationsRepositoryInterface $opsRepository */
        $opsRepository     = app(OperationsRepositoryInterface::class);

        /** @var AvailableBudgetRepositoryInterface $abRepository */
        $abRepository      = app(AvailableBudgetRepositoryInterface::class);
        $abRepository->cleanup();

        /** @var Carbon $start */
        $start             = session('start', today(config('app.timezone'))->startOfMonth());

        /** @var Carbon $end */
        $end               = session('end', today(config('app.timezone'))->endOfMonth());
        $today             = today(config('app.timezone'));
        $display           = 2; // see method docs.
        $boxTitle          = (string) trans('firefly.spent');

        $cache             = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($today);
        $cache->addProperty('box-available');
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $leftPerDayAmount  = '0';
        $leftToSpendAmount = '0';

        $currency          = app('amount')->getDefaultCurrency();
        app('log')->debug(sprintf('Default currency is %s', $currency->code));
        $availableBudgets  = $abRepository->getAvailableBudgetsByExactDate($start, $end);
        app('log')->debug(sprintf('Found %d available budget(s)', $availableBudgets->count()));
        $availableBudgets  = $availableBudgets->filter(
            static function (AvailableBudget $availableBudget) use ($currency) { // @phpstan-ignore-line
                if ($availableBudget->transaction_currency_id === $currency->id) {
                    app('log')->debug(sprintf(
                        'Will include AB #%d: from %s-%s amount %s',
                        $availableBudget->id,
                        $availableBudget->start_date->format('Y-m-d'),
                        $availableBudget->end_date->format('Y-m-d'),
                        $availableBudget->amount
                    ));

                    return $availableBudget;
                }

                return null;
            }
        );
        app('log')->debug(sprintf('Filtered back to %d available budgets', $availableBudgets->count()));
        // spent in this period, in budgets, for default currency.
        // also calculate spent per day.
        $spent             = $opsRepository->sumExpenses($start, $end, null, null, $currency);
        $spentAmount       = $spent[$currency->id]['sum'] ?? '0';
        app('log')->debug(sprintf('Spent for default currency for all budgets in this period: %s', $spentAmount));

        $days              = (int) ($today->between($start, $end) ? $today->diffInDays($start, true) + 1 : $end->diffInDays($start, true) + 1);
        app('log')->debug(sprintf('Number of days left: %d', $days));
        $spentPerDay       = bcdiv($spentAmount, (string) $days);
        app('log')->debug(sprintf('Available to spend per day: %s', $spentPerDay));
        if ($availableBudgets->count() > 0) {
            $display           = 0; // assume user overspent
            $boxTitle          = (string) trans('firefly.overspent');
            $totalAvailableSum = (string) $availableBudgets->sum('amount');
            app('log')->debug(sprintf('Total available sum is %s', $totalAvailableSum));
            // calculate with available budget.
            $leftToSpendAmount = bcadd($totalAvailableSum, $spentAmount);
            app('log')->debug(sprintf('So left to spend is %s', $leftToSpendAmount));
            if (bccomp($leftToSpendAmount, '0') >= 0) {
                app('log')->debug('Left to spend is positive or zero!');
                $boxTitle         = (string) trans('firefly.left_to_spend');
                $activeDaysLeft   = $this->activeDaysLeft($start, $end);   // see method description.
                $display          = 1;                                     // not overspent
                $leftPerDayAmount = 0 === $activeDaysLeft ? $leftToSpendAmount : bcdiv($leftToSpendAmount, (string) $activeDaysLeft);
                app('log')->debug(sprintf('Left to spend per day is %s', $leftPerDayAmount));
            }
        }

        $return            = [
            'display'       => $display,
            'spent_total'   => app('amount')->formatAnything($currency, $spentAmount, false),
            'spent_per_day' => app('amount')->formatAnything($currency, $spentPerDay, false),
            'left_to_spend' => app('amount')->formatAnything($currency, $leftToSpendAmount, false),
            'left_per_day'  => app('amount')->formatAnything($currency, $leftPerDayAmount, false),
            'title'         => $boxTitle,
        ];
        app('log')->debug('Final output', $return);

        $cache->store($return);
        app('log')->debug('Now done with available()');

        return response()->json($return);
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
        $cache->addProperty('box-balance');
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // prep some arrays:
        $incomes   = [];
        $expenses  = [];
        $sums      = [];
        $currency  = app('amount')->getDefaultCurrency();

        // collect income of user:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)
            ->setTypes([TransactionType::DEPOSIT])
        ;
        $set       = $collector->getExtractedJournals();

        /** @var array $journal */
        foreach ($set as $journal) {
            $currencyId           = (int) $journal['currency_id'];
            $amount               = $journal['amount'] ?? '0';
            $incomes[$currencyId] ??= '0';
            $incomes[$currencyId] = bcadd($incomes[$currencyId], app('steam')->positive($amount));
            $sums[$currencyId]    ??= '0';
            $sums[$currencyId]    = bcadd($sums[$currencyId], app('steam')->positive($amount));
        }

        // collect expenses
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)
            ->setTypes([TransactionType::WITHDRAWAL])
        ;
        $set       = $collector->getExtractedJournals();

        /** @var array $journal */
        foreach ($set as $journal) {
            $currencyId            = (int) $journal['currency_id'];
            $expenses[$currencyId] ??= '0';
            $expenses[$currencyId] = bcadd($expenses[$currencyId], $journal['amount'] ?? '0');
            $sums[$currencyId]     ??= '0';
            $sums[$currencyId]     = bcadd($sums[$currencyId], $journal['amount']);
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
            $currency                = app('amount')->getDefaultCurrency();
            $sums[$currency->id]     = app('amount')->formatAnything($currency, '0', false);
            $incomes[$currency->id]  = app('amount')->formatAnything($currency, '0', false);
            $expenses[$currency->id] = app('amount')->formatAnything($currency, '0', false);
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
            [AccountType::DEFAULT, AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]
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

<?php

/**
 * AccountController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    /** @var CurrencyRepositoryInterface */
    private $currencyRepository;
    /** @var AccountRepositoryInterface */
    private $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUser($user);

                $this->currencyRepository = app(CurrencyRepositoryInterface::class);
                $this->currencyRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function expenseOverview(Request $request): JsonResponse
    {
        // parameters for chart:
        $start = (string)$request->get('start');
        $end   = (string)$request->get('end');
        if ('' === $start || '' === $end) {
            throw new FireflyException('Start and end are mandatory parameters.');
        }

        $start = Carbon::createFromFormat('Y-m-d', $start);
        $end   = Carbon::createFromFormat('Y-m-d', $end);
        $start->subDay();

        // prep some vars:
        $currencies = [];
        $chartData  = [];
        $tempData   = [];

        // grab all accounts and names
        $accounts      = $this->repository->getAccountsByType([AccountType::EXPENSE]);
        $accountNames  = $this->extractNames($accounts);
        $startBalances = app('steam')->balancesPerCurrencyByAccounts($accounts, $start);
        $endBalances   = app('steam')->balancesPerCurrencyByAccounts($accounts, $end);

        // loop the end balances. This is an array for each account ($expenses)
        foreach ($endBalances as $accountId => $expenses) {
            $accountId = (int)$accountId;
            // loop each expense entry (each entry can be a different currency).
            foreach ($expenses as $currencyId => $endAmount) {
                $currencyId = (int)$currencyId;

                // see if there is an accompanying start amount.
                // grab the difference and find the currency.
                $startAmount             = $startBalances[$accountId][$currencyId] ?? '0';
                $diff                    = bcsub($endAmount, $startAmount);
                $currencies[$currencyId] = $currencies[$currencyId] ?? $this->currencyRepository->findNull($currencyId);
                if (0 !== bccomp($diff, '0')) {
                    // store the values in a temporary array.
                    $tempData[] = [
                        'name'        => $accountNames[$accountId],
                        'difference'  => $diff,
                        'diff_float'  => (float)$diff,
                        'currency_id' => $currencyId,
                    ];
                }
            }
        }

        // sort temp array by amount.
        $amounts = array_column($tempData, 'diff_float');
        array_multisort($amounts, SORT_DESC, $tempData);

        // loop all found currencies and build the data array for the chart.
        /**
         * @var int                 $currencyId
         * @var TransactionCurrency $currency
         */
        foreach ($currencies as $currencyId => $currency) {
            $currentSet             = [
                'label'                   => trans('firefly.box_spent_in_currency', ['currency' => $currency->symbol]),
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'type'                    => 'bar', // line, area or bar
                'yAxisID'                 => 0, // 0, 1, 2
                'entries'                 => $this->expandNames($tempData),
            ];
            $chartData[$currencyId] = $currentSet;
        }

        // loop temp data and place data in correct array:
        foreach ($tempData as $entry) {
            $currencyId                               = $entry['currency_id'];
            $name                                     = $entry['name'];
            $chartData[$currencyId]['entries'][$name] = round($entry['difference'], $chartData[$currencyId]['currency_decimal_places']);
        }
        $chartData = array_values($chartData);

        return response()->json($chartData);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function overview(Request $request): JsonResponse
    {
        // parameters for chart:
        $start = (string)$request->get('start');
        $end   = (string)$request->get('end');
        if ('' === $start || '' === $end) {
            throw new FireflyException('Start and end are mandatory parameters.');
        }

        $start = Carbon::createFromFormat('Y-m-d', $start);
        $end   = Carbon::createFromFormat('Y-m-d', $end);

        // user's preferences
        $defaultSet = $this->repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET])->pluck('id')->toArray();
        $frontPage  = app('preferences')->get('frontPageAccounts', $defaultSet);
        $default    = app('amount')->getDefaultCurrency();
        if (0 === \count($frontPage->data)) {
            $frontPage->data = $defaultSet;
            $frontPage->save();
        }

        // get accounts:
        $accounts  = $this->repository->getAccountsById($frontPage->data);
        $chartData = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $currency = $this->repository->getAccountCurrency($account);
            if (null === $currency) {
                $currency = $default;
            }
            $currentSet = [
                'label'                   => $account->name,
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'type'                    => 'line', // line, area or bar
                'yAxisID'                 => 0, // 0, 1, 2
                'entries'                 => [],
            ];

            $currentStart = clone $start;
            $range        = app('steam')->balanceInRange($account, $start, clone $end);
            $previous     = round(array_values($range)[0], 12);
            while ($currentStart <= $end) {
                $format   = $currentStart->format('Y-m-d');
                $label    = $currentStart->format('Y-m-d');
                $balance  = isset($range[$format]) ? round($range[$format], 12) : $previous;
                $previous = $balance;
                $currentStart->addDay();
                $currentSet['entries'][$label] = $balance;
            }
            $chartData[] = $currentSet;
        }

        return response()->json($chartData);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function revenueOverview(Request $request): JsonResponse
    {
        // parameters for chart:
        $start = (string)$request->get('start');
        $end   = (string)$request->get('end');
        if ('' === $start || '' === $end) {
            throw new FireflyException('Start and end are mandatory parameters.');
        }

        $start = Carbon::createFromFormat('Y-m-d', $start);
        $end   = Carbon::createFromFormat('Y-m-d', $end);
        $start->subDay();

        // prep some vars:
        $currencies = [];
        $chartData  = [];
        $tempData   = [];

        // grab all accounts and names
        $accounts      = $this->repository->getAccountsByType([AccountType::REVENUE]);
        $accountNames  = $this->extractNames($accounts);
        $startBalances = app('steam')->balancesPerCurrencyByAccounts($accounts, $start);
        $endBalances   = app('steam')->balancesPerCurrencyByAccounts($accounts, $end);

        // loop the end balances. This is an array for each account ($expenses)
        foreach ($endBalances as $accountId => $expenses) {
            $accountId = (int)$accountId;
            // loop each expense entry (each entry can be a different currency).
            foreach ($expenses as $currencyId => $endAmount) {
                $currencyId = (int)$currencyId;

                // see if there is an accompanying start amount.
                // grab the difference and find the currency.
                $startAmount             = $startBalances[$accountId][$currencyId] ?? '0';
                $diff                    = bcsub($endAmount, $startAmount);
                $currencies[$currencyId] = $currencies[$currencyId] ?? $this->currencyRepository->findNull($currencyId);
                if (0 !== bccomp($diff, '0')) {
                    // store the values in a temporary array.
                    $tempData[] = [
                        'name'        => $accountNames[$accountId],
                        'difference'  => bcmul($diff, '-1'),
                        'diff_float'  => (float)$diff * -1,
                        'currency_id' => $currencyId,
                    ];
                }
            }
        }

        // sort temp array by amount.
        $amounts = array_column($tempData, 'diff_float');
        array_multisort($amounts, SORT_DESC, $tempData);

        // loop all found currencies and build the data array for the chart.
        /**
         * @var int                 $currencyId
         * @var TransactionCurrency $currency
         */
        foreach ($currencies as $currencyId => $currency) {
            $currentSet             = [
                'label'                   => trans('firefly.box_earned_in_currency', ['currency' => $currency->symbol]),
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'type'                    => 'bar', // line, area or bar
                'yAxisID'                 => 0, // 0, 1, 2
                'entries'                 => $this->expandNames($tempData),
            ];
            $chartData[$currencyId] = $currentSet;
        }

        // loop temp data and place data in correct array:
        foreach ($tempData as $entry) {
            $currencyId                               = $entry['currency_id'];
            $name                                     = $entry['name'];
            $chartData[$currencyId]['entries'][$name] = round($entry['difference'], $chartData[$currencyId]['currency_decimal_places']);
        }
        $chartData = array_values($chartData);

        return response()->json($chartData);
    }

    /**
     * Small helper function for the revenue and expense account charts.
     * TODO should include Trait instead of doing this.
     *
     * @param array $names
     *
     * @return array
     */
    protected function expandNames(array $names): array
    {
        $result = [];
        foreach ($names as $entry) {
            $result[$entry['name']] = 0;
        }

        return $result;
    }

    /**
     * Small helper function for the revenue and expense account charts.
     * TODO should include Trait instead of doing this.
     *
     * @param Collection $accounts
     *
     * @return array
     */
    protected function extractNames(Collection $accounts): array
    {
        $return = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $return[$account->id] = $account->name;
        }

        return $return;
    }

}

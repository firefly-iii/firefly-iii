<?php

/*
 * AccountController.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Generic\DateRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Api\ConvertsExchangeRates;
use Illuminate\Http\JsonResponse;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Support\Collection;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Models\TransactionType;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use ConvertsExchangeRates;

    private AccountRepositoryInterface $repository;
    private GeneratorInterface $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(GeneratorInterface::class);
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v2)#/charts/getChartAccountOverview
     *
     * @param  DateRequest  $request
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function dashboard(DateRequest $request): JsonResponse
    {
        // parameters for chart:
        $dates = $request->getAll();
        /** @var Carbon $start */
        $start = $dates['start'];
        /** @var Carbon $end */
        $end = $dates['end'];

        // user's preferences
        $defaultSet = $this->repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT])->pluck('id')->toArray();
        $frontPage  = app('preferences')->get('frontPageAccounts', $defaultSet);
        $default    = app('amount')->getDefaultCurrency();
        $accounts   = $this->repository->getAccountsById($frontPage->data);
        $chartData  = [];

        if (!(is_array($frontPage->data) && count($frontPage->data) > 0)) {
            $frontPage->data = $defaultSet;
            $frontPage->save();
        }

        /** @var Account $account */
        foreach ($accounts as $account) {
            $currency = $this->repository->getAccountCurrency($account);
            if (null === $currency) {
                $currency = $default;
            }
            $currentSet   = [
                'label'                   => $account->name,
                'currency_id'             => (string)$currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'native_id'               => null,
                'native_code'             => null,
                'native_symbol'           => null,
                'native_decimal_places'   => null,
                'start_date'              => $start->toAtomString(),
                'end_date'                => $end->toAtomString(),
                'type'                    => 'line', // line, area or bar
                'yAxisID'                 => 0, // 0, 1, 2
                'entries'                 => [],
            ];
            $currentStart = clone $start;
            $range        = app('steam')->balanceInRange($account, $start, clone $end);

            // 2022-10-11: this method no longer converts to floats

            $previous = array_values($range)[0];
            while ($currentStart <= $end) {
                $format   = $currentStart->format('Y-m-d');
                $label    = $currentStart->toAtomString();
                $balance  = array_key_exists($format, $range) ? $range[$format] : $previous;
                $previous = $balance;
                $currentStart->addDay();
                $currentSet['entries'][$label] = $balance;
            }
            $currentSet  = $this->cerChartSet($currentSet);
            $chartData[] = $currentSet;
        }

        return response()->json($chartData);
    }



    /**
     * Shows income and expense, debit/credit: operations.
     *
     * @param  Collection  $accounts
     * @param  Carbon  $start
     * @param  Carbon  $end
     *
     * @return JsonResponse
     * @throws JsonException
     */
    public function operations(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        $format         = app('navigation')->preferredCarbonFormat($start, $end);
        $titleFormat    = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $preferredRange = app('navigation')->preferredRangeFormat($start, $end);
        $ids            = $accounts->pluck('id')->toArray();

        // get journals for entire period:
        $data      = [];
        $chartData = [];

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->withAccountInformation();
        $collector->setXorAccounts($accounts);
        $collector->setTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::RECONCILIATION, TransactionType::TRANSFER]);
        $journals = $collector->getExtractedJournals();

        // loop. group by currency and by period.
        /** @var array $journal */
        foreach ($journals as $journal) {
            $period                     = $journal['date']->format($format);
            $currencyId                 = (int)$journal['currency_id'];
            $data[$currencyId]          = $data[$currencyId] ?? [
                'currency_id'             => $currencyId,
                'currency_symbol'         => $journal['currency_symbol'],
                'currency_code'           => $journal['currency_code'],
                'currency_name'           => $journal['currency_name'],
                'currency_decimal_places' => (int)$journal['currency_decimal_places'],
            ];
            $data[$currencyId][$period] = $data[$currencyId][$period] ?? [
                'period' => $period,
                'spent'  => '0',
                'earned' => '0',
            ];
            // in our outgoing?
            $key    = 'spent';
            $amount = app('steam')->positive($journal['amount']);

            // deposit = incoming
            // transfer or reconcile or opening balance, and these accounts are the destination.
            if (
                TransactionType::DEPOSIT === $journal['transaction_type_type']
                ||

                (
                    (
                        TransactionType::TRANSFER === $journal['transaction_type_type']
                        || TransactionType::RECONCILIATION === $journal['transaction_type_type']
                        || TransactionType::OPENING_BALANCE === $journal['transaction_type_type']
                    )
                    && in_array($journal['destination_account_id'], $ids, true)
                )
            ) {
                $key = 'earned';
            }
            $data[$currencyId][$period][$key] = bcadd($data[$currencyId][$period][$key], $amount);
        }

        // loop this data, make chart bars for each currency:
        /** @var array $currency */
        foreach ($data as $currency) {
            $income  = [
                'label'           => (string)trans('firefly.box_earned_in_currency', ['currency' => $currency['currency_name']]),
                'type'            => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
                'currency_id'     => $currency['currency_id'],
                'currency_symbol' => $currency['currency_symbol'],
                'currency_code'   => $currency['currency_code'],
                'entries'         => [],
            ];
            $expense = [
                'label'           => (string)trans('firefly.box_spent_in_currency', ['currency' => $currency['currency_name']]),
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
                'currency_id'     => $currency['currency_id'],
                'currency_symbol' => $currency['currency_symbol'],
                'currency_code'   => $currency['currency_code'],
                'entries'         => [],
            ];
            // loop all possible periods between $start and $end
            $currentStart = clone $start;
            while ($currentStart <= $end) {
                $key                        = $currentStart->format($format);
                $title                      = $currentStart->isoFormat($titleFormat);
                $income['entries'][$title]  = app('steam')->bcround(($currency[$key]['earned'] ?? '0'), $currency['currency_decimal_places']);
                $expense['entries'][$title] = app('steam')->bcround(($currency[$key]['spent'] ?? '0'), $currency['currency_decimal_places']);
                $currentStart               = app('navigation')->addPeriod($currentStart, $preferredRange, 0);
            }

            $chartData[] = $income;
            $chartData[] = $expense;
        }

        $data = $this->generator->multiSet($chartData);

        return response()->json($data);
    }
}

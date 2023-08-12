<?php

declare(strict_types=1);
/*
 * BalanceController.php
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

namespace FireflyIII\Api\V2\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Chart\BalanceChartRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Administration\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Api\CleansChartData;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class BalanceController
 */
class BalanceController extends Controller
{
    use CleansChartData;

    private AccountRepositoryInterface $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * The code is practically a duplicate of ReportController::operations.
     *
     * Currency is up to the account/transactions in question, but conversion to the default
     * currency is possible.
     *
     * If the transaction being processed is already in native currency OR if the
     * foreign amount is in the native currency, the amount will not be converted.
     *
     * TODO validate and set administration_id
     * TODO collector set group, not user
     *
     * @param BalanceChartRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function balance(BalanceChartRequest $request): JsonResponse
    {
        $params = $request->getAll();
        /** @var Carbon $start */
        $start = $this->parameters->get('start');
        /** @var Carbon $end */
        $end = $this->parameters->get('end');
        $end->endOfDay();
        /** @var Collection $accounts */
        $accounts       = $params['accounts'];
        $preferredRange = $params['period'];

        // set some formats, based on input parameters.
        $format = app('navigation')->preferredCarbonFormatByPeriod($preferredRange);

        // prepare for currency conversion and data collection:
        $ids = $accounts->pluck('id')->toArray();
        /** @var TransactionCurrency $default */
        $default    = app('amount')->getDefaultCurrency();
        $converter  = new ExchangeRateConverter();
        $currencies = [(int)$default->id => $default,]; // currency cache
        $data       = [];
        $chartData  = [];

        // get journals for entire period:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->withAccountInformation();
        $collector->setXorAccounts($accounts);
        $collector->setTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::RECONCILIATION, TransactionType::TRANSFER]);
        $journals = $collector->getExtractedJournals();

        // set array for default currency (even if unused later on)
        $defaultCurrencyId        = (int)$default->id;
        $data[$defaultCurrencyId] = [
            'currency_id'             => $defaultCurrencyId,
            'currency_symbol'         => $default->symbol,
            'currency_code'           => $default->code,
            'currency_name'           => $default->name,
            'currency_decimal_places' => (int)$default->decimal_places,
            'native_id'               => $defaultCurrencyId,
            'native_symbol'           => $default->symbol,
            'native_code'             => $default->code,
            'native_name'             => $default->name,
            'native_decimal_places'   => (int)$default->decimal_places,
        ];


        // loop. group by currency and by period.
        /** @var array $journal */
        foreach ($journals as $journal) {
            // format the date according to the period
            $period = $journal['date']->format($format);

            // collect (and cache) currency information for this journal.
            $currencyId              = (int)$journal['currency_id'];
            $currency                = $currencies[$currencyId] ?? TransactionCurrency::find($currencyId);
            $currencies[$currencyId] = $currency; // may just re-assign itself, don't mind.

            // set the array with monetary info, if it does not exist.
            $data[$currencyId] = $data[$currencyId] ?? [
                'currency_id'             => $currencyId,
                'currency_symbol'         => $journal['currency_symbol'],
                'currency_code'           => $journal['currency_code'],
                'currency_name'           => $journal['currency_name'],
                'currency_decimal_places' => $journal['currency_decimal_places'],
                // native currency info (could be the same)
                'native_id'               => (int)$default->id,
                'native_code'             => $default->code,
                'native_symbol'           => $default->symbol,
                'native_decimal_places'   => (int)$default->decimal_places,
            ];

            // set the array (in monetary info) with spent/earned in this $period, if it does not exist.
            $data[$currencyId][$period] = $data[$currencyId][$period] ?? [
                'period'        => $period,
                'spent'         => '0',
                'earned'        => '0',
                'native_spent'  => '0',
                'native_earned' => '0',
            ];
            // is this journal's amount in- our outgoing?
            $key    = 'spent';
            $amount = app('steam')->negative($journal['amount']);
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
                $key    = 'earned';
                $amount = app('steam')->positive($journal['amount']);
            }
            // get conversion rate
            $rate            = $converter->getCurrencyRate($currency, $default, $journal['date']);
            $amountConverted = bcmul($amount, $rate);

            // perhaps transaction already has the foreign amount in the native currency.
            if ((int)$journal['foreign_currency_id'] === (int)$default->id) {
                $amountConverted = $journal['foreign_amount'] ?? '0';
                $amountConverted = 'earned' === $key ? app('steam')->positive($amountConverted) : app('steam')->negative($amountConverted);
            }

            // add normal entry
            $data[$currencyId][$period][$key] = bcadd($data[$currencyId][$period][$key], $amount);

            // add converted entry
            $convertedKey                              = sprintf('native_%s', $key);
            $data[$currencyId][$period][$convertedKey] = bcadd($data[$currencyId][$period][$convertedKey], $amountConverted);
        }

        // loop this data, make chart bars for each currency:
        /** @var array $currency */
        foreach ($data as $currency) {
            // income and expense array prepped:
            $income  = [
                'label'                   => 'earned',
                'currency_id'             => $currency['currency_id'],
                'currency_symbol'         => $currency['currency_symbol'],
                'currency_code'           => $currency['currency_code'],
                'currency_decimal_places' => $currency['currency_decimal_places'],
                'native_id'               => $currency['native_id'],
                'native_symbol'           => $currency['native_symbol'],
                'native_code'             => $currency['native_code'],
                'native_decimal_places'   => $currency['native_decimal_places'],
                'start'                   => $start->toAtomString(),
                'end'                     => $end->toAtomString(),
                'period'                  => $preferredRange,
                'entries'                 => [],
                'native_entries'          => [],
            ];
            $expense = [
                'label'                   => 'spent',
                'currency_id'             => $currency['currency_id'],
                'currency_symbol'         => $currency['currency_symbol'],
                'currency_code'           => $currency['currency_code'],
                'currency_decimal_places' => $currency['currency_decimal_places'],
                'native_id'               => $currency['native_id'],
                'native_symbol'           => $currency['native_symbol'],
                'native_code'             => $currency['native_code'],
                'native_decimal_places'   => $currency['native_decimal_places'],
                'start'                   => $start->toAtomString(),
                'end'                     => $end->toAtomString(),
                'period'                  => $preferredRange,
                'entries'                 => [],
                'native_entries'          => [],

            ];
            // loop all possible periods between $start and $end, and add them to the correct dataset.
            $currentStart = clone $start;
            while ($currentStart <= $end) {
                $key   = $currentStart->format($format);
                $label = $currentStart->toAtomString();
                // normal entries
                $income['entries'][$label]  = app('steam')->bcround(($currency[$key]['earned'] ?? '0'), $currency['currency_decimal_places']);
                $expense['entries'][$label] = app('steam')->bcround(($currency[$key]['spent'] ?? '0'), $currency['currency_decimal_places']);

                // converted entries
                $income['native_entries'][$label]  = app('steam')->bcround(($currency[$key]['native_earned'] ?? '0'), $currency['native_decimal_places']);
                $expense['native_entries'][$label] = app('steam')->bcround(($currency[$key]['native_spent'] ?? '0'), $currency['native_decimal_places']);

                // next loop
                $currentStart = app('navigation')->addPeriod($currentStart, $preferredRange, 0);
            }

            $chartData[] = $income;
            $chartData[] = $expense;
        }
        return response()->json($this->clean($chartData));
    }

}

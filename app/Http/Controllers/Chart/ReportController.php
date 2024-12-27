<?php

/**
 * ReportController.php
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

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\BasicDataSupport;
use FireflyIII\Support\Http\Controllers\ChartGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class ReportController.
 */
class ReportController extends Controller
{
    use BasicDataSupport;
    use ChartGeneration;

    protected GeneratorInterface $generator;

    /**
     * ReportController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(GeneratorInterface::class);
    }

    /**
     * This chart, by default, is shown on the multi-year and year report pages,
     * which means that giving it a 2 week "period" should be enough granularity.
     */
    public function netWorth(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        // chart properties for cache:
        $cache             = new CacheProperties();
        $cache->addProperty('chart.report.net-worth');
        $cache->addProperty($start);
        $cache->addProperty(implode(',', $accounts->pluck('id')->toArray()));
        $cache->addProperty($end);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $locale            = app('steam')->getLocale();
        $current           = clone $start;
        $chartData         = [];

        /** @var NetWorthInterface $helper */
        $helper            = app(NetWorthInterface::class);
        $helper->setUser(auth()->user());

        // filter accounts on having the preference for being included.
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $filtered          = $accounts->filter(
            static function (Account $account) use ($accountRepository) {
                $includeNetWorth = $accountRepository->getMetaValue($account, 'include_net_worth');
                $result          = null === $includeNetWorth ? true : '1' === $includeNetWorth;
                if (false === $result) {
                    Log::debug(sprintf('Will not include "%s" in net worth charts.', $account->name));
                }

                return $result;
            }
        );

        // TODO get liabilities and include those as well?

        while ($current < $end) {
            // get balances by date, grouped by currency.
            $result = $helper->byAccounts($filtered, $current);

            // loop result, add to array.
            /** @var array $netWorthItem */
            foreach ($result as $key => $netWorthItem) {
                if ('native' === $key) {
                    continue;
                }
                $currencyId                                = $netWorthItem['currency_id'];
                $label                                     = $current->isoFormat((string) trans('config.month_and_day_js', [], $locale));
                if (!array_key_exists($currencyId, $chartData)) {
                    $chartData[$currencyId] = [
                        'label'           => 'Net worth in '.$netWorthItem['currency_name'],
                        'type'            => 'line',
                        'currency_symbol' => $netWorthItem['currency_symbol'],
                        'currency_code'   => $netWorthItem['currency_code'],
                        'entries'         => [],
                    ];
                }
                $chartData[$currencyId]['entries'][$label] = $netWorthItem['balance'];
            }
            $current->addDays(7);
        }

        $data              = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows income and expense, debit/credit: operations.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function operations(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        $end->endOfDay();
        // chart properties for cache:
        $cache          = new CacheProperties();
        $cache->addProperty('chart.report.operations');
        $cache->addProperty($start);
        $cache->addProperty($accounts);
        $cache->addProperty($end);
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        Log::debug('Going to do operations for accounts ', $accounts->pluck('id')->toArray());
        Log::debug(sprintf('Period: %s to %s', $start->toW3cString(), $end->toW3cString()));
        $format         = app('navigation')->preferredCarbonFormat($start, $end);
        $titleFormat    = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $preferredRange = app('navigation')->preferredRangeFormat($start, $end);
        $ids            = $accounts->pluck('id')->toArray();
        $data           = [];
        $chartData      = [];

        /** @var GroupCollectorInterface $collector */
        $collector      = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->withAccountInformation();
        $collector->setXorAccounts($accounts);
        $collector->setTypes(
            [
                TransactionTypeEnum::WITHDRAWAL,
                TransactionTypeEnum::DEPOSIT,
                TransactionTypeEnum::RECONCILIATION,
                TransactionTypeEnum::TRANSFER,
            ]
        );
        $journals       = $collector->getExtractedJournals();

        // loop. group by currency and by period.
        /** @var array $journal */
        foreach ($journals as $journal) {
            $period                           = $journal['date']->format($format);
            $currencyId                       = (int) $journal['currency_id'];
            $data[$currencyId]          ??= [
                'currency_id'             => $currencyId,
                'currency_symbol'         => $journal['currency_symbol'],
                'currency_code'           => $journal['currency_code'],
                'currency_name'           => $journal['currency_name'],
                'currency_decimal_places' => (int) $journal['currency_decimal_places'],
            ];
            $data[$currencyId][$period] ??= [
                'period' => $period,
                'spent'  => '0',
                'earned' => '0',
            ];
            // in our outgoing?
            $key                              = 'spent';
            $amount                           = app('steam')->positive($journal['amount']);

            // deposit = incoming
            // transfer or reconcile or opening balance, and these accounts are the destination.
            if (
                TransactionTypeEnum::DEPOSIT->value === $journal['transaction_type_type']
                || ((
                    TransactionTypeEnum::TRANSFER->value === $journal['transaction_type_type']
                        || TransactionTypeEnum::RECONCILIATION->value === $journal['transaction_type_type']
                        || TransactionTypeEnum::OPENING_BALANCE->value === $journal['transaction_type_type']
                )
                    && in_array($journal['destination_account_id'], $ids, true))) {
                $key = 'earned';
            }
            $data[$currencyId][$period][$key] = bcadd($data[$currencyId][$period][$key], $amount);
        }

        // loop this data, make chart bars for each currency:
        Log::debug('Looping data');

        /** @var array $currency */
        foreach ($data as $currency) {
            Log::debug(sprintf('Now processing currency "%s"', $currency['currency_name']));
            $income       = [
                'label'           => (string) trans('firefly.box_earned_in_currency', ['currency' => $currency['currency_name']]),
                'type'            => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
                'currency_id'     => $currency['currency_id'],
                'currency_symbol' => $currency['currency_symbol'],
                'currency_code'   => $currency['currency_code'],
                'entries'         => [],
            ];
            $expense      = [
                'label'           => (string) trans('firefly.box_spent_in_currency', ['currency' => $currency['currency_name']]),
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
                'currency_id'     => $currency['currency_id'],
                'currency_symbol' => $currency['currency_symbol'],
                'currency_code'   => $currency['currency_code'],
                'entries'         => [],
            ];
            // loop all possible periods between $start and $end
            $currentStart = clone $start;
            $currentEnd   = clone $end;
            Log::debug(sprintf('START current start and end: %s and %s', $currentStart->toW3cString(), $currentEnd->toW3cString()));

            // #8374. Sloppy fix for yearly charts. Not really interested in a better fix with v2 layout and all.
            if ('1Y' === $preferredRange) {
                $currentEnd = app('navigation')->endOfPeriod($currentEnd, $preferredRange);
            }
            Log::debug('Start of sub-loop');
            while ($currentStart <= $currentEnd) {
                Log::debug(sprintf('Current start: %s', $currentStart->toW3cString()));
                $key          = $currentStart->format($format);
                $title        = $currentStart->isoFormat($titleFormat);
                // #8663 make sure the period exists in the data previously collected.
                if (array_key_exists($key, $currency)) {
                    $income['entries'][$title]  = app('steam')->bcround($currency[$key]['earned'] ?? '0', $currency['currency_decimal_places']);
                    $expense['entries'][$title] = app('steam')->bcround($currency[$key]['spent'] ?? '0', $currency['currency_decimal_places']);
                }
                // #9477 if the period is not in the data, add it with zero values.
                if (!array_key_exists($key, $currency)) {
                    $income['entries'][$title]  = '0';
                    $expense['entries'][$title] = '0';

                }
                $currentStart = app('navigation')->addPeriod($currentStart, $preferredRange, 0);
            }
            Log::debug('End of sub-loop');

            $chartData[]  = $income;
            $chartData[]  = $expense;
        }
        Log::debug('End of loop');

        $data           = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }
}

<?php
/**
 * ReportController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\BasicDataSupport;
use FireflyIII\Support\Http\Controllers\ChartGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Log;

/**
 * Class ReportController.
 */
class ReportController extends Controller
{
    use BasicDataSupport, ChartGeneration;
    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    /**
     * ReportController constructor.
     * @codeCoverageIgnore
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
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     */
    public function netWorth(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('chart.report.net-worth');
        $cache->addProperty($start);
        $cache->addProperty(implode(',', $accounts->pluck('id')->toArray()));
        $cache->addProperty($end);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $current   = clone $start;
        $chartData = [];
        /** @var NetWorthInterface $helper */
        $helper = app(NetWorthInterface::class);
        $helper->setUser(auth()->user());

        // filter accounts on having the preference for being included.
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $filtered          = $accounts->filter(
            function (Account $account) use ($accountRepository) {
                $includeNetWorth = $accountRepository->getMetaValue($account, 'include_net_worth');
                $result          = null === $includeNetWorth ? true : '1' === $includeNetWorth;
                if (false === $result) {
                    Log::debug(sprintf('Will not include "%s" in net worth charts.', $account->name));
                }

                return $result;
            }
        );


        while ($current < $end) {
            // get balances by date, grouped by currency.
            $result = $helper->getNetWorthByCurrency($filtered, $current);

            // loop result, add to array.
            /** @var array $netWorthItem */
            foreach ($result as $netWorthItem) {
                $currencyId = $netWorthItem['currency']->id;
                $label      = $current->formatLocalized((string)trans('config.month_and_day'));
                if (!isset($chartData[$currencyId])) {
                    $chartData[$currencyId] = [
                        'label'           => 'Net worth in ' . $netWorthItem['currency']->name,
                        'type'            => 'line',
                        'currency_symbol' => $netWorthItem['currency']->symbol,
                        'entries'         => [],
                    ];
                }
                $chartData[$currencyId]['entries'][$label] = $netWorthItem['balance'];

            }
            $current->addDays(7);
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows income and expense, debit/credit: operations.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function operations(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('chart.report.operations');
        $cache->addProperty($start);
        $cache->addProperty($accounts);
        $cache->addProperty($end);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        Log::debug('Going to do operations for accounts ', $accounts->pluck('id')->toArray());
        $format    = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $source    = $this->getChartData($accounts, $start, $end);
        $chartData = [
            [
                'label'           => (string)trans('firefly.income'),
                'type'            => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
                'entries'         => [],
            ],
            [
                'label'           => (string)trans('firefly.expenses'),
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
                'entries'         => [],
            ],
        ];
        foreach ($source['earned'] as $date => $amount) {
            $carbon                          = new Carbon($date);
            $label                           = $carbon->formatLocalized($format);
            $earned                          = $chartData[0]['entries'][$label] ?? '0';
            $amount                          = bcmul($amount, '-1');
            $chartData[0]['entries'][$label] = bcadd($earned, $amount);
        }
        foreach ($source['spent'] as $date => $amount) {
            $carbon                          = new Carbon($date);
            $label                           = $carbon->formatLocalized($format);
            $spent                           = $chartData[1]['entries'][$label] ?? '0';
            $chartData[1]['entries'][$label] = bcadd($spent, $amount);
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows sum income and expense, debit/credit: operations.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function sum(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('chart.report.sum');
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        $source  = $this->getChartData($accounts, $start, $end);
        $numbers = [
            'sum_earned'   => '0',
            'avg_earned'   => '0',
            'count_earned' => 0,
            'sum_spent'    => '0',
            'avg_spent'    => '0',
            'count_spent'  => 0,
        ];
        foreach ($source['earned'] as $amount) {
            $amount = bcmul($amount, '-1');
            $numbers['sum_earned'] = bcadd($amount, $numbers['sum_earned']);
            ++$numbers['count_earned'];
        }
        if ($numbers['count_earned'] > 0) {
            $numbers['avg_earned'] = $numbers['sum_earned'] / $numbers['count_earned'];
        }
        foreach ($source['spent'] as $amount) {
            $numbers['sum_spent'] = bcadd($amount, $numbers['sum_spent']);
            ++$numbers['count_spent'];
        }
        if ($numbers['count_spent'] > 0) {
            $numbers['avg_spent'] = $numbers['sum_spent'] / $numbers['count_spent'];
        }

        $chartData = [
            [
                'label'           => (string)trans('firefly.income'),
                'type'            => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
                'entries'         => [
                    (string)trans('firefly.sum_of_period')     => $numbers['sum_earned'],
                    (string)trans('firefly.average_in_period') => $numbers['avg_earned'],
                ],
            ],
            [
                'label'           => (string)trans('firefly.expenses'),
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
                'entries'         => [
                    (string)trans('firefly.sum_of_period')     => $numbers['sum_spent'],
                    (string)trans('firefly.average_in_period') => $numbers['avg_spent'],
                ],
            ],
        ];

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }
}

<?php
/**
 * ReportController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Log;
use Navigation;
use Response;
use Steam;

/**
 * Class ReportController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class ReportController extends Controller
{

    /** @var GeneratorInterface */
    protected $generator;

    /**
     *
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function netWorth(Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('chart.report.net-worth');
        $cache->addProperty($start);
        $cache->addProperty($accounts);
        $cache->addProperty($end);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        $ids       = $accounts->pluck('id')->toArray();
        $current   = clone $start;
        $chartData = [];
        while ($current < $end) {
            $balances          = Steam::balancesById($ids, $current);
            $sum               = $this->arraySum($balances);
            $label             = $current->formatLocalized(strval(trans('config.month_and_day')));
            $chartData[$label] = $sum;
            $current->addDays(7);
        }

        $data = $this->generator->singleSet(strval(trans('firefly.net_worth')), $chartData);
        $cache->store($data);

        return Response::json($data);
    }


    /**
     * Shows income and expense, debet/credit: operations
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function operations(Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('chart.report.operations');
        $cache->addProperty($start);
        $cache->addProperty($accounts);
        $cache->addProperty($end);
        if ($cache->has()) {
            //return Response::json($cache->get()); // @codeCoverageIgnore
        }
        Log::debug('Going to do operations for accounts ', $accounts->pluck('id')->toArray());
        $format    = Navigation::preferredCarbonLocalizedFormat($start, $end);
        $source    = $this->getChartData($accounts, $start, $end);
        $chartData = [
            [
                'label'   => trans('firefly.income'),
                'type'    => 'bar',
                'entries' => [],
            ],
            [
                'label'   => trans('firefly.expenses'),
                'type'    => 'bar',
                'entries' => [],
            ],
        ];

        foreach ($source['earned'] as $date => $amount) {
            $carbon                          = new Carbon($date);
            $label                           = $carbon->formatLocalized($format);
            $earned                          = $chartData[0]['entries'][$label] ?? '0';
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

        return Response::json($data);

    }

    /**
     * Shows sum income and expense, debet/credit: operations
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sum(Collection $accounts, Carbon $start, Carbon $end)
    {


        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('chart.report.sum');
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
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
            $numbers['sum_earned'] = bcadd($amount, $numbers['sum_earned']);
            $numbers['count_earned']++;
        }
        if ($numbers['count_earned'] > 0) {
            $numbers['avg_earned'] = $numbers['sum_earned'] / $numbers['count_earned'];
        }
        foreach ($source['spent'] as $amount) {
            $numbers['sum_spent'] = bcadd($amount, $numbers['sum_spent']);
            $numbers['count_spent']++;
        }
        if ($numbers['count_spent'] > 0) {
            $numbers['avg_spent'] = $numbers['sum_spent'] / $numbers['count_spent'];
        }

        $chartData = [
            [
                'label'   => strval(trans('firefly.income')),
                'type'    => 'bar',
                'entries' => [
                    strval(trans('firefly.sum_of_period'))     => $numbers['sum_earned'],
                    strval(trans('firefly.average_in_period')) => $numbers['avg_earned'],
                ],
            ],
            [
                'label'   => trans('firefly.expenses'),
                'type'    => 'bar',
                'entries' => [
                    strval(trans('firefly.sum_of_period'))     => $numbers['sum_spent'],
                    strval(trans('firefly.average_in_period')) => $numbers['avg_spent'],
                ],
            ],
        ];


        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return Response::json($data);

    }

    /**
     * @param $array
     *
     * @return string
     */
    private function arraySum($array): string
    {
        $sum = '0';
        foreach ($array as $entry) {
            $sum = bcadd($sum, $entry);
        }

        return $sum;
    }

    /**
     * Collects the incomes and expenses for the given periods, grouped per month. Will cache its results
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    private function getChartData(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.report.get-chart-data');
        $cache->addProperty($start);
        $cache->addProperty($accounts);
        $cache->addProperty($end);
        if ($cache->has()) {
            // return $cache->get(); // @codeCoverageIgnore
        }

        $currentStart = clone $start;
        $spentArray   = [];
        $earnedArray  = [];

        /** @var AccountTaskerInterface $tasker */
        $tasker = app(AccountTaskerInterface::class);

        while ($currentStart <= $end) {

            $currentEnd = Navigation::endOfPeriod($currentStart, '1M');
            $earned     = strval(
                array_sum(
                    array_map(
                        function ($item) {
                            return $item['sum'];
                        }, $tasker->getIncomeReport($currentStart, $currentEnd, $accounts)
                    )
                )
            );

            $spent = strval(
                array_sum(
                    array_map(
                        function ($item) {
                            return $item['sum'];
                        }, $tasker->getExpenseReport($currentStart, $currentEnd, $accounts)
                    )
                )
            );


            $label               = $currentStart->format('Y-m') . '-01';
            $spentArray[$label]  = bcmul($spent, '-1');
            $earnedArray[$label] = $earned;
            $currentStart        = Navigation::addPeriod($currentStart, '1M', 0);
        }
        $result = [
            'spent'  => $spentArray,
            'earned' => $earnedArray,
        ];
        $cache->store($result);

        return $result;
    }
}

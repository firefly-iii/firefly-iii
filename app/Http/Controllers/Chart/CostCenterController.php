<?php
/**
 * CostCenterController.php
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
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\CostCenter;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\CostCenter\CostCenterRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\ChartGeneration;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Log;

/**
 * Class CostCenterController.
 */
class CostCenterController extends Controller
{
    use DateCalculation, AugumentData, ChartGeneration;
    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    /**
     * CostCenterController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(GeneratorInterface::class);
    }


    /**
     * Show an overview for a cost center for all time, per month/week/year.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param CostCenterRepositoryInterface $repository
     * @param AccountRepositoryInterface  $accountRepository
     * @param CostCenter                    $costCenter
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function all(CostCenterRepositoryInterface $repository, AccountRepositoryInterface $accountRepository, CostCenter $costCenter): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.cost-center.all');
        $cache->addProperty($costCenter->id);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $start    = $repository->firstUseDate($costCenter);
        $start    = $start ?? new Carbon;
        $range    = app('preferences')->get('viewRange', '1M')->data;
        $start    = app('navigation')->startOfPeriod($start, $range);
        $end      = new Carbon;
        $accounts = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);

        Log::debug(sprintf('Full range is %s to %s', $start->format('Y-m-d'), $end->format('Y-m-d')));

        $chartData = [
            [
                'label'           => (string)trans('firefly.spent'),
                'entries'         => [], 'type' => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
            ],
            [
                'label'           => (string)trans('firefly.earned'),
                'entries'         => [], 'type' => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
            ],
            [
                'label'   => (string)trans('firefly.sum'),
                'entries' => [], 'type' => 'line', 'fill' => false,
            ],
        ];
        $step      = $this->calculateStep($start, $end);
        $current   = clone $start;

        Log::debug(sprintf('abc Step is %s', $step));

        switch ($step) {
            case '1D':
                while ($current <= $end) {
                    Log::debug(sprintf('Current day is %s', $current->format('Y-m-d')));
                    $spent                           = $repository->spentInPeriod(new Collection([$costCenter]), $accounts, $current, $current);
                    $earned                          = $repository->earnedInPeriod(new Collection([$costCenter]), $accounts, $current, $current);
                    $sum                             = bcadd($spent, $earned);
                    $label                           = app('navigation')->periodShow($current, $step);
                    $chartData[0]['entries'][$label] = round(bcmul($spent, '-1'), 12);
                    $chartData[1]['entries'][$label] = round($earned, 12);
                    $chartData[2]['entries'][$label] = round($sum, 12);
                    $current->addDay();
                }
                break;
            case '1W':
            case '1M':
            case '1Y':
                while ($current <= $end) {
                    $currentEnd = app('navigation')->endOfPeriod($current, $step);
                    Log::debug(sprintf('abc Range is %s to %s', $current->format('Y-m-d'), $currentEnd->format('Y-m-d')));

                    $spent                           = $repository->spentInPeriod(new Collection([$costCenter]), $accounts, $current, $currentEnd);
                    $earned                          = $repository->earnedInPeriod(new Collection([$costCenter]), $accounts, $current, $currentEnd);
                    $sum                             = bcadd($spent, $earned);
                    $label                           = app('navigation')->periodShow($current, $step);
                    $chartData[0]['entries'][$label] = round(bcmul($spent, '-1'), 12);
                    $chartData[1]['entries'][$label] = round($earned, 12);
                    $chartData[2]['entries'][$label] = round($sum, 12);
                    $current                         = app('navigation')->addPeriod($current, $step, 0);
                }
                break;
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Shows the cost center chart on the front page.
     *
     * @param CostCenterRepositoryInterface $repository
     * @param AccountRepositoryInterface  $accountRepository
     *
     * @return JsonResponse
     */
    public function frontPage(CostCenterRepositoryInterface $repository, AccountRepositoryInterface $accountRepository): JsonResponse
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.cost-center.frontpage');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        // currency repos:
        /** @var CurrencyRepositoryInterface $currencyRepository */
        $currencyRepository = app(CurrencyRepositoryInterface::class);
        $currencies         = [];


        $chartData   = [];
        $tempData    = [];
        $costCenters = $repository->getCostCenters();
        $accounts    = $accountRepository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);

        /** @var CostCenter $costCenter */
        foreach ($costCenters as $costCenter) {
            $spentArray = $repository->spentInPeriodPerCurrency(new Collection([$costCenter]), $accounts, $start, $end);
            foreach ($spentArray as $costCenterId => $spentInfo) {
                foreach ($spentInfo['spent'] as $currencyId => $row) {
                    $spent = $row['spent'];
                    if (bccomp($spent, '0') === -1) {
                        $currencies[$currencyId] = $currencies[$currencyId] ?? $currencyRepository->findNull((int)$currencyId);
                        $tempData[]              = [
                            'name'        => $costCenter->name,
                            'spent'       => bcmul($spent, '-1'),
                            'spent_float' => (float)bcmul($spent, '-1'),
                            'currency_id' => $currencyId,
                        ];
                    }
                }
            }
        }

        // no cost center per currency:
        $noCostCenter = $repository->spentInPeriodPcWoCostCenter(new Collection, $start, $end);
        foreach ($noCostCenter as $currencyId => $spent) {
            $currencies[$currencyId] = $currencies[$currencyId] ?? $currencyRepository->findNull($currencyId);
            $tempData[]              = [
                'name'        => trans('firefly.no_cost_center'),
                'spent'       => bcmul($spent['spent'], '-1'),
                'spent_float' => (float)bcmul($spent['spent'], '-1'),
                'currency_id' => $currencyId,
            ];
        }

        // sort temp array by amount.
        $amounts = array_column($tempData, 'spent_float');
        array_multisort($amounts, SORT_DESC, $tempData);

        // loop all found currencies and build the data array for the chart.
        /**
         * @var int                 $currencyId
         * @var TransactionCurrency $currency
         */
        foreach ($currencies as $currencyId => $currency) {
            $dataSet                = [
                'label'           => (string)trans('firefly.spent'),
                'type'            => 'bar',
                'currency_symbol' => $currency->symbol,
                'entries'         => $this->expandNames($tempData),
            ];
            $chartData[$currencyId] = $dataSet;
        }
        // loop temp data and place data in correct array:
        foreach ($tempData as $entry) {
            $currencyId                               = $entry['currency_id'];
            $name                                     = $entry['name'];
            $chartData[$currencyId]['entries'][$name] = $entry['spent'];
        }
        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Chart report.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param CostCenter $costCenter
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function reportPeriod(CostCenter $costCenter, Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.cost-center.period');
        $cache->addProperty($accounts->pluck('id')->toArray());
        $cache->addProperty($costCenter);
        if ($cache->has()) {
            return response()->json($cache->get());// @codeCoverageIgnore
        }
        $repository = app(CostCenterRepositoryInterface::class);
        $expenses   = $repository->periodExpenses(new Collection([$costCenter]), $accounts, $start, $end);
        $income     = $repository->periodIncome(new Collection([$costCenter]), $accounts, $start, $end);
        $periods    = app('navigation')->listOfPeriods($start, $end);
        $chartData  = [
            [
                'label'           => (string)trans('firefly.spent'),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
            ],
            [
                'label'           => (string)trans('firefly.earned'),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
            ],
            [
                'label'   => (string)trans('firefly.sum'),
                'entries' => [],
                'type'    => 'line',
                'fill'    => false,
            ],
        ];

        foreach (array_keys($periods) as $period) {
            $label                           = $periods[$period];
            $spent                           = $expenses[$costCenter->id]['entries'][$period] ?? '0';
            $earned                          = $income[$costCenter->id]['entries'][$period] ?? '0';
            $sum                             = bcadd($spent, $earned);
            $chartData[0]['entries'][$label] = round(bcmul($spent, '-1'), 12);
            $chartData[1]['entries'][$label] = round($earned, 12);
            $chartData[2]['entries'][$label] = round($sum, 12);
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * Chart for period for transactions without a cost center.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function reportPeriodNoCostCenter(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.cost-center.period.no-cost-center');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $repository = app(CostCenterRepositoryInterface::class);
        $expenses   = $repository->periodExpensesNoCostCenter($accounts, $start, $end);
        $income     = $repository->periodIncomeNoCostCenter($accounts, $start, $end);
        $periods    = app('navigation')->listOfPeriods($start, $end);
        $chartData  = [
            [
                'label'           => (string)trans('firefly.spent'),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
            ],
            [
                'label'           => (string)trans('firefly.earned'),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
            ],
            [
                'label'   => (string)trans('firefly.sum'),
                'entries' => [],
                'type'    => 'line',
                'fill'    => false,
            ],
        ];

        foreach (array_keys($periods) as $period) {
            $label                           = $periods[$period];
            $spent                           = $expenses['entries'][$period] ?? '0';
            $earned                          = $income['entries'][$period] ?? '0';
            $sum                             = bcadd($spent, $earned);
            $chartData[0]['entries'][$label] = bcmul($spent, '-1');
            $chartData[1]['entries'][$label] = $earned;
            $chartData[2]['entries'][$label] = $sum;
        }
        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Chart for a specific period.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param CostCenter                  $costCenter
     * @param                             $date
     *
     * @return JsonResponse
     */
    public function specificPeriod(CostCenter $costCenter, Carbon $date): JsonResponse
    {
        $range = app('preferences')->get('viewRange', '1M')->data;
        $start = app('navigation')->startOfPeriod($date, $range);
        $end   = session()->get('end');
        if ($end < $start) {
            [$end, $start] = [$start, $end];
        }

        $data = $this->makePeriodChart($costCenter, $start, $end);

        return response()->json($data);
    }
}

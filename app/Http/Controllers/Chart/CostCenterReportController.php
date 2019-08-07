<?php
/**
 * CostCenterReportController.php
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
use FireflyIII\Helpers\Chart\MetaPieChartInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\CostCenter;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\TransactionCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Separate controller because many helper functions are shared.
 *
 * Class CostCenterReportController
 */
class CostCenterReportController extends Controller
{
    use AugumentData, TransactionCalculation;

    /** @var GeneratorInterface Chart generation methods. */
    private $generator;

    /**
     * CostCenterReportController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->generator = app(GeneratorInterface::class);

                return $next($request);
            }
        );
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Chart for expenses grouped by expense account.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $costCenters
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function accountExpense(Collection $accounts, Collection $costCenters, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts)->setCostCenters($costCenters)->setStart($start)->setEnd($end)->setCollectOtherObjects(1 === (int)$others);

        $chartData = $helper->generate('expense', 'account');
        $data      = $this->generator->pieChart($chartData);

        return response()->json($data);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Chart for income grouped by revenue account.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $costCenters
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function accountIncome(Collection $accounts, Collection $costCenters, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setCostCenters($costCenters);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === (int)$others);
        $chartData = $helper->generate('income', 'account');
        $data      = $this->generator->pieChart($chartData);

        return response()->json($data);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Chart for expenses grouped by expense account.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $costCenters
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function costCenterExpense(Collection $accounts, Collection $costCenters, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setCostCenters($costCenters);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === (int)$others);
        $chartData = $helper->generate('expense', 'cost_center');
        $data      = $this->generator->pieChart($chartData);

        return response()->json($data);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Piechart for income grouped by account.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $costCenters
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function costCenterIncome(Collection $accounts, Collection $costCenters, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setCostCenters($costCenters);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === (int)$others);
        $chartData = $helper->generate('income', 'cost_center');
        $data      = $this->generator->pieChart($chartData);

        return response()->json($data);
    }


    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Main report cost center chart.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $costCenters
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function mainChart(Collection $accounts, Collection $costCenters, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.cost_center.report.main');
        $cache->addProperty($accounts);
        $cache->addProperty($costCenters);
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        $format       = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $function     = app('navigation')->preferredEndOfPeriod($start, $end);
        $chartData    = [];
        $currentStart = clone $start;

        // prep chart data:
        foreach ($costCenters as $costCenter) {
            $chartData[$costCenter->id . '-in']  = [
                'label'   => $costCenter->name . ' (' . strtolower((string)trans('firefly.income')) . ')',
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            $chartData[$costCenter->id . '-out'] = [
                'label'   => $costCenter->name . ' (' . strtolower((string)trans('firefly.expenses')) . ')',
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            // total in, total out:
            $chartData[$costCenter->id . '-total-in']  = [
                'label'   => $costCenter->name . ' (' . strtolower((string)trans('firefly.sum_of_income')) . ')',
                'type'    => 'line',
                'fill'    => false,
                'yAxisID' => 'y-axis-1',
                'entries' => [],
            ];
            $chartData[$costCenter->id . '-total-out'] = [
                'label'   => $costCenter->name . ' (' . strtolower((string)trans('firefly.sum_of_expenses')) . ')',
                'type'    => 'line',
                'fill'    => false,
                'yAxisID' => 'y-axis-1',
                'entries' => [],
            ];
        }
        $sumOfIncome  = [];
        $sumOfExpense = [];

        while ($currentStart < $end) {
            $currentEnd = clone $currentStart;
            $currentEnd = $currentEnd->$function();
            $expenses   = $this->groupByCostCenter($this->getExpensesInCostCenters($accounts, $costCenters, $currentStart, $currentEnd));
            $income     = $this->groupByCostCenter($this->getIncomeForCostCenters($accounts, $costCenters, $currentStart, $currentEnd));
            $label      = $currentStart->formatLocalized($format);

            /** @var CostCenter $costCenter */
            foreach ($costCenters as $costCenter) {
                $labelIn        = $costCenter->id . '-in';
                $labelOut       = $costCenter->id . '-out';
                $labelSumIn     = $costCenter->id . '-total-in';
                $labelSumOut    = $costCenter->id . '-total-out';
                $currentIncome  = $income[$costCenter->id] ?? '0';
                $currentExpense = $expenses[$costCenter->id] ?? '0';

                // add to sum:
                $sumOfIncome[$costCenter->id]  = $sumOfIncome[$costCenter->id] ?? '0';
                $sumOfExpense[$costCenter->id] = $sumOfExpense[$costCenter->id] ?? '0';
                $sumOfIncome[$costCenter->id]  = bcadd($sumOfIncome[$costCenter->id], $currentIncome);
                $sumOfExpense[$costCenter->id] = bcadd($sumOfExpense[$costCenter->id], $currentExpense);

                // add to chart:
                $chartData[$labelIn]['entries'][$label]     = $currentIncome;
                $chartData[$labelOut]['entries'][$label]    = $currentExpense;
                $chartData[$labelSumIn]['entries'][$label]  = $sumOfIncome[$costCenter->id];
                $chartData[$labelSumOut]['entries'][$label] = $sumOfExpense[$costCenter->id];
            }
            /** @var Carbon $currentStart */
            $currentStart = clone $currentEnd;
            $currentStart->addDay();
        }
        // remove all empty entries to prevent cluttering:
        $newSet = [];
        foreach ($chartData as $key => $entry) {
            if (0 === !array_sum($entry['entries'])) {
                $newSet[$key] = $chartData[$key];
            }
        }
        if (0 === \count($newSet)) {
            $newSet = $chartData;
        }
        $data = $this->generator->multiSet($newSet);
        $cache->store($data);

        return response()->json($data);
    }


}

<?php
/**
 * CategoryReportController.php
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
use FireflyIII\Models\Category;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\TransactionCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Separate controller because many helper functions are shared.
 *
 * Class CategoryReportController
 */
class CategoryReportController extends Controller
{
    use AugumentData, TransactionCalculation;

    /** @var GeneratorInterface Chart generation methods. */
    private $generator;

    /**
     * CategoryReportController constructor.
     * @codeCoverageIgnore
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
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function accountExpense(Collection $accounts, Collection $categories, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts)->setCategories($categories)->setStart($start)->setEnd($end)->setCollectOtherObjects(1 === (int)$others);

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
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function accountIncome(Collection $accounts, Collection $categories, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setCategories($categories);
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
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function categoryExpense(Collection $accounts, Collection $categories, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setCategories($categories);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === (int)$others);
        $chartData = $helper->generate('expense', 'category');
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
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function categoryIncome(Collection $accounts, Collection $categories, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setCategories($categories);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === (int)$others);
        $chartData = $helper->generate('income', 'category');
        $data      = $this->generator->pieChart($chartData);

        return response()->json($data);
    }


    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Main report category chart.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function mainChart(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.category.report.main');
        $cache->addProperty($accounts);
        $cache->addProperty($categories);
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
            //return response()->json($cache->get()); // @codeCoverageIgnore
        }

        $format       = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $function     = app('navigation')->preferredEndOfPeriod($start, $end);
        $chartData    = [];
        $currentStart = clone $start;

        // prep chart data:
        foreach ($categories as $category) {
            $chartData[$category->id . '-in']  = [
                'label'   => $category->name . ' (' . strtolower((string)trans('firefly.income')) . ')',
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            $chartData[$category->id . '-out'] = [
                'label'   => $category->name . ' (' . strtolower((string)trans('firefly.expenses')) . ')',
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            // total in, total out:
            $chartData[$category->id . '-total-in']  = [
                'label'   => $category->name . ' (' . strtolower((string)trans('firefly.sum_of_income')) . ')',
                'type'    => 'line',
                'fill'    => false,
                'yAxisID' => 'y-axis-1',
                'entries' => [],
            ];
            $chartData[$category->id . '-total-out'] = [
                'label'   => $category->name . ' (' . strtolower((string)trans('firefly.sum_of_expenses')) . ')',
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
            $expenses   = $this->groupByCategory($this->getExpensesInCategories($accounts, $categories, $currentStart, $currentEnd));
            $income     = $this->groupByCategory($this->getIncomeForCategories($accounts, $categories, $currentStart, $currentEnd));
            $label      = $currentStart->formatLocalized($format);

            /** @var Category $category */
            foreach ($categories as $category) {
                $labelIn        = $category->id . '-in';
                $labelOut       = $category->id . '-out';
                $labelSumIn     = $category->id . '-total-in';
                $labelSumOut    = $category->id . '-total-out';
                $currentIncome  = bcmul($income[$category->id] ?? '0','-1');
                $currentExpense = $expenses[$category->id] ?? '0';

                // add to sum:
                $sumOfIncome[$category->id]  = $sumOfIncome[$category->id] ?? '0';
                $sumOfExpense[$category->id] = $sumOfExpense[$category->id] ?? '0';
                $sumOfIncome[$category->id]  = bcadd($sumOfIncome[$category->id], $currentIncome);
                $sumOfExpense[$category->id] = bcadd($sumOfExpense[$category->id], $currentExpense);

                // add to chart:
                $chartData[$labelIn]['entries'][$label]     = $currentIncome;
                $chartData[$labelOut]['entries'][$label]    = $currentExpense;
                $chartData[$labelSumIn]['entries'][$label]  = $sumOfIncome[$category->id];
                $chartData[$labelSumOut]['entries'][$label] = $sumOfExpense[$category->id];
            }
            /** @var Carbon $currentStart */
            $currentStart = clone $currentEnd;
            $currentStart->addDay();
            $currentStart->startOfDay();
        }
        // remove all empty entries to prevent cluttering:
        $newSet = [];
        foreach ($chartData as $key => $entry) {
            if (0 === !array_sum($entry['entries'])) {
                $newSet[$key] = $chartData[$key]; // @codeCoverageIgnore
            }
        }
        if (0 === count($newSet)) {
            $newSet = $chartData;
        }
        $data = $this->generator->multiSet($newSet);
        $cache->store($data);

        return response()->json($data);
    }


}

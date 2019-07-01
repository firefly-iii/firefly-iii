<?php
/**
 * BudgetReportController.php
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
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\TransactionCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Separate controller because many helper functions are shared.
 *
 * Class BudgetReportController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BudgetReportController extends Controller
{
    use AugumentData, TransactionCalculation;
    /** @var BudgetRepositoryInterface The budget repository */
    private $budgetRepository;
    /** @var GeneratorInterface Chart generation methods. */
    private $generator;

    /**
     * BudgetReportController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->generator        = app(GeneratorInterface::class);
                $this->budgetRepository = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Chart that groups expenses by the account.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function accountExpense(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setBudgets($budgets);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === (int)$others);
        $chartData = $helper->generate('expense', 'account');
        $data      = $this->generator->pieChart($chartData);

        return response()->json($data);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Chart that groups the expenses by budget.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function budgetExpense(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end, string $others): JsonResponse
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setBudgets($budgets);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(1 === (int)$others);
        $chartData = $helper->generate('expense', 'budget');
        $data      = $this->generator->pieChart($chartData);

        return response()->json($data);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Main overview of a budget in the budget report.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function mainChart(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.budget.report.main');
        $cache->addProperty($accounts);
        $cache->addProperty($budgets);
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
        foreach ($budgets as $budget) {
            $chartData[$budget->id]           = [
                'label'   => (string)trans('firefly.spent_in_specific_budget', ['budget' => $budget->name]),
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            $chartData[$budget->id . '-sum']  = [
                'label'   => (string)trans('firefly.sum_of_expenses_in_budget', ['budget' => $budget->name]),
                'type'    => 'line',
                'fill'    => false,
                'yAxisID' => 'y-axis-1',
                'entries' => [],
            ];
            $chartData[$budget->id . '-left'] = [
                'label'   => (string)trans('firefly.left_in_budget_limit', ['budget' => $budget->name]),
                'type'    => 'bar',
                'fill'    => false,
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
        }
        $allBudgetLimits = $this->budgetRepository->getAllBudgetLimits($start, $end);
        $sumOfExpenses   = [];
        $leftOfLimits    = [];
        while ($currentStart < $end) {
            $currentEnd = clone $currentStart;
            $currentEnd = $currentEnd->$function();
            $expenses   = $this->groupByBudget($this->getExpensesInBudgets($accounts, $budgets, $currentStart, $currentEnd));
            $label      = $currentStart->formatLocalized($format);

            /** @var Budget $budget */
            foreach ($budgets as $budget) {
                // get budget limit(s) for this period):
                $budgetLimits                                       = $this->filterBudgetLimits($allBudgetLimits, $budget, $currentStart, $currentEnd);
                $currentExpenses                                    = $expenses[$budget->id] ?? '0';
                $sumOfExpenses[$budget->id]                         = $sumOfExpenses[$budget->id] ?? '0';
                $sumOfExpenses[$budget->id]                         = bcadd($currentExpenses, $sumOfExpenses[$budget->id]);
                $chartData[$budget->id]['entries'][$label]          = bcmul($currentExpenses, '-1');
                $chartData[$budget->id . '-sum']['entries'][$label] = bcmul($sumOfExpenses[$budget->id], '-1');

                if (count($budgetLimits) > 0) {
                    $budgetLimitId                                       = $budgetLimits->first()->id;
                    $leftOfLimits[$budgetLimitId]                        = $leftOfLimits[$budgetLimitId] ?? (string)$budgetLimits->sum('amount');
                    $leftOfLimits[$budgetLimitId]                        = bcadd($leftOfLimits[$budgetLimitId], $currentExpenses);
                    $chartData[$budget->id . '-left']['entries'][$label] = $leftOfLimits[$budgetLimitId];
                }
            }
            /** @var Carbon $currentStart */
            $currentStart = clone $currentEnd;
            $currentStart->addDay();
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

}

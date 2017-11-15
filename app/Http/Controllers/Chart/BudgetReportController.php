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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Chart\MetaPieChartInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\OpposingAccountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Helpers\Filter\TransferFilter;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Navigation;
use Response;

/**
 * Separate controller because many helper functions are shared.
 *
 * Class BudgetReportController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class BudgetReportController extends Controller
{

    /** @var BudgetRepositoryInterface */
    private $budgetRepository;
    /** @var  GeneratorInterface */
    private $generator;

    /**
     *
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

    /**
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accountExpense(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end, string $others)
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setBudgets($budgets);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(intval($others) === 1);
        $chartData = $helper->generate('expense', 'account');
        $data      = $this->generator->pieChart($chartData);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetExpense(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end, string $others)
    {
        /** @var MetaPieChartInterface $helper */
        $helper = app(MetaPieChartInterface::class);
        $helper->setAccounts($accounts);
        $helper->setBudgets($budgets);
        $helper->setStart($start);
        $helper->setEnd($end);
        $helper->setCollectOtherObjects(intval($others) === 1);
        $chartData = $helper->generate('expense', 'budget');
        $data      = $this->generator->pieChart($chartData);

        return Response::json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mainChart(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.budget.report.main');
        $cache->addProperty($accounts);
        $cache->addProperty($budgets);
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        $format       = Navigation::preferredCarbonLocalizedFormat($start, $end);
        $function     = Navigation::preferredEndOfPeriod($start, $end);
        $chartData    = [];
        $currentStart = clone $start;

        // prep chart data:
        foreach ($budgets as $budget) {
            $chartData[$budget->id]           = [
                'label'   => strval(trans('firefly.spent_in_specific_budget', ['budget' => $budget->name])),
                'type'    => 'bar',
                'yAxisID' => 'y-axis-0',
                'entries' => [],
            ];
            $chartData[$budget->id . '-sum']  = [
                'label'   => strval(trans('firefly.sum_of_expenses_in_budget', ['budget' => $budget->name])),
                'type'    => 'line',
                'fill'    => false,
                'yAxisID' => 'y-axis-1',
                'entries' => [],
            ];
            $chartData[$budget->id . '-left'] = [
                'label'   => strval(trans('firefly.left_in_budget_limit', ['budget' => $budget->name])),
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
            $expenses   = $this->groupByBudget($this->getExpenses($accounts, $budgets, $currentStart, $currentEnd));
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
                    $leftOfLimits[$budgetLimitId]                        = $leftOfLimits[$budgetLimitId] ?? strval($budgetLimits->sum('amount'));
                    $leftOfLimits[$budgetLimitId]                        = bcadd($leftOfLimits[$budgetLimitId], $currentExpenses);
                    $chartData[$budget->id . '-left']['entries'][$label] = $leftOfLimits[$budgetLimitId];
                }
            }
            $currentStart = clone $currentEnd;
            $currentStart->addDay();
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Returns the budget limits belonging to the given budget and valid on the given day.
     *
     * @param Collection $budgetLimits
     * @param Budget     $budget
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    private function filterBudgetLimits(Collection $budgetLimits, Budget $budget, Carbon $start, Carbon $end): Collection
    {
        $set = $budgetLimits->filter(
            function (BudgetLimit $budgetLimit) use ($budget, $start, $end) {
                if ($budgetLimit->budget_id === $budget->id
                    && $budgetLimit->start_date->lte($start) // start of budget limit is on or before start
                    && $budgetLimit->end_date->gte($end) // end of budget limit is on or after end
                ) {
                    return $budgetLimit;
                }

                return false;
            }
        );

        return $set;
    }

    /**
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    private function getExpenses(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end): Collection
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setBudgets($budgets)->withOpposingAccount();
        $collector->removeFilter(TransferFilter::class);

        $collector->addFilter(OpposingAccountFilter::class);
        $collector->addFilter(PositiveAmountFilter::class);

        $transactions = $collector->getJournals();

        return $transactions;
    }

    /**
     * @param Collection $set
     *
     * @return array
     */
    private function groupByBudget(Collection $set): array
    {
        // group by category ID:
        $grouped = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $jrnlBudId          = intval($transaction->transaction_journal_budget_id);
            $transBudId         = intval($transaction->transaction_budget_id);
            $budgetId           = max($jrnlBudId, $transBudId);
            $grouped[$budgetId] = $grouped[$budgetId] ?? '0';
            $grouped[$budgetId] = bcadd($transaction->transaction_amount, $grouped[$budgetId]);
        }

        return $grouped;
    }
}

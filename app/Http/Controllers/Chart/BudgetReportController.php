<?php
/**
 * BudgetReportController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Generator\Report\Category\MonthReportGenerator;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
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

    /** @var AccountRepositoryInterface */
    private $accountRepository;
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
                $this->generator         = app(GeneratorInterface::class);
                $this->budgetRepository  = app(BudgetRepositoryInterface::class);
                $this->accountRepository = app(AccountRepositoryInterface::class);

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
        /** @var bool $others */
        $others = intval($others) === 1;
        $cache  = new CacheProperties;
        $cache->addProperty('chart.budget.report.account-expense');
        $cache->addProperty($accounts);
        $cache->addProperty($budgets);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($others);
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $names     = [];
        $set       = $this->getExpenses($accounts, $budgets, $start, $end);
        $grouped   = $this->groupByOpposingAccount($set);
        $chartData = [];
        $total     = '0';

        foreach ($grouped as $accountId => $amount) {
            if (!isset($names[$accountId])) {
                $account           = $this->accountRepository->find(intval($accountId));
                $names[$accountId] = $account->name;
            }
            $amount                        = bcmul($amount, '-1');
            $total                         = bcadd($total, $amount);
            $chartData[$names[$accountId]] = $amount;
        }

        // also collect all transactions NOT in these budgets.
        if ($others) {
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class, [auth()->user()]);
            $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);
            $journals                                            = $collector->getJournals();
            $sum                                                 = strval($journals->sum('transaction_amount'));
            $sum                                                 = bcmul($sum, '-1');
            $sum                                                 = bcsub($sum, $total);
            $chartData[strval(trans('firefly.everything_else'))] = $sum;
        }

        $data = $this->generator->pieChart($chartData);
        $cache->store($data);

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
        /** @var bool $others */
        $others = intval($others) === 1;
        $cache  = new CacheProperties;
        $cache->addProperty('chart.budget.report.budget-expense');
        $cache->addProperty($accounts);
        $cache->addProperty($budgets);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($others);
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $names     = [];
        $set       = $this->getExpenses($accounts, $budgets, $start, $end);
        $grouped   = $this->groupByBudget($set);
        $total     = '0';
        $chartData = [];

        foreach ($grouped as $budgetId => $amount) {
            if (!isset($names[$budgetId])) {
                $budget           = $this->budgetRepository->find(intval($budgetId));
                $names[$budgetId] = $budget->name;
            }
            $amount                       = bcmul($amount, '-1');
            $total                        = bcadd($total, $amount);
            $chartData[$names[$budgetId]] = $amount;
        }

        // also collect all transactions NOT in these budgets.
        if ($others) {
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class, [auth()->user()]);
            $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);
            $journals                                            = $collector->getJournals();
            $sum                                                 = strval($journals->sum('transaction_amount'));
            $sum                                                 = bcmul($sum, '-1');
            $sum                                                 = bcsub($sum, $total);
            $chartData[strval(trans('firefly.everything_else'))] = $sum;
        }

        $data = $this->generator->pieChart($chartData);
        $cache->store($data);

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
            return Response::json($cache->get());
        }
        /** @var BudgetRepositoryInterface $repository */
        $repository   = app(BudgetRepositoryInterface::class);
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
        $allBudgetLimits = $repository->getAllBudgetLimits($start, $end);
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
                $chartData[$budget->id]['entries'][$label]          = round(bcmul($currentExpenses, '-1'), 2);
                $chartData[$budget->id . '-sum']['entries'][$label] = round(bcmul($sumOfExpenses[$budget->id], '-1'), 2);

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
        $collector = app(JournalCollectorInterface::class, [auth()->user()]);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setBudgets($budgets)->withOpposingAccount()->disableFilter();
        $accountIds   = $accounts->pluck('id')->toArray();
        $transactions = $collector->getJournals();
        $set          = MonthReportGenerator::filterExpenses($transactions, $accountIds);

        return $set;
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

    /**
     * @param Collection $set
     *
     * @return array
     */
    private function groupByOpposingAccount(Collection $set): array
    {
        $grouped = [];
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $accountId           = $transaction->opposing_account_id;
            $grouped[$accountId] = $grouped[$accountId] ?? '0';
            $grouped[$accountId] = bcadd($transaction->transaction_amount, $grouped[$accountId]);
        }

        return $grouped;
    }
}

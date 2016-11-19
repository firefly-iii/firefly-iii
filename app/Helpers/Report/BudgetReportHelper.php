<?php
/**
 * BudgetReportHelper.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Helpers\Report;


use Carbon\Carbon;
use DB;
use FireflyIII\Helpers\Collection\Budget as BudgetCollection;
use FireflyIII\Helpers\Collection\BudgetLine;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use stdClass;

/**
 * Class BudgetReportHelper
 *
 * @package FireflyIII\Helpers\Report
 */
class BudgetReportHelper implements BudgetReportHelperInterface
{
    /** @var BudgetRepositoryInterface */
    private $repository;

    /**
     * BudgetReportHelper constructor.
     *
     * @param BudgetRepositoryInterface $repository
     */
    public function __construct(BudgetRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function getBudgetPeriodReport(Carbon $start, Carbon $end, Collection $accounts): array
    {
        // get account ID's.
        $accountIds = $accounts->pluck('id')->toArray();

        // define period to group on:
        $sqlDateFormat = '%Y-%m-%d';
        // monthly report (for year)
        if ($start->diffInMonths($end) > 1) {
            $sqlDateFormat = '%Y-%m';
        }

        // yearly report (for multi year)
        if ($start->diffInMonths($end) > 12) {
            $sqlDateFormat = '%Y';
        }

        // build query.
        $query = TransactionJournal
            ::leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
            ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->leftJoin(
                'transactions', function (JoinClause $join) {
                $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('transactions.amount', '<', 0);
            }
            )
            ->whereNull('transaction_journals.deleted_at')
            ->whereNull('transactions.deleted_at')
            ->where('transaction_types.type', 'Withdrawal')
            ->where('transaction_journals.user_id', auth()->user()->id);

        if (count($accountIds) > 0) {
            $query->whereIn('transactions.account_id', $accountIds);
        }
        $query->groupBy(['budget_transaction_journal.budget_id', 'period_marker']);
        $queryResult = $query->get(
            [
                'budget_transaction_journal.budget_id',
                DB::raw('DATE_FORMAT(transaction_journals.date,"' . $sqlDateFormat . '") AS period_marker'),
                DB::raw('SUM(transactions.amount) as sum_of_period'),
            ]
        );

        $data    = [];
        $budgets = $this->repository->getBudgets();
        $periods = $this->listOfPeriods($start, $end);

        // do budget "zero"
        $emptyBudget       = new Budget;
        $emptyBudget->id   = 0;
        $emptyBudget->name = strval(trans('firefly.no_budget'));
        $budgets->push($emptyBudget);


        // get all budgets and years.
        foreach ($budgets as $budget) {
            $data[$budget->id] = [
                'name'    => $budget->name,
                'entries' => $this->filterAllAmounts($queryResult, $budget->id, $periods),
                'sum'     => '0',
            ];
        }
        // filter out empty ones and fill sum:
        $data = $this->filterBudgetPeriodReport($data);

        return $data;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return BudgetCollection
     */
    public function getBudgetReport(Carbon $start, Carbon $end, Collection $accounts): BudgetCollection
    {
        $object = new BudgetCollection;
        $set    = $this->repository->getBudgets();

        /** @var Budget $budget */
        foreach ($set as $budget) {
            $repetitions = $budget->limitrepetitions()->before($end)->after($start)->get();

            // no repetition(s) for this budget:
            if ($repetitions->count() == 0) {
                // spent for budget in time range:
                $spent = $this->repository->spentInPeriod(new Collection([$budget]), $accounts, $start, $end);

                if ($spent > 0) {
                    $budgetLine = new BudgetLine;
                    $budgetLine->setBudget($budget)->setOverspent($spent);
                    $object->addOverspent($spent)->addBudgetLine($budgetLine);
                }
                continue;
            }
            // one or more repetitions for budget:
            /** @var LimitRepetition $repetition */
            foreach ($repetitions as $repetition) {
                $data = $this->calculateExpenses($budget, $repetition, $accounts);

                $budgetLine = new BudgetLine;
                $budgetLine->setBudget($budget)->setRepetition($repetition)
                           ->setLeft($data['left'])->setSpent($data['expenses'])->setOverspent($data['overspent'])
                           ->setBudgeted(strval($repetition->amount));

                $object->addBudgeted(strval($repetition->amount))->addSpent($data['spent'])
                       ->addLeft($data['left'])->addOverspent($data['overspent'])->addBudgetLine($budgetLine);

            }

        }

        // stuff outside of budgets:

        $noBudget   = $this->repository->spentInPeriodWithoutBudget($accounts, $start, $end);
        $budgetLine = new BudgetLine;
        $budgetLine->setOverspent($noBudget)->setSpent($noBudget);
        $object->addOverspent($noBudget)->addBudgetLine($budgetLine);

        return $object;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function getBudgetsWithExpenses(Carbon $start, Carbon $end, Collection $accounts): Collection
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgets    = $repository->getActiveBudgets();

        $set = new Collection;
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $total = $repository->spentInPeriod(new Collection([$budget]), $accounts, $start, $end);
            if (bccomp($total, '0') === -1) {
                $set->push($budget);
            }
        }
        $set = $set->sortBy(
            function (Budget $budget) {
                return $budget->name;
            }
        );

        return $set;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function listOfPeriods(Carbon $start, Carbon $end): array
    {
        // define period to increment
        $increment = 'addDay';
        $format    = 'Y-m-d';
        // increment by month (for year)
        if ($start->diffInMonths($end) > 1) {
            $increment = 'addMonth';
            $format    = 'Y-m';
        }

        // increment by year (for multi year)
        if ($start->diffInMonths($end) > 12) {
            $increment = 'addYear';
            $format    = 'Y';
        }

        $begin   = clone $start;
        $entries = [];
        while ($begin < $end) {
            $formatted           = $begin->format($format);
            $entries[$formatted] = $formatted;

            $begin->$increment();
        }

        return $entries;

    }

    /**
     * @param Budget          $budget
     * @param LimitRepetition $repetition
     * @param Collection      $accounts
     *
     * @return array
     */
    private function calculateExpenses(Budget $budget, LimitRepetition $repetition, Collection $accounts): array
    {
        $array              = [];
        $expenses           = $this->repository->spentInPeriod(new Collection([$budget]), $accounts, $repetition->startdate, $repetition->enddate);
        $array['left']      = bccomp(bcadd($repetition->amount, $expenses), '0') === 1 ? bcadd($repetition->amount, $expenses) : '0';
        $array['spent']     = bccomp(bcadd($repetition->amount, $expenses), '0') === 1 ? $expenses : '0';
        $array['overspent'] = bccomp(bcadd($repetition->amount, $expenses), '0') === 1 ? '0' : bcadd($expenses, $repetition->amount);
        $array['expenses']  = $expenses;

        return $array;

    }

    /**
     * Filters entries from the result set generated by getBudgetPeriodReport
     *
     * @param Collection $set
     * @param int        $budgetId
     * @param array      $periods
     *
     * @return array
     */
    private function filterAllAmounts(Collection $set, int $budgetId, array $periods):array
    {
        $arr = [];
        foreach ($periods as $period) {
            /** @var stdClass $object */
            $result = $set->filter(
                function (TransactionJournal $object) use ($budgetId, $period) {
                    return strval($object->period_marker) === $period && $budgetId === intval($object->budget_id);
                }
            );
            $amount = '0';
            if (!is_null($result->first())) {
                $amount = $result->first()->sum_of_period;
            }

            $arr[$period] = $amount;
        }

        return $arr;
    }

    /**
     * Filters empty results from getBudgetPeriodReport
     *
     * @param array $data
     *
     * @return array
     */
    private function filterBudgetPeriodReport(array $data): array
    {
        /**
         * @var int   $budgetId
         * @var array $set
         */
        foreach ($data as $budgetId => $set) {
            $sum = '0';
            foreach ($set['entries'] as $amount) {
                $sum = bcadd($amount, $sum);
            }
            $data[$budgetId]['sum'] = $sum;
            if (bccomp('0', $sum) === 0) {
                unset($data[$budgetId]);
            }
        }

        return $data;
    }

}

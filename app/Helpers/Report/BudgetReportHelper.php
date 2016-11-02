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
use FireflyIII\Support\CacheProperties;
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength) // at 43, its ok.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's exactly 5.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function budgetYearOverview(Carbon $start, Carbon $end, Collection $accounts): Collection
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget-year');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get();
        }

        $current = clone $start;
        $return  = new Collection;
        $set     = $this->repository->getBudgets();
        $budgets = [];
        $spent   = [];
        $headers = $this->createYearHeaders($current, $end);

        /** @var Budget $budget */
        foreach ($set as $budget) {
            $id           = $budget->id;
            $budgets[$id] = $budget->name;
            $current      = clone $start;
            $budgetData   = $this->getBudgetSpentData($current, $end, $budget, $accounts);
            $sum          = $budgetData['sum'];
            $spent[$id]   = $budgetData['spent'];

            if (bccomp('0', $sum) === 0) {
                // not spent anything.
                unset($spent[$id]);
                unset($budgets[$id]);
            }
        }

        $return->put('headers', $headers);
        $return->put('budgets', $budgets);
        $return->put('spent', $spent);

        $cache->store($return);

        return $return;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function getBudgetMultiYear(Carbon $start, Carbon $end, Collection $accounts): array
    {
        $accountIds = $accounts->pluck('id')->toArray();
        $query      = TransactionJournal
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
        $query->groupBy(['budget_transaction_journal.budget_id', 'the_year']);
        $queryResult = $query->get(
            [
                'budget_transaction_journal.budget_id',
                DB::raw('DATE_FORMAT(transaction_journals.date,"%Y") AS the_year'),
                DB::raw('SUM(transactions.amount) as sum_of_period'),
            ]
        );

        $data    = [];
        $budgets = $this->repository->getBudgets();
        $years   = $this->listOfYears($start, $end);

        // do budget "zero"
        $emptyBudget       = new Budget;
        $emptyBudget->id   = 0;
        $emptyBudget->name = strval(trans('firefly.no_budget'));
        $budgets->push($emptyBudget);


        // get all budgets and years.
        foreach ($budgets as $budget) {
            $data[$budget->id] = [
                'name'    => $budget->name,
                'entries' => $this->filterAmounts($queryResult, $budget->id, $years),
                'sum'     => '0',
            ];
        }
        // filter out empty ones and fill sum:
        $data = $this->getBudgetMultiYearMeta($data);

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
    public function listOfYears(Carbon $start, Carbon $end): array
    {
        $begin = clone $start;
        $years = [];
        while ($begin < $end) {
            $years[] = $begin->year;
            $begin->addYear();
        }

        return $years;
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
     * @param Carbon $current
     * @param Carbon $end
     *
     * @return array
     */
    private function createYearHeaders(Carbon $current, Carbon $end): array
    {
        $headers = [];
        while ($current < $end) {
            $short           = $current->format('m-Y');
            $headers[$short] = $current->formatLocalized((string)trans('config.month'));
            $current->addMonth();
        }

        return $headers;
    }

    /**
     * @param Collection $set
     * @param int        $budgetId
     * @param array      $years
     *
     * @return array
     */
    private function filterAmounts(Collection $set, int $budgetId, array $years):array
    {
        $arr = [];
        foreach ($years as $year) {
            /** @var stdClass $object */
            $result = $set->filter(
                function (TransactionJournal $object) use ($budgetId, $year) {
                    return intval($object->the_year) === $year && $budgetId === intval($object->budget_id);
                }
            );
            $amount = '0';
            if (!is_null($result->first())) {
                $amount = $result->first()->sum_of_period;
            }

            $arr[$year] = $amount;
        }

        return $arr;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function getBudgetMultiYearMeta(array $data): array
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

    /**
     * @param Carbon     $current
     * @param Carbon     $end
     * @param Budget     $budget
     * @param Collection $accounts
     *
     * @return array
     */
    private function getBudgetSpentData(Carbon $current, Carbon $end, Budget $budget, Collection $accounts): array
    {
        $sum   = '0';
        $spent = [];
        while ($current < $end) {
            $currentEnd = clone $current;
            $currentEnd->endOfMonth();
            $format         = $current->format('m-Y');
            $budgetSpent    = $this->repository->spentInPeriod(new Collection([$budget]), $accounts, $current, $currentEnd);
            $spent[$format] = $budgetSpent;
            $sum            = bcadd($sum, $budgetSpent);
            $current->addMonth();
        }

        return [
            'spent' => $spent,
            'sum'   => $sum,
        ];
    }
}

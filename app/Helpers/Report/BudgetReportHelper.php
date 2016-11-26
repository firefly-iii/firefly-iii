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
use FireflyIII\Helpers\Collection\Budget as BudgetCollection;
use FireflyIII\Helpers\Collection\BudgetLine;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;

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
        $budgets = $this->repository->getBudgets();
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end);
        $collector->setBudgets($budgets);
        $transactions = $collector->getJournals();

        // this is the date format we need:
        // define period to group on:
        $carbonFormat = 'Y-m-d';
        // monthly report (for year)
        if ($start->diffInMonths($end) > 1) {
            $carbonFormat = 'Y-m';
        }

        // yearly report (for multi year)
        if ($start->diffInMonths($end) > 12) {
            $carbonFormat = 'Y';
        }

        // this is the set of transactions for this period
        // in these budgets. Now they must be grouped (manually)
        // id, period => amount
        $data = [];
        foreach ($transactions as $transaction) {
            $budgetId = max(intval($transaction->transaction_journal_budget_id), intval($transaction->transaction_budget_id));
            $date     = $transaction->date->format($carbonFormat);

            if (!isset($data[$budgetId])) {
                $data[$budgetId]['name']    = $this->getBudgetName($budgetId, $budgets);
                $data[$budgetId]['sum']     = '0';
                $data[$budgetId]['entries'] = [];
            }

            if (!isset($data[$budgetId]['entries'][$date])) {
                $data[$budgetId]['entries'][$date] = '0';
            }
            $data[$budgetId]['entries'][$date] = bcadd($data[$budgetId]['entries'][$date], $transaction->transaction_amount);
        }
        // and now the same for stuff without a budget:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end);
        $collector->setTypes([TransactionType::WITHDRAWAL]);
        $collector->withoutBudget();
        $transactions = $collector->getJournals();

        $data[0]['entries'] = [];
        $data[0]['name']    = strval(trans('firefly.no_budget'));
        $data[0]['sum']     = '0';

        foreach ($transactions as $transaction) {
            $date = $transaction->date->format($carbonFormat);

            if (!isset($data[0]['entries'][$date])) {
                $data[0]['entries'][$date] = '0';
            }
            $data[0]['entries'][$date] = bcadd($data[0]['entries'][$date], $transaction->transaction_amount);
        }

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

    /**
     * @param int        $budgetId
     * @param Collection $budgets
     *
     * @return string
     */
    private function getBudgetName(int $budgetId, Collection $budgets): string
    {

        $first = $budgets->filter(
            function (Budget $budget) use ($budgetId) {
                return $budgetId === $budget->id;
            }
        );
        if (!is_null($first->first())) {
            return $first->first()->name;
        }

        return '(unknown)';
    }

}

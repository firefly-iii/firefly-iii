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
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's exactly 5.
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
            if ($repetitions->count() == 0) { // no repetition(s) for this budget
                $spent = $this->repository->spentInPeriod(new Collection([$budget]), $accounts, $start, $end);// spent for budget in time range
                if ($spent > 0) {
                    $budgetLine = new BudgetLine;
                    $budgetLine->setBudget($budget)->setOverspent($spent);
                    $object->addOverspent($spent)->addBudgetLine($budgetLine);
                }
                continue;
            }
            /** @var LimitRepetition $repetition */
            foreach ($repetitions as $repetition) { // one or more repetitions for budget
                $data       = $this->calculateExpenses($budget, $repetition, $accounts);
                $budgetLine = new BudgetLine;
                $budgetLine->setBudget($budget)->setRepetition($repetition)
                           ->setLeft($data['left'])->setSpent($data['expenses'])->setOverspent($data['overspent'])
                           ->setBudgeted(strval($repetition->amount));

                $object->addBudgeted(strval($repetition->amount))->addSpent($data['spent'])
                       ->addLeft($data['left'])->addOverspent($data['overspent'])->addBudgetLine($budgetLine);

            }
        }
        $noBudget   = $this->repository->spentInPeriodWithoutBudget($accounts, $start, $end); // stuff outside of budgets
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
}

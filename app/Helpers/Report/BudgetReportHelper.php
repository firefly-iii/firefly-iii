<?php
declare(strict_types = 1);
/**
 * BudgetReportHelper.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Helpers\Report;


use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Budget as BudgetCollection;
use FireflyIII\Helpers\Collection\BudgetLine;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class BudgetReportHelper
 *
 * @package FireflyIII\Helpers\Report
 */
class BudgetReportHelper implements BudgetReportHelperInterface
{

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
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $set        = $repository->getBudgets();

        /** @var Budget $budget */
        foreach ($set as $budget) {
            $repetitions = $budget->limitrepetitions()->before($end)->after($start)->get();

            // no repetition(s) for this budget:
            if ($repetitions->count() == 0) {
                // spent for budget in time range:
                $spent = $repository->spentInPeriod(new Collection([$budget]), $accounts, $start, $end);

                if ($spent > 0) {
                    $budgetLine = new BudgetLine;
                    $budgetLine->setBudget($budget);
                    $budgetLine->setOverspent($spent);
                    $object->addOverspent($spent);
                    $object->addBudgetLine($budgetLine);
                }
                continue;
            }
            // one or more repetitions for budget:
            /** @var LimitRepetition $repetition */
            foreach ($repetitions as $repetition) {

                $budgetLine = new BudgetLine;
                $budgetLine->setBudget($budget);
                $budgetLine->setRepetition($repetition);
                $expenses  = $repository->spentInPeriod(new Collection([$budget]), $accounts, $repetition->startdate, $repetition->enddate);
                $left      = bccomp(bcadd($repetition->amount, $expenses), '0') === 1 ? bcadd($repetition->amount, $expenses) : '0';
                $spent     = bccomp(bcadd($repetition->amount, $expenses), '0') === 1 ? $expenses : '0';
                $overspent = bccomp(bcadd($repetition->amount, $expenses), '0') === 1 ? '0' : bcadd($expenses, $repetition->amount);

                $budgetLine->setLeft($left);
                $budgetLine->setSpent($expenses);
                $budgetLine->setOverspent($overspent);
                $budgetLine->setBudgeted($repetition->amount);

                $object->addBudgeted($repetition->amount);
                $object->addSpent($spent);
                $object->addLeft($left);
                $object->addOverspent($overspent);
                $object->addBudgetLine($budgetLine);

            }

        }

        // stuff outside of budgets:
        $outsideBudget = $repository->journalsInPeriodWithoutBudget($accounts, $start, $end);
        $noBudget      = '0';
        /** @var TransactionJournal $journal */
        foreach ($outsideBudget as $journal) {
            $noBudget = bcadd($noBudget, TransactionJournal::amount($journal));
        }
        $budgetLine = new BudgetLine;
        $budgetLine->setOverspent($noBudget);
        $budgetLine->setSpent($noBudget);
        $object->addOverspent($noBudget);
        $object->addBudgetLine($budgetLine);

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
     * Take the array as returned by CategoryRepositoryInterface::spentPerDay and CategoryRepositoryInterface::earnedByDay
     * and sum up everything in the array in the given range.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param array  $array
     *
     * @return string
     */
    protected function getSumOfRange(Carbon $start, Carbon $end, array $array)
    {
        $sum          = '0';
        $currentStart = clone $start; // to not mess with the original one
        $currentEnd   = clone $end; // to not mess with the original one

        while ($currentStart <= $currentEnd) {
            $date = $currentStart->format('Y-m-d');
            if (isset($array[$date])) {
                $sum = bcadd($sum, $array[$date]);
            }
            $currentStart->addDay();
        }

        return $sum;
    }
}

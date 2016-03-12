<?php
declare(strict_types = 1);
/**
 * BudgetReportHelper.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Helpers\Report;


use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Budget as BudgetCollection;
use FireflyIII\Helpers\Collection\BudgetLine;
use FireflyIII\Models\LimitRepetition;
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
        /** @var \FireflyIII\Repositories\Budget\BudgetRepositoryInterface $repository */
        $repository     = app('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $set            = $repository->getBudgets();
        $allRepetitions = $repository->getAllBudgetLimitRepetitions($start, $end);
        $allTotalSpent  = $repository->spentAllPerDayForAccounts($accounts, $start, $end);
        bcscale(2);

        foreach ($set as $budget) {

            $repetitions = $allRepetitions->filter(
                function (LimitRepetition $rep) use ($budget) {
                    return $rep->budget_id == $budget->id;
                }
            );
            $totalSpent  = $allTotalSpent[$budget->id] ?? [];

            // no repetition(s) for this budget:
            if ($repetitions->count() == 0) {

                $spent = array_sum($totalSpent);
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
                $expenses = $this->getSumOfRange($start, $end, $totalSpent);

                // 200 en -100 is 100, vergeleken met 0 === 1
                // 200 en -200 is 0, vergeleken met 0 === 0
                // 200 en -300 is -100, vergeleken met 0 === -1

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
        $noBudget   = $repository->getWithoutBudgetSum($accounts, $start, $end);
        $budgetLine = new BudgetLine;
        $budgetLine->setOverspent($noBudget);
        $budgetLine->setSpent($noBudget);
        $object->addOverspent($noBudget);
        $object->addBudgetLine($budgetLine);

        return $object;
    }

    /**
     * Take the array as returned by SingleCategoryRepositoryInterface::spentPerDay and SingleCategoryRepositoryInterface::earnedByDay
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
        bcscale(2);
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

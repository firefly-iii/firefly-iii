<?php

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Grumpydictator\Gchart\GChart;
use Illuminate\Support\Collection;
use Navigation;
use Preferences;
use Response;
use Session;

/**
 * Class BudgetController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class BudgetController extends Controller
{
    /**
     * @param GChart                    $chart
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function budget(GChart $chart, BudgetRepositoryInterface $repository, Budget $budget)
    {
        $chart->addColumn(trans('firefly.period'), 'date');
        $chart->addColumn(trans('firefly.spent'), 'number');


        $first = $repository->getFirstBudgetLimitDate($budget);
        $range = Preferences::get('viewRange', '1M')->data;
        $last  = Session::get('end', new Carbon);
        $final = clone $last;
        $final->addYears(2);
        $last = Navigation::endOfX($last, $range, $final);

        while ($first < $last) {
            $end = Navigation::addPeriod($first, $range, 0);

            $spent = $repository->spentInPeriodCorrected($budget, $first, $end);
            $chart->addRow($end, $spent);


            $first = Navigation::addPeriod($first, $range, 0);
        }

        $chart->generate();

        return Response::json($chart->getData());
    }

    /**
     * Shows the amount left in a specific budget limit.
     *
     * @param GChart                    $chart
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     * @param LimitRepetition           $repetition
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function budgetLimit(GChart $chart, BudgetRepositoryInterface $repository, Budget $budget, LimitRepetition $repetition)
    {
        $start = clone $repetition->startdate;
        $end   = $repetition->enddate;

        $chart->addColumn(trans('firefly.day'), 'date');
        $chart->addColumn(trans('firefly.left'), 'number');


        $amount = $repetition->amount;

        while ($start <= $end) {
            /*
             * Sum of expenses on this day:
             */
            $sum = $repository->expensesOnDayCorrected($budget, $start);
            $amount += $sum;
            $chart->addRow(clone $start, $amount);
            $start->addDay();
        }
        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * Shows a budget list with spent/left/overspent.
     *
     * @param GChart                    $chart
     * @param BudgetRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(GChart $chart, BudgetRepositoryInterface $repository)
    {
        $chart->addColumn(trans('firefly.budget'), 'string');
        $chart->addColumn(trans('firefly.left'), 'number');
        $chart->addColumn(trans('firefly.spent'), 'number');
        $chart->addColumn(trans('firefly.overspent'), 'number');

        $budgets    = $repository->getBudgets();
        $start      = Session::get('start', Carbon::now()->startOfMonth());
        $end        = Session::get('end', Carbon::now()->endOfMonth());
        $allEntries = new Collection;

        foreach ($budgets as $budget) {
            $repetitions = $repository->getBudgetLimitRepetitions($budget, $start, $end);
            if ($repetitions->count() == 0) {
                $expenses = $repository->spentInPeriodCorrected($budget, $start, $end, true);
                $allEntries->push([$budget->name, 0, 0, $expenses]);
                continue;
            }
            /** @var LimitRepetition $repetition */
            foreach ($repetitions as $repetition) {
                $expenses  = $repository->spentInPeriodCorrected($budget, $repetition->startdate, $repetition->enddate, true);
                $left      = $expenses < floatval($repetition->amount) ? floatval($repetition->amount) - $expenses : 0;
                $spent     = $expenses > floatval($repetition->amount) ? floatval($repetition->amount) : $expenses;
                $overspent = $expenses > floatval($repetition->amount) ? $expenses - floatval($repetition->amount) : 0;
                $allEntries->push(
                    [$budget->name . ' (' . $repetition->startdate->formatLocalized($this->monthAndDayFormat) . ')',
                        $left,
                        $spent,
                        $overspent
                    ]
                );
            }
        }

        $noBudgetExpenses = $repository->getWithoutBudgetSum($start, $end);
        $allEntries->push([trans('firefly.noBudget'), 0, 0, $noBudgetExpenses]);

        foreach ($allEntries as $entry) {
            if ($entry[1] != 0 || $entry[2] != 0 || $entry[3] != 0) {
                $chart->addRow($entry[0], $entry[1], $entry[2], $entry[3]);
            }
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * Show a yearly overview for a budget.
     *
     * @param GChart                    $chart
     * @param BudgetRepositoryInterface $repository
     * @param                           $year
     * @param bool                      $shared
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function year(GChart $chart, BudgetRepositoryInterface $repository, $year, $shared = false)
    {
        $start   = new Carbon($year . '-01-01');
        $end     = new Carbon($year . '-12-31');
        $shared  = $shared == 'shared' ? true : false;
        $budgets = $repository->getBudgets();

        // add columns:
        $chart->addColumn(trans('firefly.month'), 'date');
        foreach ($budgets as $budget) {
            $chart->addColumn($budget->name, 'number');
        }

        while ($start < $end) {
            // month is the current end of the period:
            $month = clone $start;
            $month->endOfMonth();
            // make a row:
            $row = [clone $start];

            // each budget, fill the row:
            foreach ($budgets as $budget) {
                $spent = $repository->spentInPeriodCorrected($budget, $start, $month, $shared);
                $row[] = $spent;
            }
            $chart->addRowArray($row);

            $start->addMonth();
        }

        $chart->generate();

        return Response::json($chart->getData());
    }
}

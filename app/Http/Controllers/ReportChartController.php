<?php

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Grumpydictator\Gchart\GChart;
use Response;

/**
 * Class ReportChartController
 *
 * @package FireflyIII\Http\Controllers
 */
class ReportChartController extends Controller
{


    public function yearBudgets(GChart $chart, BudgetRepositoryInterface $repository, $year, $shared = false)
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
                $spent = $repository->spentInPeriod($budget, $start, $month, $shared);
                $row[] = $spent;
            }
            $chart->addRowArray($row);

            $start->addMonth();
        }

        $chart->generate();

        return Response::json($chart->getData());
    }

    public function yearCategories(GChart $chart, $year, $shared = false)
    {
        $start  = new Carbon($year . '-01-01');
        $end    = new Carbon($year . '-12-31');
        $shared = $shared == 'shared' ? true : false;

        $chart->generate();

        return Response::json($chart->getData());
    }

    /**
     * Summarizes all income and expenses, per month, for a given year.
     *
     * @param GChart               $chart
     * @param ReportQueryInterface $query
     * @param                      $year
     * @param bool                 $shared
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function yearInOut(GChart $chart, ReportQueryInterface $query, $year, $shared = false)
    {
        // get start and end of year
        $start  = new Carbon($year . '-01-01');
        $end    = new Carbon($year . '-12-31');
        $shared = $shared == 'shared' ? true : false;

        $chart->addColumn(trans('firefly.month'), 'date');
        $chart->addColumn(trans('firefly.income'), 'number');
        $chart->addColumn(trans('firefly.expenses'), 'number');

        while ($start < $end) {
            $month = clone $start;
            $month->endOfMonth();
            // total income and total expenses:
            $incomeSum  = floatval($query->incomeInPeriod($start, $month, $shared)->sum('queryAmount'));
            $expenseSum = floatval($query->expenseInPeriod($start, $month, $shared)->sum('queryAmount')) * -1;

            $chart->addRow(clone $start, $incomeSum, $expenseSum);
            $start->addMonth();
        }
        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * Summarizes all income and expenses for a given year. Gives a total and an average.
     *
     * @param GChart               $chart
     * @param ReportQueryInterface $query
     * @param                      $year
     * @param bool                 $shared
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function yearInOutSummarized(GChart $chart, ReportQueryInterface $query, $year, $shared = false)
    {
        $start   = new Carbon($year . '-01-01');
        $end     = new Carbon($year . '-12-31');
        $shared  = $shared == 'shared' ? true : false;
        $income  = 0;
        $expense = 0;
        $count   = 0;

        $chart->addColumn(trans('firefly.summary'), 'string');
        $chart->addColumn(trans('firefly.income'), 'number');
        $chart->addColumn(trans('firefly.expenses'), 'number');

        while ($start < $end) {
            $month = clone $start;
            $month->endOfMonth();
            // total income and total expenses:
            $income += floatval($query->incomeInPeriod($start, $month, $shared)->sum('queryAmount'));
            $expense += floatval($query->expenseInPeriod($start, $month, $shared)->sum('queryAmount')) * -1;
            $count++;
            $start->addMonth();
        }

        // add total + average:
        $chart->addRow(trans('firefly.sum'), $income, $expense);
        $count = $count > 0 ? $count : 1;
        $chart->addRow(trans('firefly.average'), ($income / $count), ($expense / $count));

        $chart->generate();

        return Response::json($chart->getData());

    }
}
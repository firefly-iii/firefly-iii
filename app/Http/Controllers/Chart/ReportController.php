<?php

namespace FireflyIII\Http\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Http\Controllers\Controller;
use Grumpydictator\Gchart\GChart;
use Response;

/**
 * Class ReportController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class ReportController extends Controller
{


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
<?php

namespace FireflyIII\Http\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Response;
use Log;

/**
 * Class ReportController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class ReportController extends Controller
{

    /** @var  \FireflyIII\Generator\Chart\Report\ReportChartGenerator */
    protected $generator;

    /**
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app('FireflyIII\Generator\Chart\Report\ReportChartGenerator');
    }


    /**
     * Summarizes all income and expenses, per month, for a given year.
     *
     * @param ReportQueryInterface $query
     * @param                      $year
     * @param bool                 $shared
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function yearInOut(ReportQueryInterface $query, $year, $shared = false)
    {
        // get start and end of year
        $start  = new Carbon($year . '-01-01');
        $end    = new Carbon($year . '-12-31');
        $shared = $shared == 'shared' ? true : false;

        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('yearInOut');
        $cache->addProperty($year);
        $cache->addProperty($shared);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $entries = new Collection;
        while ($start < $end) {
            $month = clone $start;
            $month->endOfMonth();
            // total income and total expenses:
            $incomeSum  = $query->incomeInPeriodCorrected($start, $month, $shared)->sum('amount');
            $expenseSum = $query->expenseInPeriodCorrected($start, $month, $shared)->sum('amount');

            $entries->push([clone $start, $incomeSum, $expenseSum]);
            $start->addMonth();
        }

        $data = $this->generator->yearInOut($entries);
        $cache->store($data);

        return Response::json($data);

    }

    /**
     * Summarizes all income and expenses for a given year. Gives a total and an average.
     *
     * @param ReportQueryInterface $query
     * @param                      $year
     * @param bool                 $shared
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function yearInOutSummarized(ReportQueryInterface $query, $year, $shared = false)
    {

        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('yearInOutSummarized');
        $cache->addProperty($year);
        $cache->addProperty($shared);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $start   = new Carbon($year . '-01-01');
        $end     = new Carbon($year . '-12-31');
        $shared  = $shared == 'shared' ? true : false;
        $income  = '0';
        $expense = '0';
        $count   = 0;

        bcscale(2);

        while ($start < $end) {
            $month = clone $start;
            $month->endOfMonth();
            // total income and total expenses:
            $currentIncome = $query->incomeInPeriodCorrected($start, $month, $shared)->sum('amount');
            $currentExpense = $query->expenseInPeriodCorrected($start, $month, $shared)->sum('amount');
            
            Log::debug('Date ['.$month->format('M Y').']: income = ['.$income.' + '.$currentIncome.'], out = ['.$expense.' + '.$currentExpense.']');
            
            $income  = bcadd($income, $currentIncome);
            $expense = bcadd($expense, $currentExpense);
            
            
            
            
            
            $count++;
            $start->addMonth();
        }

        $data = $this->generator->yearInOutSummarized($income, $expense, $count);
        $cache->store($data);

        return Response::json($data);

    }
}

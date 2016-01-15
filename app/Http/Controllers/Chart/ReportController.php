<?php

namespace FireflyIII\Http\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Response;

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
     * @param                      $reportType
     * @param Carbon               $start
     * @param Carbon               $end
     * @param Collection           $accounts
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function yearInOut(ReportQueryInterface $query, $reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('yearInOut');
        $cache->addProperty($start);
        $cache->addProperty($reportType);
        $cache->addProperty($accounts);
        $cache->addProperty($end);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        // spent per month, and earned per month. For a specific set of accounts
        // grouped by month
        $spentArray  = $query->spentPerMonth($accounts, $start, $end);
        $earnedArray = $query->earnedPerMonth($accounts, $start, $end);

        if ($start->diffInMonths($end) > 12) {
            // data = method X
            $data = $this->multiYearInOut($earnedArray, $spentArray, $start, $end);
        } else {
            // data = method Y
            $data = $this->singleYearInOut($earnedArray, $spentArray, $start, $end);
        }

        $cache->store($data);

        return Response::json($data);

    }

    /**
     * Summarizes all income and expenses for a given year. Gives a total and an average.
     *
     * @param ReportQueryInterface $query
     * @param                      $reportType
     * @param Carbon               $start
     * @param Carbon               $end
     * @param Collection           $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function yearInOutSummarized(ReportQueryInterface $query, $reportType, Carbon $start, Carbon $end, Collection $accounts)
    {

        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('yearInOutSummarized');
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($reportType);
        $cache->addProperty($accounts);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        // spent per month, and earned per month. For a specific set of accounts
        // grouped by month
        $spentArray  = $query->spentPerMonth($accounts, $start, $end);
        $earnedArray = $query->earnedPerMonth($accounts, $start, $end);
        if ($start->diffInMonths($end) > 12) {
            // per year
            $data = $this->multiYearInOutSummarized($earnedArray, $spentArray, $start, $end);
        } else {
            // per month!
            $data = $this->singleYearInOutSummarized($earnedArray, $spentArray, $start, $end);
        }
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param array  $earned
     * @param array  $spent
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    protected function singleYearInOutSummarized(array $earned, array $spent, Carbon $start, Carbon $end)
    {
        $income  = '0';
        $expense = '0';
        $count   = 0;
        while ($start < $end) {
            $date           = $start->format('Y-m');
            $currentIncome  = isset($earned[$date]) ? $earned[$date] : 0;
            $currentExpense = isset($spent[$date]) ? ($spent[$date] * -1) : 0;
            $income         = bcadd($income, $currentIncome);
            $expense        = bcadd($expense, $currentExpense);

            $count++;
            $start->addMonth();
        }

        $data = $this->generator->yearInOutSummarized($income, $expense, $count);

        return $data;
    }

    /**
     * @param array  $earned
     * @param array  $spent
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    protected function multiYearInOutSummarized(array $earned, array $spent, Carbon $start, Carbon $end)
    {
        $income  = '0';
        $expense = '0';
        $count   = 0;
        while ($start < $end) {

            $currentIncome  = $this->pluckFromArray($start->year, $earned);
            $currentExpense = $this->pluckFromArray($start->year, $spent) * -1;
            $income         = bcadd($income, $currentIncome);
            $expense        = bcadd($expense, $currentExpense);

            $count++;
            $start->addYear();
        }

        $data = $this->generator->multiYearInOutSummarized($income, $expense, $count);

        return $data;
    }

    /**
     * @param array  $earned
     * @param array  $spent
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    protected function multiYearInOut(array $earned, array $spent, Carbon $start, Carbon $end)
    {
        $entries = new Collection;
        while ($start < $end) {

            $incomeSum  = $this->pluckFromArray($start->year, $earned);
            $expenseSum = $this->pluckFromArray($start->year, $spent) * -1;

            $entries->push([clone $start, $incomeSum, $expenseSum]);
            $start->addYear();
        }

        $data = $this->generator->multiYearInOut($entries);

        return $data;
    }

    /**
     * @param array  $earned
     * @param array  $spent
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    protected function singleYearInOut(array $earned, array $spent, Carbon $start, Carbon $end)
    {
        // per month? simply use each month.

        $entries = new Collection;
        while ($start < $end) {
            // total income and total expenses:
            $date       = $start->format('Y-m');
            $incomeSum  = isset($earned[$date]) ? $earned[$date] : 0;
            $expenseSum = isset($spent[$date]) ? ($spent[$date] * -1) : 0;

            $entries->push([clone $start, $incomeSum, $expenseSum]);
            $start->addMonth();
        }

        $data = $this->generator->yearInOut($entries);

        return $data;
    }

    /**
     * @param int   $year
     * @param array $set
     *
     * @return string
     */
    protected function pluckFromArray($year, array $set)
    {
        bcscale(2);
        $sum = '0';
        foreach ($set as $date => $amount) {
            if (substr($date, 0, 4) == $year) {
                $sum = bcadd($sum, $amount);
            }
        }

        return $sum;

    }
}

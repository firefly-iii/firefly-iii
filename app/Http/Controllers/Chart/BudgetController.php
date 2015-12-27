<?php

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\CacheProperties;
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

    /** @var  \FireflyIII\Generator\Chart\Budget\BudgetChartGenerator */
    protected $generator;

    /**
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app('FireflyIII\Generator\Chart\Budget\BudgetChartGenerator');
    }

    /**
     * @param BudgetRepositoryInterface $repository
     * @param                           $report_type
     * @param Carbon                    $start
     * @param Carbon                    $end
     * @param Collection                $accounts
     * @param Collection                $budgets
     */
    public function multiYear(BudgetRepositoryInterface $repository, $report_type, Carbon $start, Carbon $end, Collection $accounts, Collection $budgets)
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($report_type);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty($budgets);
        $cache->addProperty('multiYearBudget');

        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        /**
         *  budget
         *   year:
         *    spent: x
         *    budgeted: x
         *   year
         *    spent: x
         *    budgeted: x
         */
        $entries = new Collection;
        // go by budget, not by year.
        foreach ($budgets as $budget) {
            $entry = ['name' => '', 'spent' => [], 'budgeted' => []];

            $currentStart = clone $start;
            while ($currentStart < $end) {
                // fix the date:
                $currentEnd = clone $currentStart;
                $currentEnd->endOfYear();

                // get data:
                if (is_null($budget->id)) {
                    $name     = trans('firefly.noBudget');
                    $sum      = $repository->getWithoutBudgetSum($currentStart, $currentEnd);
                    $budgeted = 0;
                } else {
                    $name     = $budget->name;
                    $sum      = $repository->balanceInPeriod($budget, $currentStart, $currentEnd, $accounts);
                    $budgeted = $repository->getBudgetLimitRepetitions($budget, $currentStart, $currentEnd)->sum('amount');
                }

                // save to array:
                $year                     = $currentStart->year;
                $entry['name']            = $name;
                $entry['spent'][$year]    = ($sum * -1);
                $entry['budgeted'][$year] = $budgeted;

                // jump to next year.
                $currentStart = clone $currentEnd;
                $currentStart->addDay();
            }
            $entries->push($entry);
        }
        // generate chart with data:
        $data = $this->generator->multiYear($entries);

        return Response::json($data);

    }

    /**
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function budget(BudgetRepositoryInterface $repository, AccountRepositoryInterface $accountRepository, Budget $budget)
    {

        // dates and times
        $first = $repository->getFirstBudgetLimitDate($budget);
        $range = Preferences::get('viewRange', '1M')->data;
        $last  = Session::get('end', new Carbon);
        $final = clone $last;
        $final->addYears(2);
        $last     = Navigation::endOfX($last, $range, $final);
        $accounts = $accountRepository->getAccounts(['Default account', 'Asset account', 'Cash account']);


        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($first);
        $cache->addProperty($last);
        $cache->addProperty('budget');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $entries = new Collection;

        while ($first < $last) {
            $end = Navigation::addPeriod($first, $range, 0);
            $end->subDay();
            $chartDate = clone $end;
            $chartDate->startOfMonth();
            $spent = $repository->balanceInPeriod($budget, $first, $end, $accounts) * -1;
            $entries->push([$chartDate, $spent]);
            $first = Navigation::addPeriod($first, $range, 0);
        }

        $data = $this->generator->budget($entries);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Shows the amount left in a specific budget limit.
     *
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     * @param LimitRepetition           $repetition
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function budgetLimit(BudgetRepositoryInterface $repository, Budget $budget, LimitRepetition $repetition)
    {
        $start = clone $repetition->startdate;
        $end   = $repetition->enddate;
        bcscale(2);

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget');
        $cache->addProperty('limit');
        $cache->addProperty($budget->id);
        $cache->addProperty($repetition->id);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $entries = new Collection;
        $amount  = $repetition->amount;

        while ($start <= $end) {
            /*
             * Sum of expenses on this day:
             */
            $sum    = $repository->expensesOnDay($budget, $start);
            $amount = bcadd($amount, $sum);
            $entries->push([clone $start, $amount]);
            $start->addDay();
        }

        $data = $this->generator->budgetLimit($entries);
        $cache->store($data);

        return Response::json($data);

    }

    /**
     * Shows a budget list with spent/left/overspent.
     *
     * @param BudgetRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(BudgetRepositoryInterface $repository, AccountRepositoryInterface $accountRepository)
    {
        $start = Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->endOfMonth());

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget');
        $cache->addProperty('all');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $budgets    = $repository->getBudgetsAndLimitsInRange($start, $end);
        $allEntries = new Collection;
        $accounts   = $accountRepository->getAccounts(['Default account', 'Asset account', 'Cash account']);


        bcscale(2);

        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            // we already have amount, startdate and enddate.
            // if this "is" a limit repetition (as opposed to a budget without one entirely)
            // depends on whether startdate and enddate are null.
            if (is_null($budget->startdate) && is_null($budget->enddate)) {
                $name         = $budget->name . ' (' . $start->formatLocalized(trans('config.month')) . ')';
                $currentStart = clone $start;
                $currentEnd   = clone $end;
                $expenses     = $repository->balanceInPeriod($budget, $currentStart, $currentEnd, $accounts);
                $amount       = 0;
                $left         = 0;
                $spent        = $expenses;
                $overspent    = 0;
            } else {
                $name         = $budget->name . ' (' . $budget->startdate->formatLocalized(trans('config.month')) . ')';
                $currentStart = clone $budget->startdate;
                $currentEnd   = clone $budget->enddate;
                $expenses     = $repository->balanceInPeriod($budget, $currentStart, $currentEnd, $accounts);
                $amount       = $budget->amount;
                // smaller than 1 means spent MORE than budget allows.
                $left      = bccomp(bcadd($budget->amount, $expenses), '0') < 1 ? 0 : bcadd($budget->amount, $expenses);
                $spent     = bccomp(bcadd($budget->amount, $expenses), '0') < 1 ? $amount : $expenses;
                $overspent = bccomp(bcadd($budget->amount, $expenses), '0') < 1 ? bcadd($budget->amount, $expenses) : 0;
            }

            $allEntries->push([$name, $left, $spent, $overspent, $amount, $expenses]);
        }

        $noBudgetExpenses = $repository->getWithoutBudgetSum($start, $end) * -1;
        $allEntries->push([trans('firefly.noBudget'), 0, 0, $noBudgetExpenses, 0, 0]);
        $data = $this->generator->frontpage($allEntries);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Show a yearly overview for a budget.
     *
     * @param BudgetRepositoryInterface $repository
     * @param                           $year
     * @param bool                      $shared
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function year(BudgetRepositoryInterface $repository, $report_type, Carbon $start, Carbon $end, Collection $accounts)
    {
        $allBudgets = $repository->getBudgets();
        $budgets    = new Collection;

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($report_type);
        $cache->addProperty($accounts);
        $cache->addProperty('budget');
        $cache->addProperty('year');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        //         filter empty budgets:
        foreach ($allBudgets as $budget) {
            $spent = $repository->balanceInPeriod($budget, $start, $end, $accounts);
            if ($spent != 0) {
                $budgets->push($budget);
            }
        }

        $entries = new Collection;

        while ($start < $end) {
            // month is the current end of the period:
            $month = clone $start;
            $month->endOfMonth();
            $row = [clone $start];

            // each budget, fill the row:
            foreach ($budgets as $budget) {
                $spent = $repository->balanceInPeriod($budget, $start, $month, $accounts);
                $row[] = $spent * -1;
            }
            $entries->push($row);
            $start->endOfMonth()->addDay();
        }

        $data = $this->generator->year($budgets, $entries);
        $cache->store($data);

        return Response::json($data);
    }
}

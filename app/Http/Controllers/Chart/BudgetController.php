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
        $currentStart = clone $start;

        while ($currentStart < $end) {
            $currentEnd = clone $currentStart;
            $currentEnd->endOfYear();

            //echo 'now for '.$currentStart->format('Ymd').' to '.$currentEnd->format('Ymd').'<br>';
            //epository->balanceInPeriod()

            // do something for all budgets.

            // jump to next year.
            $currentStart = clone $currentEnd;
            $currentStart->addDay();

        }

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
            $spent = $repository->balanceInPeriodForList($budget, $first, $end, $accounts) * -1;
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
        $budgets    = $repository->getBudgets();
        $start      = Session::get('start', Carbon::now()->startOfMonth());
        $end        = Session::get('end', Carbon::now()->endOfMonth());
        $allEntries = new Collection;
        $accounts   = $accountRepository->getAccounts(['Default account', 'Asset account', 'Cash account']);

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget');
        $cache->addProperty('all');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        bcscale(2);

        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $repetitions = $repository->getBudgetLimitRepetitions($budget, $start, $end);
            if ($repetitions->count() == 0) {
                $expenses = $repository->balanceInPeriodForList($budget, $start, $end, $accounts) * -1;
                $allEntries->push([$budget->name, 0, 0, $expenses, 0, 0]);
                continue;
            }
            /** @var LimitRepetition $repetition */
            foreach ($repetitions as $repetition) {
                $expenses = $repository->balanceInPeriodForList($budget, $repetition->startdate, $repetition->enddate, $accounts) * -1;
                // $left can be less than zero.
                // $overspent can be more than zero ( = overspending)

                $left      = max(bcsub($repetition->amount, $expenses), 0); // limited at zero.
                $overspent = max(bcsub($expenses, $repetition->amount), 0); // limited at zero.
                $name      = $budget->name;

                // $spent is maxed to the repetition amount:
                $spent = $expenses > $repetition->amount ? $repetition->amount : $expenses;


                $allEntries->push([$name, $left, $spent, $overspent, $repetition->amount, $expenses]);
            }
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
        $cache->addProperty('budget');
        $cache->addProperty('year');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        // filter empty budgets:

        foreach ($allBudgets as $budget) {
            $spent = $repository->balanceInPeriodForList($budget, $start, $end, $accounts);
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
                $spent = $repository->balanceInPeriodForList($budget, $start, $month, $accounts);
                $row[] = $spent * -1;
            }
            $entries->push($row);
            $start->endOfMonth()->addDay();
        }

        $data = $this->generator->year($allBudgets, $entries);
        $cache->store($data);

        return Response::json($data);
    }
}

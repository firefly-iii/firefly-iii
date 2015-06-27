<?php

namespace FireflyIII\Http\Controllers\Chart;

use App;
use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\CacheProperties;
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

    /** @var  \FireflyIII\Generator\Chart\Budget\BudgetChartGenerator */
    protected $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = App::make('FireflyIII\Generator\Chart\Budget\BudgetChartGenerator');
    }

    /**
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function budget(BudgetRepositoryInterface $repository, Budget $budget)
    {

        // dates and times
        $first = $repository->getFirstBudgetLimitDate($budget);
        $range = Preferences::get('viewRange', '1M')->data;
        $last  = Session::get('end', new Carbon);
        $final = clone $last;
        $final->addYears(2);
        $last = Navigation::endOfX($last, $range, $final);

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
            $spent = $repository->spentInPeriodCorrected($budget, $first, $end);
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
     * @param GChart                    $chart
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
            $sum = $repository->expensesOnDayCorrected($budget, $start);
            $amount += $sum;
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
    public function frontpage(BudgetRepositoryInterface $repository)
    {
        $budgets    = $repository->getBudgets();
        $start      = Session::get('start', Carbon::now()->startOfMonth());
        $end        = Session::get('end', Carbon::now()->endOfMonth());
        $allEntries = new Collection;

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
                $expenses = $repository->spentInPeriodCorrected($budget, $start, $end, true);
                $allEntries->push([$budget->name, 0, 0, $expenses]);
                continue;
            }
            /** @var LimitRepetition $repetition */
            foreach ($repetitions as $repetition) {
                $expenses = $repository->spentInPeriodCorrected($budget, $repetition->startdate, $repetition->enddate, true);
                // $left can be less than zero.
                // $overspent can be more than zero ( = overspending)

                $left      = max(bcsub($repetition->amount, $expenses), 0); // limited at zero.
                $overspent = max(bcsub($expenses, $repetition->amount), 0); // limited at zero.
                $date      = $repetition->startdate->formatLocalized($this->monthAndDayFormat);
                $name      = $budget->name . ' (' . $date . ')';

                // $spent is maxed to the repetition amount:
                $spent = $expenses > $repetition->amount ? $repetition->amount : $expenses;


                $allEntries->push([$name, $left, $spent, $overspent]);
            }
        }

        $noBudgetExpenses = $repository->getWithoutBudgetSum($start, $end) * -1;
        $allEntries->push([trans('firefly.noBudget'), 0, 0, $noBudgetExpenses]);

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
    public function year(BudgetRepositoryInterface $repository, $year, $shared = false)
    {
        $start   = new Carbon($year . '-01-01');
        $end     = new Carbon($year . '-12-31');
        $shared  = $shared == 'shared' ? true : false;
        $budgets = $repository->getBudgets();

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget');
        $cache->addProperty('year');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $entries = new Collection;

        while ($start < $end) {
            // month is the current end of the period:
            $month = clone $start;
            $month->endOfMonth();
            $row = [clone $start];

            // each budget, fill the row:
            foreach ($budgets as $budget) {
                $spent = $repository->spentInPeriodCorrected($budget, $start, $month, $shared);
                $row[] = $spent;
            }
            $entries->push($row);
            $start->endOfMonth()->addDay();
        }

        $data = $this->generator->year($budgets, $entries);
        $cache->store($data);

        return Response::json($data);
    }
}

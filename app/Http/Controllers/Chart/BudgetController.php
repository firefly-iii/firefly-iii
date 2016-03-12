<?php
declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Navigation;
use Preferences;
use Response;

/**
 * Class BudgetController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class BudgetController extends Controller
{

    /** @var  \FireflyIII\Generator\Chart\Budget\BudgetChartGeneratorInterface */
    protected $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app('FireflyIII\Generator\Chart\Budget\BudgetChartGeneratorInterface');
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
        $last  = session('end', new Carbon);

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($first);
        $cache->addProperty($last);
        $cache->addProperty('budget');
        if ($cache->has()) {

            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $final = clone $last;
        $final->addYears(2);
        $last    = Navigation::endOfX($last, $range, $final);
        $entries = new Collection;
        // get all expenses:
        $spentArray = $repository->spentPerDay($budget, $first, $last);

        while ($first < $last) {

            // periodspecific dates:
            $currentStart = Navigation::startOfPeriod($first, $range);
            $currentEnd   = Navigation::endOfPeriod($first, $range);
            $spent        = $this->getSumOfRange($currentStart, $currentEnd, $spentArray);
            $entry        = [$first, ($spent * -1)];

            $entries->push($entry);
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

        $set     = $repository->getExpensesPerDay($budget, $start, $end);
        $entries = new Collection;
        $amount  = $repetition->amount;

        // get sum (har har)!
        while ($start <= $end) {
            $formatted = $start->format('Y-m-d');
            $filtered  = $set->filter(
                function (Budget $obj) use ($formatted) {
                    return $obj->date == $formatted;
                }
            );
            $sum       = is_null($filtered->first()) ? '0' : $filtered->first()->dailyAmount;

            /*
             * Sum of expenses on this day:
             */
            $amount = round(bcadd(strval($amount), $sum), 2);
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
     * @param ARI                       $accountRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(BudgetRepositoryInterface $repository, ARI $accountRepository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());

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
            $name = $budget->name;
            if (is_null($budget->startdate) && is_null($budget->enddate)) {
                $currentStart = clone $start;
                $currentEnd   = clone $end;
                $expenses     = $repository->balanceInPeriod($budget, $currentStart, $currentEnd, $accounts);
                $amount       = '0';
                $left         = '0';
                $spent        = $expenses;
                $overspent    = '0';
            } else {
                $currentStart = clone $budget->startdate;
                $currentEnd   = clone $budget->enddate;
                $expenses     = $repository->balanceInPeriod($budget, $currentStart, $currentEnd, $accounts);
                $amount       = $budget->amount;
                // smaller than 1 means spent MORE than budget allows.
                $left      = bccomp(bcadd($budget->amount, $expenses), '0') < 1 ? '0' : bcadd($budget->amount, $expenses);
                $spent     = bccomp(bcadd($budget->amount, $expenses), '0') < 1 ? bcmul($amount, '-1') : $expenses;
                $overspent = bccomp(bcadd($budget->amount, $expenses), '0') < 1 ? bcadd($budget->amount, $expenses) : '0';
            }

            $allEntries->push([$name, $left, $spent, $overspent, $amount, $expenses]);
        }

        $noBudgetExpenses = $repository->getWithoutBudgetSum($accounts, $start, $end);
        $allEntries->push([trans('firefly.noBudget'), '0', '0', $noBudgetExpenses, '0', '0']);
        $data = $this->generator->frontpage($allEntries);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     *
     * @param BudgetRepositoryInterface $repository
     * @param                           $reportType
     * @param Carbon                    $start
     * @param Carbon                    $end
     * @param Collection                $accounts
     * @param Collection                $budgets
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList) // need all parameters
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function multiYear(BudgetRepositoryInterface $repository, string $reportType, Carbon $start, Carbon $end, Collection $accounts, Collection $budgets)
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($reportType);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty($budgets);
        $cache->addProperty('multiYearBudget');

        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        /*
         * Get the budgeted amounts for each budgets in each year.
         */
        $budgetedSet   = $repository->getBudgetedPerYear($budgets, $start, $end);
        $budgetedArray = [];
        /** @var Budget $entry */
        foreach ($budgetedSet as $entry) {
            $budgetedArray[$entry->id][$entry->dateFormatted] = $entry->budgeted;
        }

        $set     = $repository->getBudgetsAndExpensesPerYear($budgets, $accounts, $start, $end);
        $entries = new Collection;
        // go by budget, not by year.
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $entry        = ['name' => '', 'spent' => [], 'budgeted' => []];
            $id           = $budget->id;
            $currentStart = clone $start;
            while ($currentStart < $end) {
                // fix the date:
                $currentEnd = clone $currentStart;
                $currentEnd->endOfYear();

                // basic information:
                $year          = $currentStart->year;
                $entry['name'] = $budget->name ?? (string)trans('firefly.noBudget');
                $spent         = 0;
                // this might be a good moment to collect no budget stuff.
                if (is_null($budget->id)) {
                    // get without budget sum in range:
                    $spent = $repository->getWithoutBudgetSum($accounts, $currentStart, $currentEnd) * -1;
                } else {
                    if (isset($set[$id]['entries'][$year])) {
                        $spent = $set[$id]['entries'][$year] * -1;
                    }
                }

                $budgeted                 = $budgetedArray[$id][$year] ?? '0';
                $entry['spent'][$year]    = $spent;
                $entry['budgeted'][$year] = round($budgeted, 2);


                // jump to next year.
                $currentStart = clone $currentEnd;
                $currentStart->addDay();
            }
            $entries->push($entry);
        }
        // generate chart with data:
        $data = $this->generator->multiYear($entries);
        $cache->store($data);

        return Response::json($data);

    }

    /**
     *
     * @param BudgetRepositoryInterface $repository
     * @param                           $reportType
     * @param Carbon                    $start
     * @param Carbon                    $end
     * @param Collection                $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function year(BudgetRepositoryInterface $repository, string $reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($reportType);
        $cache->addProperty($accounts);
        $cache->addProperty('budget');
        $cache->addProperty('year');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $budgetInformation = $repository->getBudgetsAndExpensesPerMonth($accounts, $start, $end);
        $budgets           = new Collection;
        $entries           = new Collection;

        /** @var array $row */
        foreach ($budgetInformation as $row) {
            $budgets->push($row['budget']);
        }

        while ($start < $end) {
            // month is the current end of the period:
            $month = clone $start;
            $month->endOfMonth();
            $row           = [clone $start];
            $dateFormatted = $start->format('Y-m');

            // each budget, check if there is an entry for this month:
            /** @var array $row */
            foreach ($budgetInformation as $budgetRow) {
                $spent = 0; // nothing spent.
                if (isset($budgetRow['entries'][$dateFormatted])) {
                    $spent = $budgetRow['entries'][$dateFormatted] * -1; // to fit array
                }
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

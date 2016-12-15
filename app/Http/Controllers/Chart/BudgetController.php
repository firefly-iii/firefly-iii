<?php
/**
 * BudgetController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Generator\Chart\Budget\BudgetChartGeneratorInterface;
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
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

    /** @var BudgetChartGeneratorInterface */
    protected $generator;

    /**
     * BudgetController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(BudgetChartGeneratorInterface::class);
    }

    /**
     * checked
     *
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function budget(BudgetRepositoryInterface $repository, Budget $budget)
    {
        $first = $repository->firstUseDate($budget);
        $range = Preferences::get('viewRange', '1M')->data;
        $last  = session('end', new Carbon);

        $cache = new CacheProperties();
        $cache->addProperty($first);
        $cache->addProperty($last);
        $cache->addProperty('chart.budget.budget');

        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $final = clone $last;
        $final->addYears(2);

        $budgetCollection = new Collection([$budget]);
        $last             = Navigation::endOfX($last, $range, $final); // not to overshoot.
        $entries          = [];
        while ($first < $last) {

            // periodspecific dates:
            $currentStart = Navigation::startOfPeriod($first, $range);
            $currentEnd   = Navigation::endOfPeriod($first, $range);
            // sub another day because reasons.
            $currentEnd->subDay();
            $spent            = $repository->spentInPeriod($budgetCollection, new Collection, $currentStart, $currentEnd);
            $format           = Navigation::periodShow($first, $range);
            $entries[$format] = bcmul($spent, '-1');
            $first            = Navigation::addPeriod($first, $range, 0);
        }

        /** @var GeneratorInterface $generator */
        $generator = app(GeneratorInterface::class);
        $data      = $generator->singleSet(strval(trans('firefly.spent')), $entries);

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
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.budget.budget.limit');
        $cache->addProperty($repetition->id);

        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $entries          = [];
        $amount           = $repetition->amount;
        $budgetCollection = new Collection([$budget]);
        while ($start <= $end) {
            $spent            = $repository->spentInPeriod($budgetCollection, new Collection, $start, $start);
            $amount           = bcadd($amount, $spent);
            $format           = $start->formatLocalized(strval(trans('config.month_and_day')));
            $entries[$format] = $amount;

            $start->addDay();
        }
        /** @var GeneratorInterface $generator */
        $generator = app(GeneratorInterface::class);
        $data      = $generator->singleSet(strval(trans('firefly.left')), $entries);
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
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.budget.frontpage');
        if ($cache->has()) {
            //return Response::json($cache->get());
        }
        $budgets     = $repository->getActiveBudgets();
        $repetitions = $repository->getAllBudgetLimitRepetitions($start, $end);
        $chartData   = [
            [
                'label'   => strval(trans('firefly.spent_in_budget')),
                'entries' => [],
                'type'    => 'bar',
            ],
            [
                'label'   => strval(trans('firefly.left_to_spend')),
                'entries' => [],
                'type'    => 'bar',
            ],
            [
                'label'   => strval(trans('firefly.overspent')),
                'entries' => [],
                'type'    => 'bar',
            ],
        ];


        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            // get relevant repetitions:
            $reps = $this->filterRepetitions($repetitions, $budget, $start, $end);

            if ($reps->count() === 0) {
                $row = $this->spentInPeriodSingle($repository, $budget, $start, $end);
                if (bccomp($row['spent'], '0') !== 0 || bccomp($row['repetition_left'], '0') !== 0) {
                    $chartData[0]['entries'][$row['name']] = bcmul($row['spent'], '-1');
                    $chartData[1]['entries'][$row['name']] = $row['repetition_left'];
                    $chartData[2]['entries'][$row['name']] = bcmul($row['repetition_overspent'], '-1');
                }
                continue;
            }
            $rows = $this->spentInPeriodMulti($repository, $budget, $reps);
            foreach ($rows as $row) {
                if (bccomp($row['spent'], '0') !== 0 || bccomp($row['repetition_left'], '0') !== 0) {
                    $chartData[0]['entries'][$row['name']] = bcmul($row['spent'], '-1');
                    $chartData[1]['entries'][$row['name']] = $row['repetition_left'];
                    $chartData[2]['entries'][$row['name']] = bcmul($row['repetition_overspent'], '-1');
                }
            }
            unset($rows, $row);

        }
        // for no budget:
        $row = $this->spentInPeriodWithout($start, $end);
        if (bccomp($row['spent'], '0') !== 0 || bccomp($row['repetition_left'], '0') !== 0) {
            $chartData[0]['entries'][$row['name']] = bcmul($row['spent'], '-1');
            $chartData[1]['entries'][$row['name']] = $row['repetition_left'];
            $chartData[2]['entries'][$row['name']] = bcmul($row['repetition_overspent'], '-1');
        }

        /** @var GeneratorInterface $generator */
        $generator = app(GeneratorInterface::class);
        $data      = $generator->multiSet($chartData);
        $cache->store($data);

        return Response::json($data);
    }


    /**
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     * @param Carbon                    $start
     * @param Carbon                    $end
     * @param Collection                $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function period(BudgetRepositoryInterface $repository, Budget $budget, Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty($budget->id);
        $cache->addProperty('chart.budget.period');
        if ($cache->has()) {
            //return Response::json($cache->get());
        }

        // get the expenses
        $budgeted = [];
        $periods  = Navigation::listOfPeriods($start, $end);
        $entries  = $repository->getBudgetPeriodReport(new Collection([$budget]), $accounts, $start, $end);
        $key      = Navigation::preferredCarbonFormat($start, $end);
        $range    = Navigation::preferredRangeFormat($start, $end);

        // get the budget limits (if any)
        $repetitions = $repository->getAllBudgetLimitRepetitions($start, $end);
        $current     = clone $start;
        while ($current < $end) {
            $currentStart     = Navigation::startOfPeriod($current, $range);
            $currentEnd       = Navigation::endOfPeriod($current, $range);
            $reps             = $repetitions->filter(
                function (LimitRepetition $repetition) use ($budget, $currentStart, $currentEnd) {
                    if ($repetition->budget_id === $budget->id && $repetition->startdate >= $currentStart && $repetition->enddate <= $currentEnd) {
                        return true;
                    }

                    return false;
                }
            );
            $index            = $currentStart->format($key);
            $budgeted[$index] = $reps->sum('amount');
            $currentEnd->addDay();
            $current = clone $currentEnd;
        }

        // join them into one set of data:
        $chartData = [
            [
                'label'   => strval(trans('firefly.spent')),
                'type'    => 'bar',
                'entries' => [],
            ],
            [
                'label'   => strval(trans('firefly.budgeted')),
                'type'    => 'bar',
                'entries' => [],
            ],
        ];

        foreach (array_keys($periods) as $period) {
            $label                           = $periods[$period];
            $spent                           = isset($entries[$budget->id]['entries'][$period]) ? $entries[$budget->id]['entries'][$period] : '0';
            $limit                           = isset($entries[$period]) ? $budgeted[$period] : 0;
            $chartData[0]['entries'][$label] = bcmul($spent, '-1');
            $chartData[1]['entries'][$label] = $limit;

        }
        /** @var GeneratorInterface $generator */
        $generator = app(GeneratorInterface::class);
        $data      = $generator->multiSet($chartData);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param BudgetRepositoryInterface $repository
     * @param Collection                $accounts
     * @param Carbon                    $start
     * @param Carbon                    $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function periodNoBudget(BudgetRepositoryInterface $repository, Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty('chart.budget.no-budget');
        if ($cache->has()) {
            // return Response::json($cache->get());
        }

        // the expenses:
        $periods   = Navigation::listOfPeriods($start, $end);
        $entries   = $repository->getNoBudgetPeriodReport($accounts, $start, $end);
        $chartData = [];

        // join them:
        foreach (array_keys($periods) as $period) {
            $label             = $periods[$period];
            $spent             = isset($entries['entries'][$period]) ? $entries['entries'][$period] : '0';
            $chartData[$label] = bcmul($spent, '-1');
        }
        /** @var GeneratorInterface $generator */
        $generator = app(GeneratorInterface::class);
        $data      = $generator->singleSet(strval(trans('firefly.spent')), $chartData);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param Collection $repetitions
     * @param Budget     $budget
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    private function filterRepetitions(Collection $repetitions, Budget $budget, Carbon $start, Carbon $end): Collection
    {

        return $repetitions->filter(
            function (LimitRepetition $repetition) use ($budget, $start, $end) {
                if ($repetition->startdate < $end && $repetition->enddate > $start && $repetition->budget_id === $budget->id) {
                    return true;
                }

                return false;
            }
        );
    }

    /**
     * Returns an array with the following values:
     * 0 =>
     *   'name' => name of budget + repetition
     *   'repetition_left' => left in budget repetition (always zero)
     *   'repetition_overspent' => spent more than budget repetition? (always zero)
     *   'spent' => actually spent in period for budget
     * 1 => (etc)
     *
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     * @param Collection                $repetitions
     *
     * @return array
     */
    private function spentInPeriodMulti(BudgetRepositoryInterface $repository, Budget $budget, Collection $repetitions): array
    {
        $return = [];
        $format = strval(trans('config.month_and_day'));
        $name   = $budget->name;
        /** @var LimitRepetition $repetition */
        foreach ($repetitions as $repetition) {
            $expenses = $repository->spentInPeriod(new Collection([$budget]), new Collection, $repetition->startdate, $repetition->enddate);

            if ($repetitions->count() > 1) {
                $name = $budget->name . ' ' . trans(
                        'firefly.between_dates',
                        ['start' => $repetition->startdate->formatLocalized($format), 'end' => $repetition->enddate->formatLocalized($format)]
                    );
            }
            $amount    = $repetition->amount;
            $left      = bccomp(bcadd($amount, $expenses), '0') < 1 ? '0' : bcadd($amount, $expenses);
            $spent     = bccomp(bcadd($amount, $expenses), '0') < 1 ? bcmul($amount, '-1') : $expenses;
            $overspent = bccomp(bcadd($amount, $expenses), '0') < 1 ? bcadd($amount, $expenses) : '0';
            $return[]  = [
                'name'                 => $name,
                'repetition_left'      => $left,
                'repetition_overspent' => $overspent,
                'spent'                => $spent,
            ];
            //$array     = [$name, $left, $spent, $overspent, $amount, $spent];
        }

        return $return;
    }

    /**
     * Returns an array with the following values:
     * 'name' => name of budget
     * 'repetition_left' => left in budget repetition (always zero)
     * 'repetition_overspent' => spent more than budget repetition? (always zero)
     * 'spent' => actually spent in period for budget
     *
     *
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     * @param Carbon                    $start
     * @param Carbon                    $end
     *
     * @return array
     */
    private function spentInPeriodSingle(BudgetRepositoryInterface $repository, Budget $budget, Carbon $start, Carbon $end): array
    {
        $spent = $repository->spentInPeriod(new Collection([$budget]), new Collection, $start, $end);
        $array = [
            'name'                 => $budget->name,
            'repetition_left'      => '0',
            'repetition_overspent' => '0',
            'spent'                => $spent,
        ];

        return $array;
    }

    /**
     * Returns an array with the following values:
     * 'name' => "no budget" in local language
     * 'repetition_left' => left in budget repetition (always zero)
     * 'repetition_overspent' => spent more than budget repetition? (always zero)
     * 'spent' => actually spent in period for budget
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    private function spentInPeriodWithout(Carbon $start, Carbon $end): array
    {
        // collector
        $collector = new JournalCollector(auth()->user());
        $types     = [TransactionType::WITHDRAWAL];
        $collector->setAllAssetAccounts()->setTypes($types)->setRange($start, $end)->withoutBudget();
        $journals = $collector->getJournals();
        $sum      = '0';
        /** @var Transaction $entry */
        foreach ($journals as $entry) {
            $sum = bcadd($entry->transaction_amount, $sum);
        }
        $array = [
            'name'                 => strval(trans('firefly.no_budget')),
            'repetition_left'      => '0',
            'repetition_overspent' => '0',
            'spent'                => $sum,
        ];

        return $array;
    }
}

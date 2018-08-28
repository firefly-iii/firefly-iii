<?php
/**
 * BudgetController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class BudgetController.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 */
class BudgetController extends Controller
{
    use DateCalculation, AugumentData;
    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    /** @var BudgetRepositoryInterface The budget repository */
    protected $repository;

    /**
     * BudgetController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                $this->generator  = app(GeneratorInterface::class);
                $this->repository = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }


    /**
     * Shows overview of a single budget.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Budget $budget
     *
     * @return JsonResponse
     */
    public function budget(Budget $budget): JsonResponse
    {
        /** @var Carbon $start */
        $start = $this->repository->firstUseDate($budget) ?? session('start', new Carbon);
        /** @var Carbon $end */
        $end   = session('end', new Carbon);
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.budget.budget');
        $cache->addProperty($budget->id);

        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        $step             = $this->calculateStep($start, $end); // depending on diff, do something with range of chart.
        $budgetCollection = new Collection([$budget]);
        $chartData        = [];
        $current          = clone $start;
        $current          = app('navigation')->startOfPeriod($current, $step);
        while ($end >= $current) {
            /** @var Carbon $currentEnd */
            $currentEnd = app('navigation')->endOfPeriod($current, $step);
            if ('1Y' === $step) {
                $currentEnd->subDay(); // @codeCoverageIgnore
            }
            $spent             = $this->repository->spentInPeriod($budgetCollection, new Collection, $current, $currentEnd);
            $label             = app('navigation')->periodShow($current, $step);
            $chartData[$label] = (float)bcmul($spent, '-1');
            $current           = clone $currentEnd;
            $current->addDay();
        }

        $data = $this->generator->singleSet((string)trans('firefly.spent'), $chartData);

        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Shows the amount left in a specific budget limit.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Budget      $budget
     * @param BudgetLimit $budgetLimit
     *
     * @return JsonResponse
     *
     * @throws FireflyException
     */
    public function budgetLimit(Budget $budget, BudgetLimit $budgetLimit): JsonResponse
    {
        if ($budgetLimit->budget->id !== $budget->id) {
            throw new FireflyException('This budget limit is not part of this budget.');
        }

        $start = clone $budgetLimit->start_date;
        $end   = clone $budgetLimit->end_date;
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.budget.budget.limit');
        $cache->addProperty($budgetLimit->id);
        $cache->addProperty($budget->id);

        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        $entries          = [];
        $amount           = $budgetLimit->amount;
        $budgetCollection = new Collection([$budget]);
        while ($start <= $end) {
            $spent            = $this->repository->spentInPeriod($budgetCollection, new Collection, $start, $start);
            $amount           = bcadd($amount, $spent);
            $format           = $start->formatLocalized((string)trans('config.month_and_day'));
            $entries[$format] = $amount;

            $start->addDay();
        }
        $data = $this->generator->singleSet((string)trans('firefly.left'), $entries);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Shows how much is spent per asset account.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Budget           $budget
     * @param BudgetLimit|null $budgetLimit
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function expenseAsset(Budget $budget, ?BudgetLimit $budgetLimit): JsonResponse
    {
        $budgetLimitId = null === $budgetLimit ? 0 : $budgetLimit->id;
        $cache         = new CacheProperties;
        $cache->addProperty($budget->id);
        $cache->addProperty($budgetLimitId);
        $cache->addProperty('chart.budget.expense-asset');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setBudget($budget);
        if (null !== $budgetLimit) {
            $collector->setRange($budgetLimit->start_date, $budgetLimit->end_date);
        }

        $transactions = $collector->getTransactions();
        $result       = [];
        $chartData    = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $assetId          = (int)$transaction->account_id;
            $result[$assetId] = $result[$assetId] ?? '0';
            $result[$assetId] = bcadd($transaction->transaction_amount, $result[$assetId]);
        }

        $names = $this->getAccountNames(array_keys($result));
        foreach ($result as $assetId => $amount) {
            $chartData[$names[$assetId]] = $amount;
        }

        $data = $this->generator->pieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Shows how much is spent per category.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Budget           $budget
     * @param BudgetLimit|null $budgetLimit
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function expenseCategory(Budget $budget, ?BudgetLimit $budgetLimit): JsonResponse
    {
        $budgetLimitId = null === $budgetLimit ? 0 : $budgetLimit->id;
        $cache         = new CacheProperties;
        $cache->addProperty($budget->id);
        $cache->addProperty($budgetLimitId);
        $cache->addProperty('chart.budget.expense-category');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setBudget($budget)->withCategoryInformation();
        if (null !== $budgetLimit) {
            $collector->setRange($budgetLimit->start_date, $budgetLimit->end_date);
        }

        $transactions = $collector->getTransactions();
        $result       = [];
        $chartData    = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $jrnlCatId           = (int)$transaction->transaction_journal_category_id;
            $transCatId          = (int)$transaction->transaction_category_id;
            $categoryId          = max($jrnlCatId, $transCatId);
            $result[$categoryId] = $result[$categoryId] ?? '0';
            $result[$categoryId] = bcadd($transaction->transaction_amount, $result[$categoryId]);
        }

        $names = $this->getCategoryNames(array_keys($result));
        foreach ($result as $categoryId => $amount) {
            $chartData[$names[$categoryId]] = $amount;
        }
        $data = $this->generator->pieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Shows how much is spent per expense account.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Budget           $budget
     * @param BudgetLimit|null $budgetLimit
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function expenseExpense(Budget $budget, ?BudgetLimit $budgetLimit): JsonResponse
    {
        $budgetLimitId = null === $budgetLimit ? 0 : $budgetLimit->id;
        $cache         = new CacheProperties;
        $cache->addProperty($budget->id);
        $cache->addProperty($budgetLimitId);
        $cache->addProperty('chart.budget.expense-expense');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setTypes([TransactionType::WITHDRAWAL])->setBudget($budget)->withOpposingAccount();
        if (null !== $budgetLimit) {
            $collector->setRange($budgetLimit->start_date, $budgetLimit->end_date);
        }

        $transactions = $collector->getTransactions();
        $result       = [];
        $chartData    = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $opposingId          = (int)$transaction->opposing_account_id;
            $result[$opposingId] = $result[$opposingId] ?? '0';
            $result[$opposingId] = bcadd($transaction->transaction_amount, $result[$opposingId]);
        }

        $names = $this->getAccountNames(array_keys($result));
        foreach ($result as $opposingId => $amount) {
            $name             = $names[$opposingId] ?? 'no name';
            $chartData[$name] = $amount;
        }

        $data = $this->generator->pieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Shows a budget list with spent/left/overspent.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function frontpage(): JsonResponse
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.budget.frontpage');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $budgets   = $this->repository->getActiveBudgets();
        $chartData = [
            ['label' => (string)trans('firefly.spent_in_budget'), 'entries' => [], 'type' => 'bar'],
            ['label' => (string)trans('firefly.left_to_spend'), 'entries' => [], 'type' => 'bar'],
            ['label' => (string)trans('firefly.overspent'), 'entries' => [], 'type' => 'bar'],
        ];

        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            // get relevant repetitions:
            $limits   = $this->repository->getBudgetLimits($budget, $start, $end);
            $expenses = $this->getExpensesForBudget($limits, $budget, $start, $end);

            foreach ($expenses as $name => $row) {
                $chartData[0]['entries'][$name] = $row['spent'];
                $chartData[1]['entries'][$name] = $row['left'];
                $chartData[2]['entries'][$name] = $row['overspent'];
            }
        }
        // for no budget:
        $spent = $this->spentInPeriodWithout($start, $end);
        $name  = (string)trans('firefly.no_budget');
        if (0 !== bccomp($spent, '0')) {
            $chartData[0]['entries'][$name] = bcmul($spent, '-1');
            $chartData[1]['entries'][$name] = '0';
            $chartData[2]['entries'][$name] = '0';
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Shows a budget overview chart (spent and budgeted).
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Budget     $budget
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return JsonResponse
     */
    public function period(Budget $budget, Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty($budget->id);
        $cache->addProperty('chart.budget.period');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $periods  = app('navigation')->listOfPeriods($start, $end);
        $entries  = $this->repository->getBudgetPeriodReport(new Collection([$budget]), $accounts, $start, $end); // get the expenses
        $budgeted = $this->getBudgetedInPeriod($budget, $start, $end);

        // join them into one set of data:
        $chartData = [
            ['label' => (string)trans('firefly.spent'), 'type' => 'bar', 'entries' => []],
            ['label' => (string)trans('firefly.budgeted'), 'type' => 'bar', 'entries' => []],
        ];

        foreach (array_keys($periods) as $period) {
            $label                           = $periods[$period];
            $spent                           = $entries[$budget->id]['entries'][$period] ?? '0';
            $limit                           = (int)($budgeted[$period] ?? 0);
            $chartData[0]['entries'][$label] = round(bcmul($spent, '-1'), 12);
            $chartData[1]['entries'][$label] = $limit;
        }
        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Shows a chart for transactions without a budget.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     */
    public function periodNoBudget(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty('chart.budget.no-budget');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        // the expenses:
        $periods   = app('navigation')->listOfPeriods($start, $end);
        $entries   = $this->repository->getNoBudgetPeriodReport($accounts, $start, $end);
        $chartData = [];

        // join them:
        foreach (array_keys($periods) as $period) {
            $label             = $periods[$period];
            $spent             = $entries['entries'][$period] ?? '0';
            $chartData[$label] = bcmul($spent, '-1');
        }
        $data = $this->generator->singleSet((string)trans('firefly.spent'), $chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Get the amount of money budgeted in a period.
     *
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    protected function getBudgetedInPeriod(Budget $budget, Carbon $start, Carbon $end): array // get data + augment with info
    {
        $key      = app('navigation')->preferredCarbonFormat($start, $end);
        $range    = app('navigation')->preferredRangeFormat($start, $end);
        $current  = clone $start;
        $budgeted = [];
        while ($current < $end) {
            /** @var Carbon $currentStart */
            $currentStart = app('navigation')->startOfPeriod($current, $range);
            /** @var Carbon $currentEnd */
            $currentEnd       = app('navigation')->endOfPeriod($current, $range);
            $budgetLimits     = $this->repository->getBudgetLimits($budget, $currentStart, $currentEnd);
            $index            = $currentStart->format($key);
            $budgeted[$index] = $budgetLimits->sum('amount');
            $currentEnd->addDay();
            $current = clone $currentEnd;
        }

        return $budgeted;
    }


    /**
     * Get the expenses for a budget in a date range.
     *
     * @param Collection $limits
     * @param Budget     $budget
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getExpensesForBudget(Collection $limits, Budget $budget, Carbon $start, Carbon $end): array // get data + augment with info
    {
        $return = [];
        if (0 === $limits->count()) {
            $spent = $this->repository->spentInPeriod(new Collection([$budget]), new Collection, $start, $end);
            if (0 !== bccomp($spent, '0')) {
                $return[$budget->name]['spent']     = bcmul($spent, '-1');
                $return[$budget->name]['left']      = 0;
                $return[$budget->name]['overspent'] = 0;
            }

            return $return;
        }

        $rows = $this->spentInPeriodMulti($budget, $limits);
        foreach ($rows as $name => $row) {
            if (0 !== bccomp($row['spent'], '0') || 0 !== bccomp($row['left'], '0')) {
                $return[$name] = $row;
            }
        }
        unset($rows);

        return $return;
    }

    /**
     *
     * Returns an array with the following values:
     * 0 =>
     *   'name' => name of budget + repetition
     *   'left' => left in budget repetition (always zero)
     *   'overspent' => spent more than budget repetition? (always zero)
     *   'spent' => actually spent in period for budget
     * 1 => (etc)
     *
     * @param Budget     $budget
     * @param Collection $limits
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     */
    protected function spentInPeriodMulti(Budget $budget, Collection $limits): array // get data + augment with info
    {
        $return = [];
        $format = (string)trans('config.month_and_day');
        $name   = $budget->name;
        /** @var BudgetLimit $budgetLimit */
        foreach ($limits as $budgetLimit) {
            $expenses = $this->repository->spentInPeriod(new Collection([$budget]), new Collection, $budgetLimit->start_date, $budgetLimit->end_date);
            $expenses = app('steam')->positive($expenses);

            if ($limits->count() > 1) {
                $name = $budget->name . ' ' . trans(
                        'firefly.between_dates',
                        [
                            'start' => $budgetLimit->start_date->formatLocalized($format),
                            'end'   => $budgetLimit->end_date->formatLocalized($format),
                        ]
                    );
            }
            $amount       = $budgetLimit->amount;
            $leftInLimit  = bcsub($amount, $expenses);
            $hasOverspent = bccomp($leftInLimit, '0') === -1;
            $left         = $hasOverspent ? '0' : bcsub($amount, $expenses);
            $spent        = $hasOverspent ? $amount : $expenses;
            $overspent    = $hasOverspent ? app('steam')->positive($leftInLimit) : '0';

            $return[$name] = [
                'left'      => $left,
                'overspent' => $overspent,
                'spent'     => $spent,
            ];
        }

        return $return;
    }

    /**
     * Returns an array with the following values:
     * 'name' => "no budget" in local language
     * 'repetition_left' => left in budget repetition (always zero)
     * 'repetition_overspent' => spent more than budget repetition? (always zero)
     * 'spent' => actually spent in period for budget.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    protected function spentInPeriodWithout(Carbon $start, Carbon $end): string // get data + augment with info
    {
        // collector
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $types     = [TransactionType::WITHDRAWAL];
        $collector->setAllAssetAccounts()->setTypes($types)->setRange($start, $end)->withoutBudget();
        $transactions = $collector->getTransactions();
        $sum      = '0';
        /** @var Transaction $entry */
        foreach ($transactions as $entry) {
            $sum = bcadd($entry->transaction_amount, $sum);
        }

        return $sum;
    }
}

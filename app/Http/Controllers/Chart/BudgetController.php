<?php

/**
 * BudgetController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\NoBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Chart\Budget\FrontpageChartGenerator;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class BudgetController.
 */
class BudgetController extends Controller
{
    use AugumentData;
    use DateCalculation;

    protected GeneratorInterface            $generator;
    protected OperationsRepositoryInterface $opsRepository;
    protected BudgetRepositoryInterface     $repository;
    private BudgetLimitRepositoryInterface  $blRepository;
    private NoBudgetRepositoryInterface     $nbRepository;

    /**
     * BudgetController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                $this->generator     = app(GeneratorInterface::class);
                $this->repository    = app(BudgetRepositoryInterface::class);
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->blRepository  = app(BudgetLimitRepositoryInterface::class);
                $this->nbRepository  = app(NoBudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Shows overview of a single budget.
     */
    public function budget(Budget $budget): JsonResponse
    {
        /** @var Carbon $start */
        $start          = $this->repository->firstUseDate($budget) ?? session('start', today(config('app.timezone')));

        /** @var Carbon $end */
        $end            = session('end', today(config('app.timezone')));
        $cache          = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.budget.budget');
        $cache->addProperty($budget->id);

        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $step           = $this->calculateStep($start, $end); // depending on diff, do something with range of chart.
        $collection     = new Collection([$budget]);
        $chartData      = [];
        $loopStart      = clone $start;
        $loopStart      = app('navigation')->startOfPeriod($loopStart, $step);
        $currencies     = [];
        $defaultEntries = [];
        while ($end >= $loopStart) {
            /** @var Carbon $loopEnd */
            $loopEnd                = app('navigation')->endOfPeriod($loopStart, $step);
            $spent                  = $this->opsRepository->sumExpenses($loopStart, $loopEnd, null, $collection);
            $label                  = trim(app('navigation')->periodShow($loopStart, $step));

            foreach ($spent as $row) {
                $currencyId                               = $row['currency_id'];
                $currencies[$currencyId] ??= $row; // don't mind the field 'sum'
                // also store this day's sum:
                $currencies[$currencyId]['spent'][$label] = $row['sum'];
            }
            $defaultEntries[$label] = 0;
            // set loop start to the next period:
            $loopStart              = clone $loopEnd;
            $loopStart->addSecond();
        }
        // loop all currencies:
        foreach ($currencies as $currencyId => $currency) {
            $chartData[$currencyId] = [
                'label'           => count($currencies) > 1 ? sprintf('%s (%s)', $budget->name, $currency['currency_name']) : $budget->name,
                'type'            => 'bar',
                'currency_symbol' => $currency['currency_symbol'],
                'currency_code'   => $currency['currency_code'],
                'entries'         => $defaultEntries,
            ];
            foreach ($currency['spent'] as $label => $spent) {
                $chartData[$currencyId]['entries'][$label] = bcmul($spent, '-1');
            }
        }
        $data           = $this->generator->multiSet(array_values($chartData));
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows the amount left in a specific budget limit.
     *
     * @throws FireflyException
     */
    public function budgetLimit(Budget $budget, BudgetLimit $budgetLimit): JsonResponse
    {
        if ($budgetLimit->budget->id !== $budget->id) {
            throw new FireflyException('This budget limit is not part of this budget.');
        }

        $start                                  = clone $budgetLimit->start_date;
        $end                                    = clone $budgetLimit->end_date;
        $cache                                  = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.budget.budget.limit');
        $cache->addProperty($budgetLimit->id);
        $cache->addProperty($budget->id);

        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $locale                                 = app('steam')->getLocale();
        $entries                                = [];
        $amount                                 = $budgetLimit->amount;
        $budgetCollection                       = new Collection([$budget]);
        $currency                               = $budgetLimit->transactionCurrency;
        while ($start <= $end) {
            $current          = clone $start;
            $expenses         = $this->opsRepository->sumExpenses($current, $current, null, $budgetCollection, $currency);
            $spent            = $expenses[$currency->id]['sum'] ?? '0';
            $amount           = bcadd($amount, $spent);
            $format           = $start->isoFormat((string) trans('config.month_and_day_js', [], $locale));
            $entries[$format] = $amount;

            $start->addDay();
        }
        $data                                   = $this->generator->singleSet((string) trans('firefly.left'), $entries);
        // add currency symbol from budget limit:
        $data['datasets'][0]['currency_symbol'] = $budgetLimit->transactionCurrency->symbol;
        $data['datasets'][0]['currency_code']   = $budgetLimit->transactionCurrency->code;
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows how much is spent per asset account.
     */
    public function expenseAsset(Budget $budget, ?BudgetLimit $budgetLimit = null): JsonResponse
    {
        /** @var GroupCollectorInterface $collector */
        $collector     = app(GroupCollectorInterface::class);
        $budgetLimitId = null === $budgetLimit ? 0 : $budgetLimit->id;
        $cache         = new CacheProperties();
        $cache->addProperty($budget->id);
        $cache->addProperty($budgetLimitId);
        $cache->addProperty('chart.budget.expense-asset');
        $start         = session('first', today(config('app.timezone'))->startOfYear());
        $end           = today();

        if (null !== $budgetLimit) {
            $start = $budgetLimit->start_date;
            $end   = $budgetLimit->end_date;
            $collector->setRange($budgetLimit->start_date, $budgetLimit->end_date)->setCurrency($budgetLimit->transactionCurrency);
        }
        $cache->addProperty($start);
        $cache->addProperty($end);

        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $collector->setRange($start, $end);
        $collector->setBudget($budget);
        $journals      = $collector->getExtractedJournals();
        $result        = [];
        $chartData     = [];

        // group by asset account ID:
        foreach ($journals as $journal) {
            $key                    = sprintf('%d-%d', (int) $journal['source_account_id'], $journal['currency_id']);
            $result[$key] ??= [
                'amount'          => '0',
                'currency_symbol' => $journal['currency_symbol'],
                'currency_code'   => $journal['currency_code'],
                'currency_name'   => $journal['currency_name'],
            ];
            $result[$key]['amount'] = bcadd($journal['amount'], $result[$key]['amount']);
        }

        $names         = $this->getAccountNames(array_keys($result));
        foreach ($result as $combinedId => $info) {
            $parts   = explode('-', $combinedId);
            $assetId = (int) $parts[0];
            $title   = sprintf('%s (%s)', $names[$assetId] ?? '(empty)', $info['currency_name']);
            $chartData[$title]
                     = [
                         'amount'          => $info['amount'],
                         'currency_symbol' => $info['currency_symbol'],
                         'currency_code'   => $info['currency_code'],
                     ];
        }

        $data          = $this->generator->multiCurrencyPieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows how much is spent per category.
     */
    public function expenseCategory(Budget $budget, ?BudgetLimit $budgetLimit = null): JsonResponse
    {
        /** @var GroupCollectorInterface $collector */
        $collector     = app(GroupCollectorInterface::class);
        $budgetLimitId = null === $budgetLimit ? 0 : $budgetLimit->id;
        $cache         = new CacheProperties();
        $cache->addProperty($budget->id);
        $cache->addProperty($budgetLimitId);
        $cache->addProperty('chart.budget.expense-category');
        $start         = session('first', today(config('app.timezone'))->startOfYear());
        $end           = today();
        if (null !== $budgetLimit) {
            $start = $budgetLimit->start_date;
            $end   = $budgetLimit->end_date;
            $collector->setCurrency($budgetLimit->transactionCurrency);
        }
        $cache->addProperty($start);
        $cache->addProperty($end);

        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $collector->setRange($start, $end);
        $collector->setBudget($budget)->withCategoryInformation();
        $journals      = $collector->getExtractedJournals();
        $result        = [];
        $chartData     = [];
        foreach ($journals as $journal) {
            $key                    = sprintf('%d-%d', $journal['category_id'], $journal['currency_id']);
            $result[$key] ??= [
                'amount'          => '0',
                'currency_symbol' => $journal['currency_symbol'],
                'currency_code'   => $journal['currency_code'],
                'currency_name'   => $journal['currency_name'],
            ];
            $result[$key]['amount'] = bcadd($journal['amount'], $result[$key]['amount']);
        }

        $names         = $this->getCategoryNames(array_keys($result));
        foreach ($result as $combinedId => $info) {
            $parts             = explode('-', $combinedId);
            $categoryId        = (int) $parts[0];
            $title             = sprintf('%s (%s)', $names[$categoryId] ?? '(empty)', $info['currency_name']);
            $chartData[$title] = [
                'amount'          => $info['amount'],
                'currency_symbol' => $info['currency_symbol'],
                'currency_code'   => $info['currency_code'],
            ];
        }
        $data          = $this->generator->multiCurrencyPieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows how much is spent per expense account.
     */
    public function expenseExpense(Budget $budget, ?BudgetLimit $budgetLimit = null): JsonResponse
    {
        /** @var GroupCollectorInterface $collector */
        $collector     = app(GroupCollectorInterface::class);
        $budgetLimitId = null === $budgetLimit ? 0 : $budgetLimit->id;
        $cache         = new CacheProperties();
        $cache->addProperty($budget->id);
        $cache->addProperty($budgetLimitId);
        $cache->addProperty('chart.budget.expense-expense');
        $start         = session('first', today(config('app.timezone'))->startOfYear());
        $end           = today();
        if (null !== $budgetLimit) {
            $start = $budgetLimit->start_date;
            $end   = $budgetLimit->end_date;
            $collector->setRange($budgetLimit->start_date, $budgetLimit->end_date)->setCurrency($budgetLimit->transactionCurrency);
        }
        $cache->addProperty($start);
        $cache->addProperty($end);

        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionType::WITHDRAWAL])->setBudget($budget)->withAccountInformation();
        $journals      = $collector->getExtractedJournals();
        $result        = [];
        $chartData     = [];

        /** @var array $journal */
        foreach ($journals as $journal) {
            $key                    = sprintf('%d-%d', $journal['destination_account_id'], $journal['currency_id']);
            $result[$key] ??= [
                'amount'          => '0',
                'currency_symbol' => $journal['currency_symbol'],
                'currency_code'   => $journal['currency_code'],
                'currency_name'   => $journal['currency_name'],
            ];
            $result[$key]['amount'] = bcadd($journal['amount'], $result[$key]['amount']);
        }

        $names         = $this->getAccountNames(array_keys($result));
        foreach ($result as $combinedId => $info) {
            $parts             = explode('-', $combinedId);
            $opposingId        = (int) $parts[0];
            $name              = $names[$opposingId] ?? 'no name';
            $title             = sprintf('%s (%s)', $name, $info['currency_name']);
            $chartData[$title] = [
                'amount'          => $info['amount'],
                'currency_symbol' => $info['currency_symbol'],
                'currency_code'   => $info['currency_code'],
            ];
        }

        $data          = $this->generator->multiCurrencyPieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows a budget list with spent/left/overspent.
     */
    public function frontpage(): JsonResponse
    {
        $start          = session('start', today(config('app.timezone'))->startOfMonth());
        $end            = session('end', today(config('app.timezone'))->endOfMonth());

        // chart properties for cache:
        $cache          = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.budget.frontpage');
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        $chartGenerator = app(FrontpageChartGenerator::class);
        $chartGenerator->setUser(auth()->user());
        $chartGenerator->setStart($start);
        $chartGenerator->setEnd($end);

        $chartData      = $chartGenerator->generate();
        $data           = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows a budget overview chart (spent and budgeted).
     *
     * Suppress warning because this method will be replaced by API calls.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function period(Budget $budget, TransactionCurrency $currency, Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        // chart properties for cache:
        $cache          = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty($budget->id);
        $cache->addProperty($currency->id);
        $cache->addProperty('chart.budget.period');
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $titleFormat    = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $preferredRange = app('navigation')->preferredRangeFormat($start, $end);
        $chartData      = [
            [
                'label'           => (string) trans('firefly.box_spent_in_currency', ['currency' => $currency->name]),
                'type'            => 'bar',
                'entries'         => [],
                'currency_symbol' => $currency->symbol,
                'currency_code'   => $currency->code,
            ],
            [
                'label'           => (string) trans('firefly.box_budgeted_in_currency', ['currency' => $currency->name]),
                'type'            => 'bar',
                'currency_symbol' => $currency->symbol,
                'currency_code'   => $currency->code,
                'entries'         => [],
            ],
        ];

        $currentStart   = clone $start;
        while ($currentStart <= $end) {
            $currentStart                    = app('navigation')->startOfPeriod($currentStart, $preferredRange);
            $title                           = $currentStart->isoFormat($titleFormat);
            $currentEnd                      = app('navigation')->endOfPeriod($currentStart, $preferredRange);

            // default limit is no limit:
            $chartData[0]['entries'][$title] = 0;

            // default spent is not spent at all.
            $chartData[1]['entries'][$title] = 0;

            // get budget limit in this period for this currency.
            $limit                           = $this->blRepository->find($budget, $currency, $currentStart, $currentEnd);
            if (null !== $limit) {
                $chartData[1]['entries'][$title] = app('steam')->bcround($limit->amount, $currency->decimal_places);
            }

            // get spent amount in this period for this currency.
            $sum                             = $this->opsRepository->sumExpenses($currentStart, $currentEnd, $accounts, new Collection([$budget]), $currency);
            $amount                          = app('steam')->positive($sum[$currency->id]['sum'] ?? '0');
            $chartData[0]['entries'][$title] = app('steam')->bcround($amount, $currency->decimal_places);

            $currentStart                    = clone $currentEnd;
            $currentStart->addDay()->startOfDay();
        }

        $data           = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows a chart for transactions without a budget.
     */
    public function periodNoBudget(TransactionCurrency $currency, Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        // chart properties for cache:
        $cache          = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty($currency->id);
        $cache->addProperty('chart.budget.no-budget');
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        // the expenses:
        $titleFormat    = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $chartData      = [];
        $currentStart   = clone $start;
        $preferredRange = app('navigation')->preferredRangeFormat($start, $end);
        while ($currentStart <= $end) {
            $currentEnd        = app('navigation')->endOfPeriod($currentStart, $preferredRange);
            $title             = $currentStart->isoFormat($titleFormat);
            $sum               = $this->nbRepository->sumExpenses($currentStart, $currentEnd, $accounts, $currency);
            $amount            = app('steam')->positive($sum[$currency->id]['sum'] ?? '0');
            $chartData[$title] = app('steam')->bcround($amount, $currency->decimal_places);
            $currentStart      = app('navigation')->addPeriod($currentStart, $preferredRange, 0);
        }

        $data           = $this->generator->singleSet((string) trans('firefly.spent'), $chartData);
        $cache->store($data);

        return response()->json($data);
    }
}

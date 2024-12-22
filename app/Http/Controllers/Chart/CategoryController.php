<?php

/**
 * CategoryController.php
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
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\NoCategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Chart\Category\FrontpageChartGenerator;
use FireflyIII\Support\Chart\Category\WholePeriodChartGenerator;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\ChartGeneration;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class CategoryController.
 */
class CategoryController extends Controller
{
    use AugumentData;
    use ChartGeneration;
    use DateCalculation;

    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    /**
     * CategoryController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(GeneratorInterface::class);
    }

    /**
     * Show an overview for a category for all time, per month/week/year.
     * TODO test method, for category refactor.
     *
     * @throws FireflyException
     */
    public function all(Category $category): JsonResponse
    {
        // cache results:
        $cache          = new CacheProperties();
        $cache->addProperty('chart.category.all');
        $cache->addProperty($category->id);
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        /** @var CategoryRepositoryInterface $repository */
        $repository     = app(CategoryRepositoryInterface::class);
        $start          = $repository->firstUseDate($category) ?? $this->getDate();
        $range          = app('navigation')->getViewRange(false);
        $start          = app('navigation')->startOfPeriod($start, $range);
        $end            = $this->getDate();

        /** @var WholePeriodChartGenerator $chartGenerator */
        $chartGenerator = app(WholePeriodChartGenerator::class);
        $chartData      = $chartGenerator->generate($category, $start, $end);
        $data           = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    private function getDate(): Carbon
    {
        return today(config('app.timezone'));
    }

    /**
     * Shows the category chart on the front page.
     * TODO test method for category refactor.
     */
    public function frontPage(): JsonResponse
    {
        $start              = session('start', today(config('app.timezone'))->startOfMonth());
        $end                = session('end', today(config('app.timezone'))->endOfMonth());
        // chart properties for cache:
        $cache              = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.category.frontpage');
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        $frontpageGenerator = new FrontpageChartGenerator($start, $end);
        $chartData          = $frontpageGenerator->generate();
        $data               = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Chart report.
     * TODO test method for category refactor.
     */
    public function reportPeriod(Category $category, Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.category.period');
        $cache->addProperty($accounts->pluck('id')->toArray());
        $cache->addProperty($category);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $data  = $this->reportPeriodChart($accounts, $start, $end, $category);

        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Generate report chart for either with or without category.
     */
    private function reportPeriodChart(Collection $accounts, Carbon $start, Carbon $end, ?Category $category): array
    {

        $income     = [];
        $expenses   = [];
        $categoryId = 0;
        if (null === $category) {
            /** @var NoCategoryRepositoryInterface $noCatRepository */
            $noCatRepository = app(NoCategoryRepositoryInterface::class);

            // this gives us all currencies
            $expenses        = $noCatRepository->listExpenses($start, $end, $accounts);
            $income          = $noCatRepository->listIncome($start, $end, $accounts);
        }

        if (null !== $category) {
            /** @var OperationsRepositoryInterface $opsRepository */
            $opsRepository = app(OperationsRepositoryInterface::class);
            $categoryId    = $category->id;
            // this gives us all currencies
            $collection    = new Collection([$category]);
            $expenses      = $opsRepository->listExpenses($start, $end, $accounts, $collection);
            $income        = $opsRepository->listIncome($start, $end, $accounts, $collection);
        }
        $currencies = array_unique(array_merge(array_keys($income), array_keys($expenses)));
        $periods    = app('navigation')->listOfPeriods($start, $end);
        $format     = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $chartData  = [];
        // make empty data array:
        // double foreach (bad) to make empty array:
        foreach ($currencies as $currencyId) {
            $currencyInfo = $expenses[$currencyId] ?? $income[$currencyId];
            $outKey       = sprintf('%d-out', $currencyId);
            $inKey        = sprintf('%d-in', $currencyId);
            $chartData[$outKey]
                          = [
                              'label'           => sprintf('%s (%s)', (string) trans('firefly.spent'), $currencyInfo['currency_name']),
                              'entries'         => [],
                              'type'            => 'bar',
                              'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
                          ];

            $chartData[$inKey]
                          = [
                              'label'           => sprintf('%s (%s)', (string) trans('firefly.earned'), $currencyInfo['currency_name']),
                              'entries'         => [],
                              'type'            => 'bar',
                              'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
                          ];
            // loop empty periods:
            foreach (array_keys($periods) as $period) {
                $label                                 = $periods[$period];
                $chartData[$outKey]['entries'][$label] = '0';
                $chartData[$inKey]['entries'][$label]  = '0';
            }
            // loop income and expenses for this category.:
            $outSet       = $expenses[$currencyId]['categories'][$categoryId] ?? ['transaction_journals' => []];
            foreach ($outSet['transaction_journals'] as $journal) {
                $amount                               = app('steam')->positive($journal['amount']);
                $date                                 = $journal['date']->isoFormat($format);
                $chartData[$outKey]['entries'][$date] ??= '0';

                $chartData[$outKey]['entries'][$date] = bcadd($amount, $chartData[$outKey]['entries'][$date]);
            }

            $inSet        = $income[$currencyId]['categories'][$categoryId] ?? ['transaction_journals' => []];
            foreach ($inSet['transaction_journals'] as $journal) {
                $amount                              = app('steam')->positive($journal['amount']);
                $date                                = $journal['date']->isoFormat($format);
                $chartData[$inKey]['entries'][$date] ??= '0';
                $chartData[$inKey]['entries'][$date] = bcadd($amount, $chartData[$inKey]['entries'][$date]);
            }
        }

        return $this->generator->multiSet($chartData);
    }

    /**
     * Chart for period for transactions without a category.
     * TODO test me.
     */
    public function reportPeriodNoCategory(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.category.period.no-cat');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $data  = $this->reportPeriodChart($accounts, $start, $end, null);

        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Chart for a specific period.
     * TODO test me, for category refactor.
     *
     * @throws FireflyException
     */
    public function specificPeriod(Category $category, Carbon $date): JsonResponse
    {
        $range          = app('navigation')->getViewRange(false);
        $start          = app('navigation')->startOfPeriod($date, $range);
        $end            = session()->get('end');
        if ($end < $start) {
            [$end, $start] = [$start, $end];
        }

        $cache          = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($category->id);
        $cache->addProperty('chart.category.period-chart');
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        /** @var WholePeriodChartGenerator $chartGenerator */
        $chartGenerator = app(WholePeriodChartGenerator::class);
        $chartData      = $chartGenerator->generate($category, $start, $end);
        $data           = $this->generator->multiSet($chartData);

        $cache->store($data);

        return response()->json($data);
    }
}

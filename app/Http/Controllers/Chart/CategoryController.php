<?php
/**
 * CategoryController.php
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
use Exception;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\NoCategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use FireflyIII\Support\CacheProperties;
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
    use DateCalculation, AugumentData, ChartGeneration;
    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    /**
     * CategoryController constructor.
     *
     * @codeCoverageIgnore
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
     * @param Category $category
     *
     * @return JsonResponse
     */
    public function all(Category $category): JsonResponse
    {
        // cache results:
        $cache = new CacheProperties;
        $cache->addProperty('chart.category.all');
        $cache->addProperty($category->id);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $start      = $repository->firstUseDate($category) ?? $this->getDate();
        $range      = app('preferences')->get('viewRange', '1M')->data;
        $start      = app('navigation')->startOfPeriod($start, $range);
        $end        = $this->getDate();

        //Log::debug(sprintf('Full range is %s to %s', $start->format('Y-m-d'), $end->format('Y-m-d')));

        /** @var WholePeriodChartGenerator $generator */
        $generator = app(WholePeriodChartGenerator::class);
        $chartData = $generator->generate($category, $start, $end);
        $data      = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Shows the category chart on the front page.
     * TODO test method, for category refactor.
     *
     * @return JsonResponse
     */
    public function frontPage(): JsonResponse
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.category.frontpage');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        // currency repos:
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        /** @var OperationsRepositoryInterface $opsRepository */
        $opsRepository = app(OperationsRepositoryInterface::class);

        /** @var NoCategoryRepositoryInterface $noCatRepository */
        $noCatRepository = app(NoCategoryRepositoryInterface::class);

        $chartData  = [];
        $currencies = [];
        $tempData   = [];
        $categories = $repository->getCategories();
        $accounts   = $accountRepository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);

        /** @var Category $category */
        foreach ($categories as $category) {
            $collection = new Collection([$category]);
            $spent      = $opsRepository->sumExpenses($start, $end, $accounts, $collection);
            //$spentArray = $opsRepository->spentInPeriodPerCurrency(new Collection([$category]), $accounts, $start, $end);
            foreach ($spent as $currency) {
                $currencyId              = $currency['currency_id'];
                $currencies[$currencyId] = $currencies[$currencyId] ?? [
                        'currency_id'             => $currencyId,
                        'currency_name'           => $currency['currency_name'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_code'           => $currency['currency_code'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                    ];
                $tempData[]              = [
                    'name'        => $category->name,
                    'sum'         => $currency['sum'],
                    'sum_float'   => round($currency['sum'], $currency['currency_decimal_places']),
                    'currency_id' => $currencyId,
                ];
            }
        }

        // no category per currency:
        $noCategory = $noCatRepository->sumExpenses($start, $end);

        foreach ($noCategory as $currency) {
            $currencyId              = $currency['currency_id'];
            $currencies[$currencyId] = $currencies[$currencyId] ?? [
                    'currency_id'             => $currency['currency_id'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_code'           => $currency['currency_code'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                ];
            $tempData[]              = [
                'name'        => trans('firefly.no_category'),
                'sum'         => $currency['sum'],
                'sum_float'   => round($currency['sum'], $currency['currency_decimal_places']),
                'currency_id' => $currency['currency_id'],
            ];
        }

        // sort temp array by amount.
        $amounts = array_column($tempData, 'sum_float');
        array_multisort($amounts, SORT_DESC, $tempData);

        // loop all found currencies and build the data array for the chart.
        /** @var array $currency */
        foreach ($currencies as $currency) {
            $dataSet                             = [
                'label'           => sprintf('%s (%s)', (string)trans('firefly.spent'), $currency['currency_name']),
                'type'            => 'bar',
                'currency_symbol' => $currency['currency_symbol'],
                'entries'         => $this->expandNames($tempData),
            ];
            $chartData[$currency['currency_id']] = $dataSet;
        }

        // loop temp data and place data in correct array:
        foreach ($tempData as $entry) {
            $currencyId                               = $entry['currency_id'];
            $name                                     = $entry['name'];
            $chartData[$currencyId]['entries'][$name] = bcmul($entry['sum'], '-1');
        }
        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Chart report.
     * TODO test method, for category refactor.
     *
     * @param Category   $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     */
    public function reportPeriod(Category $category, Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.category.period');
        $cache->addProperty($accounts->pluck('id')->toArray());
        $cache->addProperty($category);
        if ($cache->has()) {
            return response()->json($cache->get());// @codeCoverageIgnore
        }

        /** @var OperationsRepositoryInterface $opsRepository */
        $opsRepository = app(OperationsRepositoryInterface::class);


        // this gives us all currencies
        $collection = new Collection([$category]);
        $expenses   = $opsRepository->listExpenses($start, $end, null, $collection);
        $income     = $opsRepository->listIncome($start, $end, null, $collection);
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
                'label'           => sprintf('%s (%s)', (string)trans('firefly.spent'), $currencyInfo['currency_name']),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
            ];

            $chartData[$inKey]
                = [
                'label'           => sprintf('%s (%s)', (string)trans('firefly.earned'), $currencyInfo['currency_name']),
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
            $outSet = $expenses[$currencyId]['categories'][$category->id] ?? ['transaction_journals' => []];
            foreach ($outSet['transaction_journals'] as $journal) {
                $amount                               = app('steam')->positive($journal['amount']);
                $date                                 = $journal['date']->formatLocalized($format);
                $chartData[$outKey]['entries'][$date] = $chartData[$outKey]['entries'][$date] ?? '0';

                $chartData[$outKey]['entries'][$date] = bcadd($amount, $chartData[$outKey]['entries'][$date]);
            }

            $inSet = $income[$currencyId]['categories'][$category->id] ?? ['transaction_journals' => []];
            foreach ($inSet['transaction_journals'] as $journal) {
                $amount                              = app('steam')->positive($journal['amount']);
                $date                                = $journal['date']->formatLocalized($format);
                $chartData[$inKey]['entries'][$date] = $chartData[$inKey]['entries'][$date] ?? '0';
                $chartData[$inKey]['entries'][$date] = bcadd($amount, $chartData[$inKey]['entries'][$date]);
            }
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Chart for period for transactions without a category.
     * TODO test me.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     */
    public function reportPeriodNoCategory(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.category.period.no-cat');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        /** @var NoCategoryRepositoryInterface $noCatRepository */
        $noCatRepository = app(NoCategoryRepositoryInterface::class);

        // this gives us all currencies
        $expenses   = $noCatRepository->listExpenses($start, $end, $accounts);
        $income     = $noCatRepository->listIncome($start, $end, $accounts);
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
                'label'           => sprintf('%s (%s)', (string)trans('firefly.spent'), $currencyInfo['currency_name']),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
            ];

            $chartData[$inKey]
                = [
                'label'           => sprintf('%s (%s)', (string)trans('firefly.earned'), $currencyInfo['currency_name']),
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
            // loop income and expenses:
            $outSet = $expenses[$currencyId] ?? ['transaction_journals' => []];
            foreach ($outSet['transaction_journals'] as $journal) {
                $amount                               = app('steam')->positive($journal['amount']);
                $date                                 = $journal['date']->formatLocalized($format);
                $chartData[$outKey]['entries'][$date] = $chartData[$outKey]['entries'][$date] ?? '0';
                $chartData[$outKey]['entries'][$date] = bcadd($amount, $chartData[$outKey]['entries'][$date]);
            }

            $inSet = $income[$currencyId] ?? ['transaction_journals' => []];
            foreach ($inSet['transaction_journals'] as $journal) {
                $amount                              = app('steam')->positive($journal['amount']);
                $date                                = $journal['date']->formatLocalized($format);
                $chartData[$inKey]['entries'][$date] = $chartData[$inKey]['entries'][$date] ?? '0';
                $chartData[$inKey]['entries'][$date] = bcadd($amount, $chartData[$inKey]['entries'][$date]);
            }
        }
        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Chart for a specific period.
     * TODO test method, for category refactor.
     *
     * @param Category                    $category
     * @param                             $date
     *
     * @return JsonResponse
     */
    public function specificPeriod(Category $category, Carbon $date): JsonResponse
    {
        $range = app('preferences')->get('viewRange', '1M')->data;
        $start = app('navigation')->startOfPeriod($date, $range);
        $end   = session()->get('end');
        if ($end < $start) {
            [$end, $start] = [$start, $end]; // @codeCoverageIgnore
        }

        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($category->id);
        $cache->addProperty('chart.category.period-chart');


        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        /** @var GeneratorInterface $generator */
        $generator = app(GeneratorInterface::class);

        /** @var WholePeriodChartGenerator $chartGenerator */
        $chartGenerator = app(WholePeriodChartGenerator::class);
        $chartData      = $chartGenerator->generate($category, $start, $end);
        $data           = $generator->multiSet($chartData);

        $cache->store($data);

        return response()->json($data);
    }

    /**
     * @return Carbon
     */
    private function getDate(): Carbon
    {
        $carbon = null;
        try {
            $carbon = new Carbon;
        } catch (Exception $e) {
            $e->getMessage();
        }

        return $carbon;
    }
}

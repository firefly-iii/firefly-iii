<?php
/**
 * CategoryController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Navigation;
use Preferences;
use Response;

/**
 * Class CategoryController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class CategoryController extends Controller
{
    /** @var  GeneratorInterface */
    protected $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(GeneratorInterface::class);
    }

    /**
     * Show an overview for a category for all time, per month/week/year.
     *
     * @param CategoryRepositoryInterface $repository
     * @param AccountRepositoryInterface  $accountRepository
     * @param Category                    $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function all(CategoryRepositoryInterface $repository, AccountRepositoryInterface $accountRepository, Category $category)
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.category.all');
        $cache->addProperty($category->id);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $start = $repository->firstUseDate($category);

        if (is_null($first)) {
            $start = new Carbon;
        }

        $range     = Preferences::get('viewRange', '1M')->data;
        $start     = Navigation::startOfPeriod($start, $range);
        $end       = new Carbon;
        $accounts  = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $chartData = [
            [
                'label'   => strval(trans('firefly.spent')),
                'entries' => [],
                'type'    => 'bar',
            ],
            [
                'label'   => strval(trans('firefly.earned')),
                'entries' => [],
                'type'    => 'bar',
            ],
            [
                'label'   => strval(trans('firefly.sum')),
                'entries' => [],
                'type'    => 'line',
                'fill'    => false,
            ],
        ];

        while ($start <= $end) {
            $currentEnd                      = Navigation::endOfPeriod($start, $range);
            $spent                           = $repository->spentInPeriod(new Collection([$category]), $accounts, $start, $currentEnd);
            $earned                          = $repository->earnedInPeriod(new Collection([$category]), $accounts, $start, $currentEnd);
            $sum                             = bcadd($spent, $earned);
            $label                           = Navigation::periodShow($start, $range);
            $chartData[0]['entries'][$label] = round(bcmul($spent, '-1'), 12);
            $chartData[1]['entries'][$label] = round($earned, 12);
            $chartData[2]['entries'][$label] = round($sum, 12);
            $start                           = Navigation::addPeriod($start, $range, 0);
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return Response::json($data);

    }

    /**
     * @param CategoryRepositoryInterface $repository
     * @param AccountRepositoryInterface  $accountRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function frontpage(CategoryRepositoryInterface $repository, AccountRepositoryInterface $accountRepository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.category.frontpage');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        $chartData  = [];
        $categories = $repository->getCategories();
        $accounts   = $accountRepository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        /** @var Category $category */
        foreach ($categories as $category) {
            $spent = $repository->spentInPeriod(new Collection([$category]), $accounts, $start, $end);
            if (bccomp($spent, '0') === -1) {
                $chartData[$category->name] = bcmul($spent, '-1');
            }
        }

        $chartData[strval(trans('firefly.no_category'))] = bcmul($repository->spentInPeriodWithoutCategory(new Collection, $start, $end), '-1');

        // sort
        arsort($chartData);

        $data = $this->generator->singleSet(strval(trans('firefly.spent')), $chartData);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     * @param Collection                  $accounts
     * @param Carbon                      $start
     * @param Carbon                      $end
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function reportPeriod(CategoryRepositoryInterface $repository, Category $category, Collection $accounts, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.category.period');
        $cache->addProperty($accounts->pluck('id')->toArray());
        $cache->addProperty($category);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $expenses  = $repository->periodExpenses(new Collection([$category]), $accounts, $start, $end);
        $income    = $repository->periodIncome(new Collection([$category]), $accounts, $start, $end);
        $periods   = Navigation::listOfPeriods($start, $end);
        $chartData = [
            [
                'label'   => strval(trans('firefly.spent')),
                'entries' => [],
                'type'    => 'bar',
            ],
            [
                'label'   => strval(trans('firefly.earned')),
                'entries' => [],
                'type'    => 'bar',
            ],
            [
                'label'   => strval(trans('firefly.sum')),
                'entries' => [],
                'type'    => 'line',
                'fill'    => false,
            ],
        ];

        foreach (array_keys($periods) as $period) {
            $label                           = $periods[$period];
            $spent                           = $expenses[$category->id]['entries'][$period] ?? '0';
            $earned                          = $income[$category->id]['entries'][$period] ?? '0';
            $sum                             = bcadd($spent, $earned);
            $chartData[0]['entries'][$label] = round(bcmul($spent, '-1'), 12);
            $chartData[1]['entries'][$label] = round($earned, 12);
            $chartData[2]['entries'][$label] = round($sum, 12);
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param CategoryRepositoryInterface $repository
     * @param Collection                  $accounts
     * @param Carbon                      $start
     * @param Carbon                      $end
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function reportPeriodNoCategory(CategoryRepositoryInterface $repository, Collection $accounts, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.category.period.no-cat');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $expenses  = $repository->periodExpensesNoCategory($accounts, $start, $end);
        $income    = $repository->periodIncomeNoCategory($accounts, $start, $end);
        $periods   = Navigation::listOfPeriods($start, $end);
        $chartData = [
            [
                'label'   => strval(trans('firefly.spent')),
                'entries' => [],
                'type'    => 'bar',
            ],
            [
                'label'   => strval(trans('firefly.earned')),
                'entries' => [],
                'type'    => 'bar',
            ],
            [
                'label'   => strval(trans('firefly.sum')),
                'entries' => [],
                'type'    => 'line',
                'fill'    => false,
            ],
        ];

        foreach (array_keys($periods) as $period) {
            $label                           = $periods[$period];
            $spent                           = $expenses['entries'][$period] ?? '0';
            $earned                          = $income['entries'][$period] ?? '0';
            $sum                             = bcadd($spent, $earned);
            $chartData[0]['entries'][$label] = bcmul($spent, '-1');
            $chartData[1]['entries'][$label] = $earned;
            $chartData[2]['entries'][$label] = $sum;

        }
        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @param                             $date
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function specificPeriod(CategoryRepositoryInterface $repository, Category $category, Carbon $date)
    {
        $range = Preferences::get('viewRange', '1M')->data;
        $start = Navigation::startOfPeriod($date, $range);
        $end   = Navigation::endOfPeriod($date, $range);
        $data  = $this->makePeriodChart($repository, $category, $start, $end);

        return Response::json($data);
    }


    /**
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     * @param Carbon                      $start
     * @param Carbon                      $end
     *
     * @return array
     */
    private function makePeriodChart(CategoryRepositoryInterface $repository, Category $category, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($category->id);
        $cache->addProperty('chart.category.period-chart');

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        // chart data
        $chartData = [
            [
                'label'   => strval(trans('firefly.spent')),
                'entries' => [],
                'type'    => 'bar',
            ],
            [
                'label'   => strval(trans('firefly.earned')),
                'entries' => [],
                'type'    => 'bar',
            ],
            [
                'label'   => strval(trans('firefly.sum')),
                'entries' => [],
                'type'    => 'line',
                'fill'    => false,
            ],
        ];

        while ($start <= $end) {
            $spent  = $repository->spentInPeriod(new Collection([$category]), $accounts, $start, $start);
            $earned = $repository->earnedInPeriod(new Collection([$category]), $accounts, $start, $start);
            $sum    = bcadd($spent, $earned);
            $label  = trim(Navigation::periodShow($start, '1D'));

            $chartData[0]['entries'][$label] = round(bcmul($spent, '-1'), 12);
            $chartData[1]['entries'][$label] = round($earned, 12);
            $chartData[2]['entries'][$label] = round($sum, 12);


            $start->addDay();
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return $data;

    }

}

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

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Generator\Chart\Category\CategoryChartGeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface as CRI;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Navigation;
use Preferences;
use Response;
use stdClass;

/**
 * Class CategoryController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class CategoryController extends Controller
{
    /** @var  CategoryChartGeneratorInterface */
    protected $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(CategoryChartGeneratorInterface::class);
    }


    /**
     * Show an overview for a category for all time, per month/week/year.
     *
     * @param CRI                        $repository
     * @param AccountRepositoryInterface $accountRepository
     * @param Category                   $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function all(CRI $repository, AccountRepositoryInterface $accountRepository, Category $category)
    {
        $start              = $repository->firstUseDate($category);
        $range              = Preferences::get('viewRange', '1M')->data;
        $start              = Navigation::startOfPeriod($start, $range);
        $categoryCollection = new Collection([$category]);
        $end                = new Carbon;
        $entries            = new Collection;
        $cache              = new CacheProperties;
        $accounts           = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('all');
        $cache->addProperty('categories');
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        while ($start <= $end) {
            $currentEnd = Navigation::endOfPeriod($start, $range);
            $spent      = $repository->spentInPeriod($categoryCollection, $accounts, $start, $currentEnd);
            $earned     = $repository->earnedInPeriod($categoryCollection, $accounts, $start, $currentEnd);
            $date       = Navigation::periodShow($start, $range);
            $entries->push([clone $start, $date, $spent, $earned]);
            $start = Navigation::addPeriod($start, $range, 0);
        }
        $entries = $entries->reverse();
        $entries = $entries->slice(0, 48);
        $entries = $entries->reverse();
        $data    = $this->generator->all($entries);
        $cache->store($data);

        return Response::json($data);

    }

    /**
     * @param CRI      $repository
     * @param Category $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function currentPeriod(CRI $repository, Category $category)
    {
        $start = clone session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        $data  = $this->makePeriodChart($repository, $category, $start, $end);

        return Response::json($data);
    }

    /**
     * @param CRI                        $repository
     * @param AccountRepositoryInterface $accountRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function frontpage(CRI $repository, AccountRepositoryInterface $accountRepository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category');
        $cache->addProperty('frontpage');
        if ($cache->has()) {
            return Response::json($cache->get());
        }
        $categories = $repository->getCategories();
        $accounts   = $accountRepository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        $set        = new Collection;
        /** @var Category $category */
        foreach ($categories as $category) {
            $spent = $repository->spentInPeriod(new Collection([$category]), $accounts, $start, $end);
            if (bccomp($spent, '0') === -1) {
                $category->spent = $spent;
                $set->push($category);
            }
        }
        // this is a "fake" entry for the "no category" entry.
        $entry        = new stdClass;
        $entry->name  = trans('firefly.no_category');
        $entry->spent = $repository->spentInPeriodWithoutCategory(new Collection, $start, $end);
        $set->push($entry);

        $set  = $set->sortBy('spent');
        $data = $this->generator->frontpage($set);
        $cache->store($data);

        return Response::json($data);

    }

    /**
     * @param CRI                         $repository
     * @param Category                    $category
     *
     * @param                             $date
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function specificPeriod(CRI $repository, Category $category, $date)
    {
        $carbon = new Carbon($date);
        $range  = Preferences::get('viewRange', '1M')->data;
        $start  = Navigation::startOfPeriod($carbon, $range);
        $end    = Navigation::endOfPeriod($carbon, $range);
        $data   = $this->makePeriodChart($repository, $category, $start, $end);

        return Response::json($data);
    }

    /**
     * @param CRI      $repository
     * @param Category $category
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return array
     */
    private function makePeriodChart(CRI $repository, Category $category, Carbon $start, Carbon $end)
    {
        $categoryCollection = new Collection([$category]);
        $cache              = new CacheProperties;

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);

        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty($category->id);
        $cache->addProperty('specific-period');


        if ($cache->has()) {
            return $cache->get();
        }
        $entries = new Collection;
        while ($start <= $end) {
            $spent  = $repository->spentInPeriod($categoryCollection, $accounts, $start, $start);
            $earned = $repository->earnedInPeriod($categoryCollection, $accounts, $start, $start);
            $date   = Navigation::periodShow($start, '1D');
            $entries->push([clone $start, $date, $spent, $earned]);
            $start->addDay();
        }

        $data = $this->generator->period($entries);
        $cache->store($data);

        return $data;

    }

}

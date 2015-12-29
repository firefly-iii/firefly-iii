<?php

namespace FireflyIII\Http\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\SingleCategoryRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Navigation;
use Preferences;
use Response;
use Session;

/**
 * Class CategoryController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class CategoryController extends Controller
{
    /** @var  \FireflyIII\Generator\Chart\Category\CategoryChartGenerator */
    protected $generator;

    /**
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app('FireflyIII\Generator\Chart\Category\CategoryChartGenerator');
    }


    /**
     * Show an overview for a category for all time, per month/week/year.
     *
     * @param SingleCategoryRepositoryInterface $repository
     * @param Category                          $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function all(SingleCategoryRepositoryInterface $repository, Category $category)
    {
        // oldest transaction in category:
        $start   = $repository->getFirstActivityDate($category);
        $range   = Preferences::get('viewRange', '1M')->data;
        $start   = Navigation::startOfPeriod($start, $range);
        $end     = new Carbon;
        $entries = new Collection;


        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('all');
        $cache->addProperty('categories');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        while ($start <= $end) {
            $currentEnd = Navigation::endOfPeriod($start, $range);
            $spent      = $repository->spentInPeriod($category, $start, $currentEnd);
            $earned     = $repository->earnedInPeriod($category, $start, $currentEnd);
            $date       = Navigation::periodShow($start, $range);
            $entries->push([clone $start, $date, $spent, $earned]);
            $start = Navigation::addPeriod($start, $range, 0);
        }
        // limit the set to the last 40:
        $entries = $entries->reverse();
        $entries = $entries->slice(0, 48);
        $entries = $entries->reverse();

        $data = $this->generator->all($entries);
        $cache->store($data);

        return Response::json($data);


    }

    /**
     * Show this month's category overview.
     *
     * @param CategoryRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(CategoryRepositoryInterface $repository)
    {

        $start = Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->endOfMonth());

        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category');
        $cache->addProperty('frontpage');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $array = $repository->getCategoriesAndExpenses($start, $end);
        // sort by callback:
        uasort(
            $array,
            function ($left, $right) {
                if ($left['sum'] == $right['sum']) {
                    return 0;
                }

                return ($left['sum'] < $right['sum']) ? -1 : 1;
            }
        );
        $set  = new Collection($array);
        $data = $this->generator->frontpage($set);
        $cache->store($data);

        return Response::json($data);

    }

    /**
     * @param CategoryRepositoryInterface $repository
     * @param                             $reportType
     * @param Carbon                      $start
     * @param Carbon                      $end
     * @param Collection                  $accounts
     * @param Collection                  $categories
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function multiYear($reportType, Carbon $start, Carbon $end, Collection $accounts, Collection $categories)
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        /** @var SingleCategoryRepositoryInterface $singleRepository */
        $singleRepository = app('FireflyIII\Repositories\Category\SingleCategoryRepositoryInterface');

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($reportType);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty($categories);
        $cache->addProperty('multiYearCategory');

        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        /**
         *  category
         *   year:
         *    spent: x
         *    earned: x
         *   year
         *    spent: x
         *    earned: x
         */
        $entries = new Collection;
        // go by budget, not by year.
        /** @var Category $category */
        foreach ($categories as $category) {
            $entry = ['name' => '', 'spent' => [], 'earned' => []];

            $currentStart = clone $start;
            while ($currentStart < $end) {
                // fix the date:
                $currentEnd = clone $currentStart;
                $currentEnd->endOfYear();

                // get data:
                if (is_null($category->id)) {
                    $name   = trans('firefly.noCategory');
                    $spent  = $repository->spentNoCategoryForAccounts($accounts, $currentStart, $currentEnd);
                    $earned = $repository->earnedNoCategoryForAccounts($accounts, $currentStart, $currentEnd);
                } else {
                    $name   = $category->name;
                    $spent  = $singleRepository->spentInPeriodForAccounts($category, $accounts, $currentStart, $currentEnd);
                    $earned = $singleRepository->earnedInPeriodForAccounts($category, $accounts, $currentStart, $currentEnd);
                }

                // save to array:
                $year                   = $currentStart->year;
                $entry['name']          = $name;
                $entry['spent'][$year]  = ($spent * -1);
                $entry['earned'][$year] = $earned;

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
     * @param SingleCategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function currentPeriod(SingleCategoryRepositoryInterface $repository, Category $category)
    {
        $start = clone Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->endOfMonth());

        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($category->id);
        $cache->addProperty('category');
        $cache->addProperty('currentPeriod');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        $entries = new Collection;


        while ($start <= $end) {
            $spent  = $repository->spentOnDaySum($category, $start);
            $earned = $repository->earnedOnDaySum($category, $start);
            $date   = Navigation::periodShow($start, '1D');
            $entries->push([clone $start, $date, $spent, $earned]);
            $start->addDay();
        }

        $data = $this->generator->period($entries);
        $cache->store($data);

        return Response::json($data);


    }

    /**
     * @param SingleCategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @param                             $date
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function specificPeriod(SingleCategoryRepositoryInterface $repository, Category $category, $date)
    {
        $carbon = new Carbon($date);
        $range  = Preferences::get('viewRange', '1M')->data;
        $start  = Navigation::startOfPeriod($carbon, $range);
        $end    = Navigation::endOfPeriod($carbon, $range);

        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($category->id);
        $cache->addProperty('category');
        $cache->addProperty('specificPeriod');
        $cache->addProperty($date);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        $entries = new Collection;


        while ($start <= $end) {
            $spent   = $repository->spentOnDaySum($category, $start);
            $earned  = $repository->earnedOnDaySum($category, $start);
            $theDate = Navigation::periodShow($start, '1D');
            $entries->push([clone $start, $theDate, $spent, $earned]);
            $start->addDay();
        }

        $data = $this->generator->period($entries);
        $cache->store($data);

        return Response::json($data);


    }

    /**
     * Returns a chart of what has been earned in this period in each category
     * grouped by month.
     *
     * @param CategoryRepositoryInterface $repository
     * @param                             $reportType
     * @param Carbon                      $start
     * @param Carbon                      $end
     * @param Collection                  $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function earnedInPeriod(CategoryRepositoryInterface $repository, $reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        $cache = new CacheProperties; // chart properties for cache:
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($reportType);
        $cache->addProperty($accounts);
        $cache->addProperty('category');
        $cache->addProperty('earned-in-period');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $set        = $repository->earnedForAccountsPerMonth($accounts, $start, $end);
        $categories = $set->unique('id')->sortBy(
            function (Category $category) {
                return $category->name;
            }
        );
        $entries    = new Collection;

        while ($start < $end) { // filter the set:
            $row = [clone $start];
            // get possibly relevant entries from the big $set
            $currentSet = $set->filter(
                function (Category $category) use ($start) {
                    return $category->dateFormatted == $start->format("Y-m");
                }
            );
            // check for each category if its in the current set.
            /** @var Category $category */
            foreach ($categories as $category) {
                // if its in there, use the value.
                $entry = $currentSet->filter(
                    function (Category $cat) use ($category) {
                        return ($cat->id == $category->id);
                    }
                )->first();
                if (!is_null($entry)) {
                    $row[] = round($entry->earned, 2);
                } else {
                    $row[] = 0;
                }
            }

            $entries->push($row);
            $start->addMonth();
        }
        $data = $this->generator->earnedInPeriod($categories, $entries);
        $cache->store($data);

        return $data;

    }

    /**
     * Returns a chart of what has been spent in this period in each category
     * grouped by month.
     *
     * @param CategoryRepositoryInterface $repository
     * @param                             $reportType
     * @param Carbon                      $start
     * @param Carbon                      $end
     * @param Collection                  $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function spentInPeriod(CategoryRepositoryInterface $repository, $reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        $cache = new CacheProperties; // chart properties for cache:
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($reportType);
        $cache->addProperty($accounts);
        $cache->addProperty('category');
        $cache->addProperty('spent-in-period');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $set        = $repository->spentForAccountsPerMonth($accounts, $start, $end);
        $categories = $set->unique('id')->sortBy(
            function (Category $category) {
                return $category->name;
            }
        );
        $entries    = new Collection;

        while ($start < $end) { // filter the set:
            $row = [clone $start];
            // get possibly relevant entries from the big $set
            $currentSet = $set->filter(
                function (Category $category) use ($start) {
                    return $category->dateFormatted == $start->format("Y-m");
                }
            );
            // check for each category if its in the current set.
            /** @var Category $category */
            foreach ($categories as $category) {
                // if its in there, use the value.
                $entry = $currentSet->filter(
                    function (Category $cat) use ($category) {
                        return ($cat->id == $category->id);
                    }
                )->first();
                if (!is_null($entry)) {
                    $row[] = round(($entry->spent * -1), 2);
                } else {
                    $row[] = 0;
                }
            }

            $entries->push($row);
            $start->addMonth();
        }
        $data = $this->generator->spentInPeriod($categories, $entries);
        $cache->store($data);

        return $data;
    }

}

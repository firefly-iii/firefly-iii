<?php

namespace FireflyIII\Http\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
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
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function all(CategoryRepositoryInterface $repository, Category $category)
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
            $entries->push([clone $start, $spent, $earned]);
            $start = Navigation::addPeriod($start, $range, 0);
        }

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

        $array = $repository->getCategoriesAndExpensesCorrected($start, $end);
        // sort by callback:
        uasort(
            $array,
            function ($left, $right) {
                if ($left['sum'] == $right['sum']) {
                    return 0;
                }

                return ($left['sum'] < $right['sum']) ? 1 : -1;
            }
        );
        $set  = new Collection($array);
        $data = $this->generator->frontpage($set);

        return Response::json($data);

    }

    /**
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function month(CategoryRepositoryInterface $repository, Category $category)
    {
        $start = clone Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->endOfMonth());

        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($category->id);
        $cache->addProperty('category');
        $cache->addProperty('month');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        $entries = new Collection;


        while ($start <= $end) {
            $spent  = $repository->spentOnDaySumCorrected($category, $start);
            $earned = $repository->earnedOnDaySumCorrected($category, $start);
            $entries->push([clone $start, $spent, $earned]);
            $start->addDay();
        }

        $data = $this->generator->month($entries);
        $cache->store($data);

        return Response::json($data);


    }

    /**
     * This chart will only show expenses.
     *
     * @param CategoryRepositoryInterface $repository
     * @param                             $year
     * @param bool                        $shared
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function spentInYear(CategoryRepositoryInterface $repository, $year, $shared = false)
    {
        $start = new Carbon($year . '-01-01');
        $end   = new Carbon($year . '-12-31');

        $cache = new CacheProperties; // chart properties for cache:
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category');
        $cache->addProperty('spent-in-year');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $shared        = $shared == 'shared' ? true : false;
        $allCategories = $repository->getCategories();
        $entries       = new Collection;
        $categories    = $allCategories->filter(
            function (Category $category) use ($repository, $start, $end, $shared) {
                $spent = $repository->balanceInPeriod($category, $start, $end, $shared);
                if ($spent < 0) {
                    return $category;
                }

                return null;
            }
        );

        while ($start < $end) {
            $month = clone $start; // month is the current end of the period
            $month->endOfMonth();
            $row = [clone $start]; // make a row:

            foreach ($categories as $category) { // each budget, fill the row
                $spent = $repository->balanceInPeriod($category, $start, $month, $shared);
                if ($spent < 0) {
                    $row[] = $spent * -1;
                } else {
                    $row[] = 0;
                }
            }
            $entries->push($row);
            $start->addMonth();
        }
        $data = $this->generator->spentInYear($categories, $entries);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * This chart will only show income.
     *
     * @param CategoryRepositoryInterface $repository
     * @param                             $year
     * @param bool                        $shared
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function earnedInYear(CategoryRepositoryInterface $repository, $year, $shared = false)
    {
        $start = new Carbon($year . '-01-01');
        $end   = new Carbon($year . '-12-31');

        $cache = new CacheProperties; // chart properties for cache:
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category');
        $cache->addProperty('earned-in-year');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $shared        = $shared == 'shared' ? true : false;
        $allCategories = $repository->getCategories();
        $allEntries    = new Collection;
        $categories    = $allCategories->filter(
            function (Category $category) use ($repository, $start, $end, $shared) {
                $spent = $repository->balanceInPeriod($category, $start, $end, $shared);
                if ($spent > 0) {
                    return $category;
                }

                return null;
            }
        );

        while ($start < $end) {
            $month = clone $start; // month is the current end of the period
            $month->endOfMonth();
            $row = [clone $start]; // make a row:

            foreach ($categories as $category) { // each budget, fill the row
                $spent = $repository->balanceInPeriod($category, $start, $month, $shared);
                if ($spent > 0) {
                    $row[] = $spent;
                } else {
                    $row[] = 0;
                }
            }
            $allEntries->push($row);
            $start->addMonth();
        }
        $data = $this->generator->earnedInYear($categories, $allEntries);
        $cache->store($data);

        return Response::json($data);
    }
}

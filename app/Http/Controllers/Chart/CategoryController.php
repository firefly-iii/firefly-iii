<?php

namespace FireflyIII\Http\Controllers\Chart;


use App;
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
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = App::make('FireflyIII\Generator\Chart\Category\CategoryChartGenerator');
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
        $start = $repository->getFirstActivityDate($category);
        $range = Preferences::get('viewRange', '1M')->data;
        $start = Navigation::startOfPeriod($start, $range);
        $end   = new Carbon;

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
            $spent      = $repository->spentInPeriodCorrected($category, $start, $currentEnd);
            $entries->push([clone $start, $spent]);
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
            $spent = $repository->spentOnDaySumCorrected($category, $start);

            $entries->push([clone $start, $spent]);
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
    public function year(CategoryRepositoryInterface $repository, $year, $shared = false)
    {
        $start = new Carbon($year . '-01-01');
        $end   = new Carbon($year . '-12-31');


        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category');
        $cache->addProperty('year');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $shared     = $shared == 'shared' ? true : false;
        $categories = $repository->getCategories();
        $entries    = new Collection;

        while ($start < $end) {
            // month is the current end of the period:
            $month = clone $start;
            $month->endOfMonth();
            // make a row:
            $row = [clone $start];

            // each budget, fill the row:
            foreach ($categories as $category) {
                $spent = $repository->spentInPeriodCorrected($category, $start, $month, $shared);
                $row[] = $spent;
            }
            $entries->push($row);

            $start->addMonth();
        }

        $data = $this->generator->year($categories, $entries);
        $cache->store($data);

        return Response::json($data);
    }
}

<?php

namespace FireflyIII\Http\Controllers\Chart;


use Cache;
use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\ChartProperties;
use Grumpydictator\Gchart\GChart;
use Log;
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


    /**
     * Show an overview for a category for all time, per month/week/year.
     *
     * @param GChart                      $chart
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function all(GChart $chart, CategoryRepositoryInterface $repository, Category $category)
    {
        // oldest transaction in category:
        $start = $repository->getFirstActivityDate($category);
        $range = Preferences::get('viewRange', '1M')->data;
        // jump to start of week / month / year / etc
        $start = Navigation::startOfPeriod($start, $range);

        $chart->addColumn(trans('firefly.period'), 'date');
        $chart->addColumn(trans('firefly.spent'), 'number');


        $end = new Carbon;
        while ($start <= $end) {

            $currentEnd = Navigation::endOfPeriod($start, $range);
            $spent      = $repository->spentInPeriodCorrected($category, $start, $currentEnd);
            $chart->addRow(clone $start, $spent);

            $start = Navigation::addPeriod($start, $range, 0);
        }

        $chart->generate();

        return Response::json($chart->getData());


    }

    /**
     * Show this month's category overview.
     *
     * @param GChart                      $chart
     * @param CategoryRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(GChart $chart, CategoryRepositoryInterface $repository)
    {
        $chart->addColumn(trans('firefly.category'), 'string');
        $chart->addColumn(trans('firefly.spent'), 'number');

        $start = Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->endOfMonth());

        // chart properties for cache:
        $chartProperties = new ChartProperties;
        $chartProperties->addProperty($start);
        $chartProperties->addProperty($end);
        $chartProperties->addProperty('category');
        $chartProperties->addProperty('frontpage');
        $md5 = $chartProperties->md5();


        if (Cache::has($md5)) {
            Log::debug('Successfully returned cached chart [' . $md5 . '].');

            return Response::json(Cache::get($md5));
        }

        $set   = $repository->getCategoriesAndExpensesCorrected($start, $end);

        // sort by callback:
        uasort(
            $set,
            function($left, $right) {
                if ($left['sum'] == $right['sum']) {
                    return 0;
                }

                return ($left['sum'] < $right['sum']) ? 1 : -1;
            }
        );


        foreach ($set as $entry) {
            $sum = floatval($entry['sum']);
            if ($sum != 0) {
                $chart->addRow($entry['name'], $sum);
            }
        }

        $chart->generate();

        $data = $chart->getData();
        Cache::forever($md5, $data);

        return Response::json($data);

    }

    /**
     * @param GChart                      $chart
     * @param CategoryRepositoryInterface $repository
     * @param Category                    $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function month(GChart $chart, CategoryRepositoryInterface $repository, Category $category)
    {
        $start = clone Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->endOfMonth());

        $chart->addColumn(trans('firefly.period'), 'date');
        $chart->addColumn(trans('firefly.spent'), 'number');

        while ($start <= $end) {
            $spent = $repository->spentOnDaySumCorrected($category, $start);
            $chart->addRow(clone $start, $spent);
            $start->addDay();
        }

        $chart->generate();

        return Response::json($chart->getData());


    }

    /**
     * This chart will only show expenses.
     *
     * @param GChart                      $chart
     * @param CategoryRepositoryInterface $repository
     * @param                             $year
     * @param bool                        $shared
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function year(GChart $chart, CategoryRepositoryInterface $repository, $year, $shared = false)
    {
        $start      = new Carbon($year . '-01-01');
        $end        = new Carbon($year . '-12-31');
        $shared     = $shared == 'shared' ? true : false;
        $categories = $repository->getCategories();

        // add columns:
        $chart->addColumn(trans('firefly.month'), 'date');
        foreach ($categories as $category) {
            $chart->addColumn($category->name, 'number');
        }

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
            $chart->addRowArray($row);

            $start->addMonth();
        }

        $chart->generate();

        return Response::json($chart->getData());
    }
}

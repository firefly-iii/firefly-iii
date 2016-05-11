<?php
declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Generator\Chart\Category\CategoryChartGeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface as CRI;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Log;
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
    const MAKE_POSITIVE = -1;
    const KEEP_POSITIVE = 1;


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
     * @param CRI      $repository
     * @param Category $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function all(CRI $repository, Category $category)
    {
        $start              = $repository->firstUseDate($category, new Collection);
        $range              = Preferences::get('viewRange', '1M')->data;
        $start              = Navigation::startOfPeriod($start, $range);
        $categoryCollection = new Collection([$category]);
        $end                = new Carbon;
        $entries            = new Collection;
        $cache              = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('all');
        $cache->addProperty('categories');
        if ($cache->has()) {
            //return Response::json($cache->get());
        }

        while ($start <= $end) {
            $currentEnd = Navigation::endOfPeriod($start, $range);
            Log::debug('Searching for expenses from ' . $start . ' to ' . $currentEnd);
            $spent  = $repository->spentInPeriod($categoryCollection, new Collection, $start, $currentEnd);
            $earned = $repository->earnedInPeriod($categoryCollection, new Collection, $start, $currentEnd);
            $date   = Navigation::periodShow($start, $range);
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
     * Show this month's category overview.
     *
     * @param CRI $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(CRI $repository)
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
            //return Response::json($cache->get());
        }
        $categories = $repository->getCategories();
        $set        = new Collection;
        /** @var Category $category */
        foreach ($categories as $category) {
            $spent = $repository->spentInPeriod(new Collection([$category]), new Collection, $start, $end);
            Log::debug('Spent for ' . $category->name . ' is ' . $spent . ' (' . bccomp($spent, '0') . ')');
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
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     * @param Collection $categories
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function multiYear(Carbon $start, Carbon $end, Collection $accounts, Collection $categories)
    {

        /** @var CRI $repository */
        $repository = app(CRI::class);

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty($categories);
        $cache->addProperty('multiYearCategory');

        if ($cache->has()) {
            //return Response::json($cache->get());
        }

        $entries = new Collection;

        /** @var Category $category */
        foreach ($categories as $category) {
            $entry = ['name' => '', 'spent' => [], 'earned' => []];

            $currentStart = clone $start;
            while ($currentStart < $end) {
                // fix the date:
                $year       = $currentStart->year;
                $currentEnd = clone $currentStart;
                $currentEnd->endOfYear();

                // get data:
                if (is_null($category->id)) {
                    $name   = trans('firefly.noCategory');
                    $spent  = $repository->spentInPeriodWithoutCategory($accounts, $currentStart, $currentEnd);
                    $earned = $repository->earnedInPeriodWithoutCategory($accounts, $currentStart, $currentEnd);
                } else {

                    $name   = $category->name;
                    $spent  = $repository->spentInPeriod(new Collection([$category]), $accounts, $currentStart, $currentEnd);
                    $earned = $repository->earnedInPeriod(new Collection([$category]), $accounts, $currentStart, $currentEnd);
                }

                // save to array:
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
     * @param Category   $category
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function period(Category $category, Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty($category->id);
        $cache->addProperty('category');
        $cache->addProperty('period');
        if ($cache->has()) {
            // return Response::json($cache->get());
        }

        /** @var CRI $repository */
        $repository         = app(CRI::class);
        $categoryCollection = new Collection([$category]);
        // loop over period, add by users range:
        $current   = clone $start;
        $viewRange = Preferences::get('viewRange', '1M')->data;
        $format    = strval(trans('config.month'));
        $set       = new Collection;
        while ($current < $end) {
            $currentStart = clone $current;
            $currentEnd   = Navigation::endOfPeriod($currentStart, $viewRange);
            $spent        = $repository->spentInPeriod($categoryCollection, $accounts, $currentStart, $currentEnd);
            $earned       = $repository->earnedInPeriod($categoryCollection, $accounts, $currentStart, $currentEnd);

            $entry = [
                $category->name,
                $currentStart->formatLocalized($format),
                $spent,
                $earned,

            ];
            $set->push($entry);
            $currentEnd->addDay();
            $current = clone $currentEnd;
        }
        $data = $this->generator->period($set);
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
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($category->id);
        $cache->addProperty('specific-period');

        if ($cache->has()) {
            // return $cache->get();
        }
        $entries = new Collection;
        Log::debug('Start is ' . $start . ' en end is ' . $end);
        while ($start <= $end) {
            Log::debug('Now at ' . $start);
            $spent = $repository->spentInPeriod($categoryCollection, new Collection, $start, $start);
            Log::debug('spent: ' . $spent);
            $earned = $repository->earnedInPeriod($categoryCollection, new Collection, $start, $start);
            Log::debug('earned: ' . $earned);
            $date = Navigation::periodShow($start, '1D');
            $entries->push([clone $start, $date, $spent, $earned]);
            $start->addDay();
        }

        $data = $this->generator->period($entries);
        $cache->store($data);

        return $data;

    }

}

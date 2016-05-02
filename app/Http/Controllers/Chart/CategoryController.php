<?php
declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Generator\Chart\Category\CategoryChartGeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface as CRI;
use FireflyIII\Repositories\Category\SingleCategoryRepositoryInterface;
use FireflyIII\Repositories\Category\SingleCategoryRepositoryInterface as SCRI;
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
     * @param SCRI     $repository
     * @param Category $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function all(SCRI $repository, Category $category)
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
            return Response::json($cache->get());
        }
        $spentArray  = $repository->spentPerDay($category, $start, $end, new Collection);
        $earnedArray = $repository->earnedPerDay($category, $start, $end, new Collection);

        while ($start <= $end) {
            $currentEnd = Navigation::endOfPeriod($start, $range);
            $spent      = $this->getSumOfRange($start, $currentEnd, $spentArray);
            $earned     = $this->getSumOfRange($start, $currentEnd, $earnedArray);
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
     * @param SCRI     $repository
     * @param Category $category
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function currentPeriod(SCRI $repository, Category $category)
    {
        $start = clone session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        $data  = $this->makePeriodChart($repository, $category, $start, $end);

        return Response::json($data);
    }

    /**
     * Returns a chart of what has been earned in this period in each category
     * grouped by month.
     *
     * @param CRI                         $repository
     * @param                             $reportType
     * @param Carbon                      $start
     * @param Carbon                      $end
     * @param Collection                  $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function earnedInPeriod(CRI $repository, string $reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        $cache = new CacheProperties; // chart properties for cache:
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($reportType);
        $cache->addProperty($accounts);
        $cache->addProperty('category');
        $cache->addProperty('earned-in-period');
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $set        = $repository->earnedForAccountsPerMonth($accounts, $start, $end);
        $categories = $set->unique('id')->sortBy(
            function (Category $category) {
                return $category->name;
            }
        );
        $entries    = $this->filterCollection($start, $end, $set, $categories);
        $data       = $this->generator->earnedInPeriod($categories, $entries);
        $cache->store($data);

        return $data;

    }

    /**
     * Show this month's category overview.
     *
     * @param CRI $repository
     *
     * @param ARI $accountRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(CRI $repository, ARI $accountRepository)
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

        // get data for categories (and "no category"):
        $accounts = $accountRepository->getAccounts(['Default account', 'Asset account', 'Cash account']);
        $set      = $repository->spentForAccountsPerMonth($accounts, $start, $end);
        $outside  = $repository->sumSpentNoCategory($accounts, $start, $end);

        // this is a "fake" entry for the "no category" entry.
        $entry        = new stdClass();
        $entry->name  = trans('firefly.no_category');
        $entry->spent = $outside;
        $set->push($entry);

        $set  = $set->sortBy('spent');
        $data = $this->generator->frontpage($set);
        $cache->store($data);

        return Response::json($data);

    }

    /**
     * @param                             $reportType
     * @param Carbon                      $start
     * @param Carbon                      $end
     * @param Collection                  $accounts
     * @param Collection                  $categories
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function multiYear(string $reportType, Carbon $start, Carbon $end, Collection $accounts, Collection $categories)
    {
        /** @var CRI $repository */
        $repository = app(CRI::class);

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($reportType);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty($categories);
        $cache->addProperty('multiYearCategory');

        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $entries = new Collection;
        $set     = $repository->listMultiYear($categories, $accounts, $start, $end);

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
                    $spent  = $repository->sumSpentNoCategory($accounts, $currentStart, $currentEnd);
                    $earned = $repository->sumEarnedNoCategory($accounts, $currentStart, $currentEnd);
                } else {
                    // get from set:
                    $entrySpent  = $set->filter(
                        function (Category $cat) use ($year, $category) {
                            return ($cat->type == 'Withdrawal' && $cat->dateFormatted == $year && $cat->id == $category->id);
                        }
                    )->first();
                    $entryEarned = $set->filter(
                        function (Category $cat) use ($year, $category) {
                            return ($cat->type == 'Deposit' && $cat->dateFormatted == $year && $cat->id == $category->id);
                        }
                    )->first();

                    $name   = $category->name;
                    $spent  = !is_null($entrySpent) ? $entrySpent->sum : 0;
                    $earned = !is_null($entryEarned) ? $entryEarned->sum : 0;
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
     * @param string     $reportType
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function period(Category $category, string $reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($reportType);
        $cache->addProperty($accounts);
        $cache->addProperty($category->id);
        $cache->addProperty('category');
        $cache->addProperty('period');
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        /** @var SingleCategoryRepositoryInterface $repository */
        $repository = app(SingleCategoryRepositoryInterface::class);
        // loop over period, add by users range:
        $current   = clone $start;
        $viewRange = Preferences::get('viewRange', '1M')->data;
        $format    = strval(trans('config.month'));
        $set       = new Collection;
        while ($current < $end) {
            $currentStart = clone $current;
            $currentEnd   = Navigation::endOfPeriod($currentStart, $viewRange);

            $spent  = strval(array_sum($repository->spentPerDay($category, $currentStart, $currentEnd, $accounts)));
            $earned = strval(array_sum($repository->earnedPerDay($category, $currentStart, $currentEnd, $accounts)));

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
     * @param SCRI                        $repository
     * @param Category                    $category
     *
     * @param                             $date
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function specificPeriod(SCRI $repository, Category $category, $date)
    {
        $carbon = new Carbon($date);
        $range  = Preferences::get('viewRange', '1M')->data;
        $start  = Navigation::startOfPeriod($carbon, $range);
        $end    = Navigation::endOfPeriod($carbon, $range);
        $data   = $this->makePeriodChart($repository, $category, $start, $end);

        return Response::json($data);


    }

    /**
     * Returns a chart of what has been spent in this period in each category
     * grouped by month.
     *
     * @param CRI                         $repository
     * @param                             $reportType
     * @param Carbon                      $start
     * @param Carbon                      $end
     * @param Collection                  $accounts
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function spentInPeriod(CRI $repository, $reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        $cache = new CacheProperties; // chart properties for cache:
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($reportType);
        $cache->addProperty($accounts);
        $cache->addProperty('category');
        $cache->addProperty('spent-in-period');
        if ($cache->has()) {
            return Response::json($cache->get());
        }


        $set        = $repository->spentForAccountsPerMonth($accounts, $start, $end);
        $categories = $set->unique('id')->sortBy(
            function (Category $category) {
                return $category->name;
            }
        );
        $entries    = $this->filterCollection($start, $end, $set, $categories);
        $entries    = $this->invertSelection($entries);
        $data       = $this->generator->spentInPeriod($categories, $entries);
        $cache->store($data);

        return $data;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $set
     * @param Collection $categories
     *
     * @return Collection
     */
    private function filterCollection(Carbon $start, Carbon $end, Collection $set, Collection $categories): Collection
    {
        $entries = new Collection;

        while ($start < $end) { // filter the set:
            $row        = [clone $start];
            $currentSet = $set->filter( // get possibly relevant entries from the big $set
                function (Category $category) use ($start) {
                    return $category->dateFormatted == $start->format('Y-m');
                }
            );
            /** @var Category $category */
            foreach ($categories as $category) { // check for each category if its in the current set.
                $entry = $currentSet->filter( // if its in there, use the value.
                    function (Category $cat) use ($category) {
                        return ($cat->id == $category->id);
                    }
                )->first();
                if (!is_null($entry)) {
                    $row[] = $entry->earned ? round($entry->earned, 2) : round($entry->spent, 2);
                } else {
                    $row[] = 0;
                }
            }
            $entries->push($row);
            $start->addMonth();
        }

        return $entries;
    }

    /**
     * Not the most elegant solution but it works.
     *
     * @param Collection $entries
     *
     * @return Collection
     */
    private function invertSelection(Collection $entries): Collection
    {
        $result = new Collection;
        foreach ($entries as $entry) {
            $new   = [$entry[0]];
            $count = count($entry);
            for ($i = 1; $i < $count; $i++) {
                $new[$i] = ($entry[$i] * -1);
            }
            $result->push($new);
        }

        return $result;

    }

    /**
     * @param SCRI     $repository
     * @param Category $category
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return array
     */
    private function makePeriodChart(SCRI $repository, Category $category, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($category->id);
        $cache->addProperty('specific-period');
        if ($cache->has()) {
            return $cache->get(); //
        }
        $entries = new Collection;

        // get amount earned in period, grouped by day.
        // get amount spent in period, grouped by day.
        $spentArray  = $repository->spentPerDay($category, $start, $end, new Collection);
        $earnedArray = $repository->earnedPerDay($category, $start, $end, new Collection);

        while ($start <= $end) {
            $str    = $start->format('Y-m-d');
            $spent  = $spentArray[$str] ?? '0';
            $earned = $earnedArray[$str] ?? '0';
            $date   = Navigation::periodShow($start, '1D');
            $entries->push([clone $start, $date, $spent, $earned]);
            $start->addDay();
        }

        $data = $this->generator->period($entries);
        $cache->store($data);

        return $data;
    }

}

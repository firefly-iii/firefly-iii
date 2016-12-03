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

namespace FireflyIII\Http\Controllers\Report;


use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Navigation;

/**
 * Class CategoryController
 *
 * @package FireflyIII\Http\Controllers\Report
 */
class CategoryController extends Controller
{
    /**
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return string
     */
    public function categoryPeriodReport(Carbon $start, Carbon $end, Collection $accounts)
    {

        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();
        $report     = $repository->getCategoryPeriodReport($categories, $accounts, $start, $end, true);
        $report     = $this->filterCategoryPeriodReport($report);
        $periods    = Navigation::listOfPeriods($start, $end);

        $result = view('reports.partials.category-period', compact('categories', 'periods', 'report'))->render();

        return $result;
    }

    /**
     * @param ReportHelperInterface $helper
     * @param Carbon                $start
     * @param Carbon                $end
     * @param Collection            $accounts
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function categoryReport(ReportHelperInterface $helper, Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get();
        }

        $categories = $helper->getCategoryReport($start, $end, $accounts);

        $result = view('reports.partials.categories', compact('categories'))->render();
        $cache->store($result);

        return $result;
    }

    /**
     * Filters empty results from category period report
     *
     * @param array $data
     *
     * @return array
     */
    private function filterCategoryPeriodReport(array $data): array
    {
        foreach ($data as $key => $set) {
            /**
             * @var int   $categoryId
             * @var array $set
             */
            foreach ($set as $categoryId => $info) {
                $sum = '0';
                foreach ($info['entries'] as $amount) {
                    $sum = bcadd($amount, $sum);
                }
                $data[$key][$categoryId]['sum'] = $sum;
                if (bccomp('0', $sum) === 0) {
                    unset($data[$key][$categoryId]);
                }
            }
        }

        return $data;
    }

    /**
     * @param int        $categoryId
     * @param Collection $categories
     *
     * @return string
     */
    private function getCategoryName(int $categoryId, Collection $categories): string
    {

        $first = $categories->filter(
            function (Category $category) use ($categoryId) {
                return $categoryId === $category->id;
            }
        );
        if (!is_null($first->first())) {
            return $first->first()->name;
        }

        return '(unknown)';
    }

}
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
use Log;
use Navigation;

/**
 * Class CategoryController
 *
 * @package FireflyIII\Http\Controllers\Report
 */
class CategoryController extends Controller
{
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
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return string
     */
    public function expenseReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category-period-expenses-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            Log::debug('Return report from cache');
            return $cache->get();
        }
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();
        $data       = $repository->periodExpenses($categories, $accounts, $start, $end);
        $data[0]    = $repository->periodExpensesNoCategory($accounts, $start, $end);
        $report     = $this->filterReport($data);
        $periods    = Navigation::listOfPeriods($start, $end);
        $result = view('reports.partials.category-period', compact('report', 'periods'))->render();

        $cache->store($result);

        return $result;
    }

    /**
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return string
     */
    public function incomeReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category-period-income-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            Log::debug('Return report from cache');
            return $cache->get();
        }
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();
        $data       = $repository->periodIncome($categories, $accounts, $start, $end);
        $data[0]    = $repository->periodIncomeNoCategory($accounts, $start, $end);
        $report     = $this->filterReport($data);
        $periods    = Navigation::listOfPeriods($start, $end);
        $result = view('reports.partials.category-period', compact('report', 'periods'))->render();

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
    private function filterReport(array $data): array
    {
        foreach ($data as $categoryId => $set) {
            $sum = '0';
            foreach ($set['entries'] as $amount) {
                $sum = bcadd($amount, $sum);
            }
            $data[$categoryId]['sum'] = $sum;
            if (bccomp('0', $sum) === 0) {
                unset($data[$categoryId]);
            }
        }


        return $data;
    }


}
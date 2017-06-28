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

namespace FireflyIII\Http\Controllers\Report;


use Carbon\Carbon;
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
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     */
    public function expenses(Collection $accounts, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category-period-expenses-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();
        $data       = $repository->periodExpenses($categories, $accounts, $start, $end);
        $data[0]    = $repository->periodExpensesNoCategory($accounts, $start, $end);
        $report     = $this->filterReport($data);
        $periods    = Navigation::listOfPeriods($start, $end);
        $result     = view('reports.partials.category-period', compact('report', 'periods'))->render();

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
    public function income(Collection $accounts, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category-period-income-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();
        $data       = $repository->periodIncome($categories, $accounts, $start, $end);
        $data[0]    = $repository->periodIncomeNoCategory($accounts, $start, $end);
        $report     = $this->filterReport($data);
        $periods    = Navigation::listOfPeriods($start, $end);
        $result     = view('reports.partials.category-period', compact('report', 'periods'))->render();

        $cache->store($result);

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     * @internal param ReportHelperInterface $helper
     */
    public function operations(Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();
        $report     = [];
        /** @var Category $category */
        foreach ($categories as $category) {
            $spent = $repository->spentInPeriod(new Collection([$category]), $accounts, $start, $end);
            if (bccomp($spent, '0') !== 0) {
                $report[$category->id] = ['name' => $category->name, 'spent' => $spent,'id' => $category->id];
            }
        }

        // sort the result
        // Obtain a list of columns
        $sum = [];
        foreach ($report as $categoryId => $row) {
            $sum[$categoryId] = floatval($row['spent']);
        }

        array_multisort($sum, SORT_ASC, $report);

        $result = view('reports.partials.categories', compact('report'))->render();
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

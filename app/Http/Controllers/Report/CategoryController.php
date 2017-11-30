<?php
/**
 * CategoryController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
 * Class CategoryController.
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
     *
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
            if (0 !== bccomp($spent, '0')) {
                $report[$category->id] = ['name' => $category->name, 'spent' => $spent, 'id' => $category->id];
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
     * Filters empty results from category period report.
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
            if (0 === bccomp('0', $sum)) {
                unset($data[$categoryId]);
            }
        }

        return $data;
    }
}

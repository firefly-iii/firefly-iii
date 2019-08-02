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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\BasicDataSupport;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class CategoryController.
 */
class CategoryController extends Controller
{
    use BasicDataSupport;

    /**
     * Show overview of expenses in category.
     *
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
        $report     = $this->filterPeriodReport($data);

        // depending on the carbon format (a reliable way to determine the general date difference)
        // change the "listOfPeriods" call so the entire period gets included correctly.
        $range = app('navigation')->preferredCarbonFormat($start, $end);

        if ('Y' === $range) {
            $start->startOfYear();
        }
        if ('Y-m' === $range) {
            $start->startOfMonth();
        }

        $periods    = app('navigation')->listOfPeriods($start, $end);
        try {
            $result = view('reports.partials.category-period', compact('report', 'periods'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render category::expenses: %s', $e->getMessage()));
            $result = sprintf('An error prevented Firefly III from rendering: %s. Apologies.', $e->getMessage());
        }
        // @codeCoverageIgnoreEnd

        $cache->store($result);

        return $result;
    }


    /**
     * Show overview of income in category.
     *
     * @param Collection $accounts
     *
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function income(Collection $accounts, Carbon $start, Carbon $end): string
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
        $report     = $this->filterPeriodReport($data);

        // depending on the carbon format (a reliable way to determine the general date difference)
        // change the "listOfPeriods" call so the entire period gets included correctly.
        $range = app('navigation')->preferredCarbonFormat($start, $end);

        if ('Y' === $range) {
            $start->startOfYear();
        }
        if ('Y-m' === $range) {
            $start->startOfMonth();
        }

        $periods    = app('navigation')->listOfPeriods($start, $end);
        try {
            $result = view('reports.partials.category-period', compact('report', 'periods'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render category::expenses: %s', $e->getMessage()));
            $result = sprintf('An error prevented Firefly III from rendering: %s. Apologies.', $e->getMessage());
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }


    /**
     * Show overview of operations.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
            $spent  = $repository->spentInPeriod(new Collection([$category]), $accounts, $start, $end);
            $earned = $repository->earnedInPeriod(new Collection([$category]), $accounts, $start, $end);
            if (0 !== bccomp($spent, '0') || 0 !== bccomp($earned, '0')) {
                $report[$category->id] = ['name' => $category->name, 'spent' => $spent, 'earned' => $earned, 'id' => $category->id];
            }
        }
        $sum = [];
        foreach ($report as $categoryId => $row) {
            $sum[$categoryId] = (float)$row['spent'];
        }
        array_multisort($sum, SORT_ASC, $report);
        // @codeCoverageIgnoreStart
        try {
            $result = view('reports.partials.categories', compact('report'))->render();
            $cache->store($result);
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render category::expenses: %s', $e->getMessage()));
            $result = sprintf('An error prevented Firefly III from rendering: %s. Apologies.', $e->getMessage());
        }

        // @codeCoverageIgnoreEnd

        return $result;
    }


}

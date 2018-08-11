<?php
/**
 * AugumentData.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Trait AugumentData
 *
 * @package FireflyIII\Support\Http\Controllers
 */
trait AugumentData
{
    /**
     * Get the account names belonging to a bunch of account ID's.
     *
     * @param array $accountIds
     *
     * @return array
     */
    protected function getAccountNames(array $accountIds): array // extract info from array.
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $accounts   = $repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT, AccountType::EXPENSE, AccountType::CASH]);
        $grouped    = $accounts->groupBy('id')->toArray();
        $return     = [];
        foreach ($accountIds as $accountId) {
            if (isset($grouped[$accountId])) {
                $return[$accountId] = $grouped[$accountId][0]['name'];
            }
        }
        $return[0] = '(no name)';

        return $return;
    }

    /**
     * Get the budget names from a set of budget ID's.
     *
     * @param array $budgetIds
     *
     * @return array
     */
    protected function getBudgetNames(array $budgetIds): array // extract info from array.
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgets    = $repository->getBudgets();
        $grouped    = $budgets->groupBy('id')->toArray();
        $return     = [];
        foreach ($budgetIds as $budgetId) {
            if (isset($grouped[$budgetId])) {
                $return[$budgetId] = $grouped[$budgetId][0]['name'];
            }
        }
        $return[0] = (string)trans('firefly.no_budget');

        return $return;
    }

    /**
     * Get the category names from a set of category ID's. Small helper function for some of the charts.
     *
     * @param array $categoryIds
     *
     * @return array
     */
    protected function getCategoryNames(array $categoryIds): array // extract info from array.
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();
        $grouped    = $categories->groupBy('id')->toArray();
        $return     = [];
        foreach ($categoryIds as $categoryId) {
            if (isset($grouped[$categoryId])) {
                $return[$categoryId] = $grouped[$categoryId][0]['name'];
            }
        }
        $return[0] = (string)trans('firefly.noCategory');

        return $return;
    }


    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Returns the budget limits belonging to the given budget and valid on the given day.
     *
     * @param Collection $budgetLimits
     * @param Budget     $budget
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    protected function filterBudgetLimits(Collection $budgetLimits, Budget $budget, Carbon $start, Carbon $end): Collection // filter data
    {
        $set = $budgetLimits->filter(
            function (BudgetLimit $budgetLimit) use ($budget, $start, $end) {
                if ($budgetLimit->budget_id === $budget->id
                    && $budgetLimit->start_date->lte($start) // start of budget limit is on or before start
                    && $budgetLimit->end_date->gte($end) // end of budget limit is on or after end
                ) {
                    return $budgetLimit;
                }

                return false;
            }
        );

        return $set;
    }
}
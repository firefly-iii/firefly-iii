<?php
/**
 * PopupReportInterface.php
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

namespace FireflyIII\Helpers\Report;

use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use Illuminate\Support\Collection;

/**
 * Interface PopupReportInterface.
 */
interface PopupReportInterface
{
    /**
     * @param $account
     * @param $attributes
     *
     * @return Collection
     */
    public function balanceDifference($account, $attributes): Collection;

    /**
     * @param Budget  $budget
     * @param Account $account
     * @param array   $attributes
     *
     * @return Collection
     */
    public function balanceForBudget(Budget $budget, Account $account, array $attributes): Collection;

    /**
     * @param Account $account
     * @param array   $attributes
     *
     * @return Collection
     */
    public function balanceForNoBudget(Account $account, array $attributes): Collection;

    /**
     * @param Budget $budget
     * @param array  $attributes
     *
     * @return Collection
     */
    public function byBudget(Budget $budget, array $attributes): Collection;

    /**
     * @param Category $category
     * @param array    $attributes
     *
     * @return Collection
     */
    public function byCategory(Category $category, array $attributes): Collection;

    /**
     * @param Account $account
     * @param array   $attributes
     *
     * @return Collection
     */
    public function byExpenses(Account $account, array $attributes): Collection;

    /**
     * @param Account $account
     * @param array   $attributes
     *
     * @return Collection
     */
    public function byIncome(Account $account, array $attributes): Collection;
}

<?php
/**
 * PopupReportInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Report;

use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use Illuminate\Support\Collection;

/**
 * Interface PopupReportInterface
 *
 * @package FireflyIII\Helpers\Report
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

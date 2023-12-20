<?php

/**
 * PopupReportInterface.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Helpers\Report;

use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;

/**
 * Interface PopupReportInterface.
 */
interface PopupReportInterface
{
    /**
     * Get balances for budget.
     */
    public function balanceForBudget(Budget $budget, Account $account, array $attributes): array;

    /**
     * Get balances for transactions without a budget.
     */
    public function balanceForNoBudget(Account $account, array $attributes): array;

    /**
     * Group by budget.
     */
    public function byBudget(Budget $budget, array $attributes): array;

    /**
     * Group by category.
     */
    public function byCategory(?Category $category, array $attributes): array;

    /**
     * Do something with expense. Sorry, I am not very inspirational here.
     */
    public function byExpenses(Account $account, array $attributes): array;

    /**
     * Do something with income. Sorry, I am not very inspirational here.
     */
    public function byIncome(Account $account, array $attributes): array;
}

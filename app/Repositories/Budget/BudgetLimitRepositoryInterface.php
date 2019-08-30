<?php
/**
 * BudgetLimitRepositoryInterface.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Repositories\Budget;

use FireflyIII\Models\BudgetLimit;
use FireflyIII\User;

/**
 * Interface BudgetLimitRepositoryInterface
 */
interface BudgetLimitRepositoryInterface
{
    /**
     * Destroy a budget limit.
     *
     * @param BudgetLimit $budgetLimit
     */
    public function destroyBudgetLimit(BudgetLimit $budgetLimit): void;

    /**
     * @param User $user
     */
    public function setUser(User $user): void;
}
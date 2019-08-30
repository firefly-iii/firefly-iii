<?php
/**
 * OperationsRepositoryInterface.php
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

use Carbon\Carbon;
use FireflyIII\Models\Budget;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface OperationsRepositoryInterface
 */
interface OperationsRepositoryInterface
{
    /**
     * A method that returns the amount of money budgeted per day for this budget,
     * on average.
     *
     * @param Budget $budget
     *
     * @return string
     * @deprecated
     */
    public function budgetedPerDay(Budget $budget): string;

    /**
     * This method collects various info on budgets, used on the budget page and on the index.
     *
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     */
    public function collectBudgetInformation(Collection $budgets, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     */
    public function getBudgetPeriodReport(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param User $user
     */
    public function setUser(User $user): void;

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     * @deprecated
     */
    public function spentInPeriod(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): string;

    /**
     * Return multi-currency spent information.
     *
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     */
    public function spentInPeriodMc(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array;

}
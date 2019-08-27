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

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Models\Category;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface OperationsRepositoryInterface
 *
 * @package FireflyIII\Repositories\Category
 */
interface OperationsRepositoryInterface
{
    /**
     * Returns the amount earned in a category, for a set of accounts, in a specific period.
     *
     * @param Category   $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     *
     */
    public function earnedInPeriod(Category $category, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     */
    public function earnedInPeriodPerCurrency(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     */
    public function periodExpenses(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     */
    public function periodIncome(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param User $user
     */
    public function setUser(User $user): void;

    /**
     * Returns the amount spent in a category, for a set of accounts, in a specific period.
     *
     * @param Category   $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     *
     */
    public function spentInPeriod(Category $category, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     */
    public function spentInPeriodPerCurrency(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): array;
}
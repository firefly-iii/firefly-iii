<?php
/**
 * NoCategoryRepositoryInterface.php
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
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface NoCategoryRepositoryInterface
 *
 * @package FireflyIII\Repositories\Category
 */
interface NoCategoryRepositoryInterface
{
    /**
     * @param User $user
     */
    public function setUser(User $user): void;

    /**
     * A very cryptic method name that means:
     *
     * Get me the amount earned in this period, grouped per currency, where no category was set.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     */
    public function earnedInPeriodPcWoCategory(Collection $accounts, Carbon $start, Carbon $end): array;


    /**
     * TODO not multi-currency
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     */
    public function periodExpensesNoCategory(Collection $accounts, Carbon $start, Carbon $end): array;


    /**
     * TODO not multi-currency
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     */
    public function periodIncomeNoCategory(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * A very cryptic method name that means:
     *
     * Get me the amount spent in this period, grouped per currency, where no category was set.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     *
     */
    public function spentInPeriodPcWoCategory(Collection $accounts, Carbon $start, Carbon $end): array;


}
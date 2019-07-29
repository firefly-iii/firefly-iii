<?php
/**
 * CostCenterRepositoryInterface.php
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

namespace FireflyIII\Repositories\CostCenter;

use Carbon\Carbon;
use FireflyIII\Models\CostCenter;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface CostCenterRepositoryInterface.
 */
interface CostCenterRepositoryInterface
{

    /**
     * @param CostCenter $costCenter
     *
     * @return bool
     */
    public function destroy(CostCenter $costCenter): bool;

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriod(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): string;

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function earnedInPeriodCollection(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): Collection;

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * A very cryptic method name that means:
     *
     * Get me the amount earned in this period, grouped per currency, where no costCenter was set.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function earnedInPeriodPcWoCostCenter(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function earnedInPeriodPerCurrency(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * Find a cost center.
     *
     * @param string $name
     *
     * @return CostCenter
     */
    public function findByName(string $name): ?CostCenter;

    /**
     * Find a cost center or return NULL
     *
     * @param int $costCenter
     *
     * @return CostCenter|null
     */
    public function findNull(int $costCenter): ?CostCenter;

    /**
     * @param CostCenter $costCenter
     *
     * @return Carbon|null
     */
    public function firstUseDate(CostCenter $costCenter): ?Carbon;

    /**
     * Get all cost centers with ID's.
     *
     * @param array $costCenter
     *
     * @return Collection
     */
    public function getByIds(array $costCenter): Collection;

    /**
     * Returns a list of all the cost centers belonging to a user.
     *
     * @return Collection
     */
    public function getCostCenters(): Collection;

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * Return most recent transaction(journal) date or null when never used before.
     *
     * @param CostCenter   $costCenter
     * @param Collection $accounts
     *
     * @return Carbon|null
     */
    public function lastUseDate(CostCenter $costCenter, Collection $accounts): ?Carbon;

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodExpenses(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): array;

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodExpensesNoCostCenter(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodIncome(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function periodIncomeNoCostCenter(Collection $accounts, Carbon $start, Carbon $end): array;

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function searchCostCenter(string $query): Collection;

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): string;

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function spentInPeriodCollection(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * A very cryptic method name that means:
     *
     * Get me the amount spent in this period, grouped per currency, where no cost center was set.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriodPcWoCostCenter(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $costCenters
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriodPerCurrency(Collection $costCenters, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWithoutCostCenter(Collection $accounts, Carbon $start, Carbon $end): string;

    /**
     * @param array $data
     *
     * @return CostCenter
     */
    public function store(array $data): CostCenter;

    /**
     * @param CostCenter $costCenter
     * @param array    $data
     *
     * @return CostCenter
     */
    public function update(CostCenter $costCenter, array $data): CostCenter;
}

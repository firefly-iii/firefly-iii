<?php
/**
 * BudgetRepositoryInterface.php
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

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Budget;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface BudgetRepositoryInterface.
 */
interface BudgetRepositoryInterface
{
    /**
     * Destroy all budgets.
     */
    public function destroyAll(): void;


    /**
     * @return bool
     */
    public function cleanupBudgets(): bool;

    /**
     * @param Budget $budget
     *
     * @return bool
     */
    public function destroy(Budget $budget): bool;

    /**
     * @param int|null    $budgetId
     * @param string|null $budgetName
     *
     * @return Budget|null
     */
    public function findBudget(?int $budgetId, ?string $budgetName): ?Budget;

    /**
     * Find budget by name.
     *
     * @param string|null $name
     *
     * @return Budget|null
     */
    public function findByName(?string $name): ?Budget;

    /**
     * TODO refactor to "find"
     *
     * @param int|null $budgetId
     *
     * @return Budget|null
     */
    public function findNull(int $budgetId = null): ?Budget;

    /**
     * This method returns the oldest journal or transaction date known to this budget.
     * Will cache result.
     *
     * @param Budget $budget
     *
     * @return Carbon
     */
    public function firstUseDate(Budget $budget): ?Carbon;

    /**
     * @return Collection
     */
    public function getActiveBudgets(): Collection;

    /**
     * @return Collection
     */
    public function getBudgets(): Collection;

    /**
     * Get all budgets with these ID's.
     *
     * @param array $budgetIds
     *
     * @return Collection
     */
    public function getByIds(array $budgetIds): Collection;

    /**
     * @return Collection
     */
    public function getInactiveBudgets(): Collection;

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function searchBudget(string $query): Collection;

    /**
     * @param Budget $budget
     * @param int    $order
     */
    public function setBudgetOrder(Budget $budget, int $order): void;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param array $data
     *
     * @return Budget
     * @throws FireflyException
     */
    public function store(array $data): Budget;

    /**
     * @param Budget $budget
     * @param array  $data
     *
     * @return Budget
     */
    public function update(Budget $budget, array $data): Budget;
}

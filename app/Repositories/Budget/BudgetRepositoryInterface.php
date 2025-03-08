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
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface BudgetRepositoryInterface.
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 *
 */
interface BudgetRepositoryInterface
{
    public function budgetEndsWith(string $query, int $limit): Collection;

    public function budgetStartsWith(string $query, int $limit): Collection;

    /**
     * Returns the amount that is budgeted in a period.
     */
    public function budgetedInPeriod(Carbon $start, Carbon $end): array;

    /**
     * Returns the amount that is budgeted in a period.
     */
    public function budgetedInPeriodForBudget(Budget $budget, Carbon $start, Carbon $end): array;

    public function cleanupBudgets(): bool;

    public function destroy(Budget $budget): bool;

    /**
     * Destroy all budgets.
     */
    public function destroyAll(): void;

    public function destroyAutoBudget(Budget $budget): void;

    public function find(?int $budgetId = null): ?Budget;

    public function findBudget(?int $budgetId, ?string $budgetName): ?Budget;

    /**
     * Find budget by name.
     */
    public function findByName(?string $name): ?Budget;

    /**
     * This method returns the oldest journal or transaction date known to this budget.
     * Will cache result.
     */
    public function firstUseDate(Budget $budget): ?Carbon;

    public function getActiveBudgets(): Collection;

    public function getAttachments(Budget $budget): Collection;

    public function getAutoBudget(Budget $budget): ?AutoBudget;

    public function getBudgets(): Collection;

    /**
     * Get all budgets with these ID's.
     */
    public function getByIds(array $budgetIds): Collection;

    public function getInactiveBudgets(): Collection;

    public function getMaxOrder(): int;

    public function getNoteText(Budget $budget): ?string;

    public function searchBudget(string $query, int $limit): Collection;

    public function setBudgetOrder(Budget $budget, int $order): void;

    /**
     * Used in the v2 API to calculate the amount of money spent in all active budgets.
     */
    public function spentInPeriod(Carbon $start, Carbon $end): array;

    /**
     * Used in the v2 API to calculate the amount of money spent in a single budget..
     */
    public function spentInPeriodForBudget(Budget $budget, Carbon $start, Carbon $end): array;

    /**
     * @throws FireflyException
     */
    public function store(array $data): Budget;

    public function update(Budget $budget, array $data): Budget;
}

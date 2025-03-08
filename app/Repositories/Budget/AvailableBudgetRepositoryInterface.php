<?php

/**
 * AvailableBudgetRepositoryInterface.php
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
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface AvailableBudgetRepositoryInterface
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 */
interface AvailableBudgetRepositoryInterface
{
    public function cleanup(): void;

    /**
     * Delete all available budgets.
     */
    public function destroyAll(): void;

    public function destroyAvailableBudget(AvailableBudget $availableBudget): void;

    /**
     * Find existing AB.
     */
    public function find(TransactionCurrency $currency, Carbon $start, Carbon $end): ?AvailableBudget;

    public function findById(int $id): ?AvailableBudget;

    /**
     * Return a list of all available budgets (in all currencies) (for the selected period).
     */
    public function get(?Carbon $start = null, ?Carbon $end = null): Collection;

    /**
     * @deprecated
     */
    public function getAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end): string;

    public function getAvailableBudgetWithCurrency(Carbon $start, Carbon $end): array;

    /**
     * Returns all available budget objects.
     */
    public function getAvailableBudgetsByCurrency(TransactionCurrency $currency): Collection;

    /**
     * Returns all available budget objects.
     */
    public function getAvailableBudgetsByDate(?Carbon $start, ?Carbon $end): Collection;

    public function getAvailableBudgetsByExactDate(Carbon $start, Carbon $end): Collection;

    /**
     * Get by transaction currency and date. Should always result in one entry or NULL.
     */
    public function getByCurrencyDate(Carbon $start, Carbon $end, TransactionCurrency $currency): ?AvailableBudget;

    /**
     * @deprecated
     */
    public function setAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end, string $amount): AvailableBudget;

    public function store(array $data): ?AvailableBudget;

    public function update(AvailableBudget $availableBudget, array $data): AvailableBudget;

    public function updateAvailableBudget(AvailableBudget $availableBudget, array $data): AvailableBudget;
}

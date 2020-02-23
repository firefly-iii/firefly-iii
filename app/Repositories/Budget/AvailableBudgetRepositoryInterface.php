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
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface AvailableBudgetRepositoryInterface
 */
interface AvailableBudgetRepositoryInterface
{

    /**
     * Delete all available budgets.
     */
    public function destroyAll(): void;

    /**
     * @param AvailableBudget $availableBudget
     */
    public function destroyAvailableBudget(AvailableBudget $availableBudget): void;

    /**
     * Find existing AB.
     *
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     *
     * @return AvailableBudget|null
     */
    public function find(TransactionCurrency $currency, Carbon $start, Carbon $end): ?AvailableBudget;

    /**
     * Return a list of all available budgets (in all currencies) (for the selected period).
     *
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return Collection
     */
    public function get(?Carbon $start = null, ?Carbon $end = null): Collection;

    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     *
     * @return string
     * @deprecated
     */
    public function getAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end): string;

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function getAvailableBudgetWithCurrency(Carbon $start, Carbon $end): array;

    /**
     * Returns all available budget objects.
     *
     * @param TransactionCurrency $currency
     *
     * @return Collection
     */
    public function getAvailableBudgetsByCurrency(TransactionCurrency $currency): Collection;

    /**
     * Returns all available budget objects.
     *
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return Collection
     *
     */
    public function getAvailableBudgetsByDate(?Carbon $start, ?Carbon $end): Collection;

    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     * @param string              $amount
     *
     * @return AvailableBudget
     * @deprecated
     */
    public function setAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end, string $amount): AvailableBudget;

    /**
     * @param User $user
     */
    public function setUser(User $user): void;

    /**
     * @param array $data
     *
     * @return AvailableBudget|null
     */
    public function store(array $data): ?AvailableBudget;

    /**
     * @param AvailableBudget $availableBudget
     * @param array           $data
     *
     * @return AvailableBudget
     */
    public function update(AvailableBudget $availableBudget, array $data): AvailableBudget;

    /**
     * @param AvailableBudget $availableBudget
     * @param array           $data
     *
     * @return AvailableBudget
     */
    public function updateAvailableBudget(AvailableBudget $availableBudget, array $data): AvailableBudget;

}
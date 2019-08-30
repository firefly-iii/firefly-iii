<?php
/**
 * BudgetRepositoryInterface.php
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

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface BudgetRepositoryInterface.
 */
interface BudgetRepositoryInterface
{
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
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     *
     * @return string
     */
    public function getAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end): string;

    /**
     * TODO only used in API
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function getAvailableBudgetWithCurrency(Carbon $start, Carbon $end): array;

    /**
     * TODO only used in API
     *
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
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getBudgetLimits(Budget $budget, Carbon $start = null, Carbon $end = null): Collection;

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getBudgetPeriodReport(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array;

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
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getNoBudgetPeriodReport(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function searchBudget(string $query): Collection;

    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     * @param string              $amount
     *
     * @return AvailableBudget
     */
    public function setAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end, string $amount): AvailableBudget;

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
     * Return multi-currency spent information.
     *
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriodMc(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWoBudget(Collection $accounts, Carbon $start, Carbon $end): string;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriodWoBudgetMc(Collection $accounts, Carbon $start, Carbon $end): array;


    /**
     * @param array $data
     *
     * @return Budget
     */
    public function store(array $data): Budget;

    /**
     * @param array $data
     *
     * @return BudgetLimit
     */
    public function storeBudgetLimit(array $data): BudgetLimit;

    /**
     * @param Budget $budget
     * @param array  $data
     *
     * @return Budget
     */
    public function update(Budget $budget, array $data): Budget;

    /**
     * @param AvailableBudget $availableBudget
     * @param array           $data
     *
     * @return AvailableBudget
     */
    public function updateAvailableBudget(AvailableBudget $availableBudget, array $data): AvailableBudget;

    /**
     * @param BudgetLimit $budgetLimit
     * @param array       $data
     *
     * @return BudgetLimit
     */
    public function updateBudgetLimit(BudgetLimit $budgetLimit, array $data): BudgetLimit;


    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     * @param string $amount
     *
     * @return BudgetLimit|null
     */
    public function updateLimitAmount(Budget $budget, Carbon $start, Carbon $end, string $amount): ?BudgetLimit;
}

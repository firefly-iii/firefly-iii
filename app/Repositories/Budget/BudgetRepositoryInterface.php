<?php
/**
 * BudgetRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use Illuminate\Support\Collection;

/**
 * Interface BudgetRepositoryInterface
 *
 * @package FireflyIII\Repositories\Budget
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
     * Find a budget.
     *
     * @param int $budgetId
     *
     * @return Budget
     */
    public function find(int $budgetId): Budget;

    /**
     * Find a budget.
     *
     * @param string $name
     *
     * @return Budget
     */
    public function findByName(string $name): Budget;

    /**
     * This method returns the oldest journal or transaction date known to this budget.
     * Will cache result.
     *
     * @param Budget $budget
     *
     * @return Carbon
     */
    public function firstUseDate(Budget $budget): Carbon;

    /**
     * @return Collection
     */
    public function getActiveBudgets(): Collection;

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getAllBudgetLimitRepetitions(Carbon $start, Carbon $end): Collection;

    /**
     * @return Collection
     */
    public function getBudgets(): Collection;

    /**
     * @return Collection
     */
    public function getInactiveBudgets(): Collection;

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriod(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriodWithoutBudget(Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end) : string;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWithoutBudget(Collection $accounts, Carbon $start, Carbon $end): string;

    /**
     * @param array $data
     *
     * @return Budget
     */
    public function store(array $data): Budget;

    /**
     * @param Budget $budget
     * @param array  $data
     *
     * @return Budget
     */
    public function update(Budget $budget, array $data) : Budget;

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     * @param string $range
     * @param int    $amount
     *
     * @return BudgetLimit
     */
    public function updateLimitAmount(Budget $budget, Carbon $start, Carbon $end, string $range, int $amount) : BudgetLimit;

}

<?php

/**
 * BudgetLimitRepositoryInterface.php
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
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Support\Collection;

/**
 * Interface BudgetLimitRepositoryInterface
 */
interface BudgetLimitRepositoryInterface
{
    /**
     * Tells you which amount has been budgeted (for the given budgets)
     * in the selected query. Returns a positive amount as a string.
     */
    public function budgeted(Carbon $start, Carbon $end, TransactionCurrency $currency, ?Collection $budgets = null): string;

    /**
     * Destroy all budget limits.
     */
    public function destroyAll(): void;

    /**
     * Destroy a budget limit.
     */
    public function destroyBudgetLimit(BudgetLimit $budgetLimit): void;

    public function find(Budget $budget, TransactionCurrency $currency, Carbon $start, Carbon $end): ?BudgetLimit;

    /**
     * TODO this method is not multi currency aware.
     */
    public function getAllBudgetLimits(?Carbon $start = null, ?Carbon $end = null): Collection;

    public function getAllBudgetLimitsByCurrency(TransactionCurrency $currency, ?Carbon $start = null, ?Carbon $end = null): Collection;

    public function getBudgetLimits(Budget $budget, ?Carbon $start = null, ?Carbon $end = null): Collection;

    public function getNoteText(BudgetLimit $budgetLimit): string;

    public function setNoteText(BudgetLimit $budgetLimit, string $text): void;

    public function store(array $data): BudgetLimit;

    public function update(BudgetLimit $budgetLimit, array $data): BudgetLimit;

    public function updateLimitAmount(Budget $budget, Carbon $start, Carbon $end, string $amount): ?BudgetLimit;
}

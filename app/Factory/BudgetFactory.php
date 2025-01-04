<?php

/**
 * BudgetFactory.php
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

namespace FireflyIII\Factory;

use FireflyIII\Models\Budget;
use FireflyIII\User;

/**
 * Class BudgetFactory.
 */
class BudgetFactory
{
    private User $user;

    public function find(?int $budgetId, ?string $budgetName): ?Budget
    {
        $budgetId   = (int) $budgetId;
        $budgetName = (string) $budgetName;

        if (0 === $budgetId && '' === $budgetName) {
            return null;
        }

        // first by ID:
        if ($budgetId > 0) {
            /** @var null|Budget $budget */
            $budget = $this->user->budgets()->find($budgetId);
            if (null !== $budget) {
                return $budget;
            }
        }

        if ('' !== $budgetName) {
            $budget = $this->findByName($budgetName);
            if (null !== $budget) {
                return $budget;
            }
        }

        return null;
    }

    public function findByName(string $name): ?Budget
    {
        /** @var null|Budget */
        return $this->user->budgets()->where('name', $name)->first();
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}

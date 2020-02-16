<?php
/**
 * Budgets.php
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

namespace FireflyIII\Import\Mapper;

use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;

/**
 * Class Budgets.
 */
class Budgets implements MapperInterface
{
    /**
     * Get map of budgets.
     *
     * @return array
     */
    public function getMap(): array
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $result     = $repository->getActiveBudgets();
        $list       = [];

        /** @var Budget $budget */
        foreach ($result as $budget) {
            $budgetId        = (int)$budget->id;
            $list[$budgetId] = $budget->name;
        }
        asort($list);
        $list = [0 => (string)trans('import.map_do_not_map')] + $list;

        return $list;
    }
}

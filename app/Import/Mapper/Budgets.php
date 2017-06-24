<?php
/**
 * Budgets.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Mapper;


use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;

/**
 * Class Budgets
 *
 * @package FireflyIII\Import\Mapper
 */
class Budgets implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap(): array
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $result     = $repository->getBudgets();
        $list       = [];

        /** @var Budget $budget */
        foreach ($result as $budget) {
            $list[$budget->id] = $budget->name;
        }
        asort($list);

        $list = [0 => trans('csv.map_do_not_map')] + $list;

        return $list;

    }
}

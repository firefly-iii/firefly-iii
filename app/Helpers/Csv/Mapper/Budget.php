<?php

namespace FireflyIII\Helpers\Csv\Mapper;

use Auth;
use FireflyIII\Models\Budget as BudgetModel;

/**
 * Class Budget
 *
 * @package FireflyIII\Helpers\Csv\Mapper
 */
class Budget implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap()
    {
        $result = Auth::user()->budgets()->get(['budgets.*']);
        $list   = [];

        /** @var BudgetModel $budget */
        foreach ($result as $budget) {
            $list[$budget->id] = $budget->name;
        }
        asort($list);

        $list = [0 => trans('firefly.csv_do_not_map')] + $list;

        return $list;
    }
}

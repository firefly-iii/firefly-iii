<?php

namespace Firefly\Storage\Budget;


class EloquentBudgetRepository implements BudgetRepositoryInterface
{

    public function getAsSelectList()
    {
        $list = \Auth::user()->budgets()->get();
        $return = [];
        foreach ($list as $entry) {
            $return[intval($entry->id)] = $entry->name;
        }
        return $return;
    }

    public function find($id)
    {

        return \Auth::user()->budgets()->find($id);
    }

} 
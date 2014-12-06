<?php

namespace Firefly\Trigger\Budgets;

use Illuminate\Events\Dispatcher;

/**
 * Class EloquentBudgetTrigger
 *
 * These triggers don't actually DO anything but are here in case it should be necessary to trigger to something
 * anyway. I may have forgotten.
 *
 * @package Firefly\Trigger\Budgets
 */
class EloquentBudgetTrigger
{

    /**
     * Destroying a budget doesn't do much either.
     *
     * @param \Budget $budget
     *
     * @return bool
     */
    public function destroy(\Budget $budget)
    {
        return true;

    }

    /**
     * A new budget is just there, there is nothing to trigger.
     *
     * @param \Budget $budget
     *
     * @return bool
     */
    public function store(\Budget $budget)
    {
        return true;

    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
//        $events->listen('budgets.destroy', 'Firefly\Trigger\Budgets\EloquentBudgetTrigger@destroy');
//        $events->listen('budgets.store', 'Firefly\Trigger\Budgets\EloquentBudgetTrigger@store');
//        $events->listen('budgets.update', 'Firefly\Trigger\Budgets\EloquentBudgetTrigger@update');

    }

    /**
     * Same. Doesn't do much.
     *
     * @param \Budget $budget
     *
     * @return bool
     */
    public function update(\Budget $budget)
    {
        return true;

    }
}

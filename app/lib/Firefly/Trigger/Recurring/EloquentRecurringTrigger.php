<?php

namespace Firefly\Trigger\Recurring;

use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

/**
 * Class EloquentRecurringTrigger
 *
 * @package Firefly\Trigger\Recurring
 */
class EloquentRecurringTrigger
{

    /**
     * @param \RecurringTransaction $recurring
     */
    public function destroy(\RecurringTransaction $recurring)
    {
    }

    /**
     * @param \RecurringTransaction $recurring
     */
    public function store(\RecurringTransaction $recurring)
    {

    }

    public function createReminders()
    {
    }

    /**
     * Trigger!
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
//        $events->listen('recurring.destroy', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@destroy');
//        $events->listen('recurring.store', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@store');
//        $events->listen('recurring.update', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@update');
//        $events->listen('recurring.check', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@createReminders');
    }

    /**
     * @param \RecurringTransaction $recurring
     */
    public function update(\RecurringTransaction $recurring)
    {
    }
} 
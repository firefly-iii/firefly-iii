<?php

namespace Firefly\Trigger\Limits;

use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

/**
 * Class EloquentLimitTrigger
 *
 * @package Firefly\Trigger\Limits
 */
class EloquentLimitTrigger
{

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('budgets.change', 'Firefly\Trigger\Limits\EloquentLimitTrigger@updateLimitRepetitions');

    }

    /**
     *
     */
    public function updateLimitRepetitions()
    {
        if (!\Auth::check() || is_null(\Auth::user())) {
            return;
        }

        // get budgets with limits:
        $budgets = \Auth::user()->budgets()
            ->with(
                ['limits', 'limits.limitrepetitions']
            )
            ->where('components.class', 'Budget')
            ->get(['components.*']);

        // double check the non-repeating budgetlimits first.
        foreach ($budgets as $budget) {
            \Log::debug('Budgetstart: ' . $budget->name);
            foreach ($budget->limits as $limit) {
                if ($limit->repeats == 0) {
                    $limit->createRepetition($limit->startdate);
                }
                if ($limit->repeats == 1) {
                    $start = $limit->startdate;
                    $end = new Carbon;

                    // repeat for period:
                    $current = clone $start;
                    \Log::debug('Create repeating limit for #' . $limit->id . ' starting on ' . $current);
                    while ($current <= $end) {
                        \Log::debug('Current is now: ' . $current);
                        $limit->createRepetition(clone $current);
                        // switch period, add time:
                        switch ($limit->repeat_freq) {
                            case 'daily':
                                $current->addDay();
                                break;
                            case 'weekly':
                                $current->addWeek();
                                break;
                            case 'monthly':
                                $current->addMonth();
                                break;
                            case 'quarterly':
                                $current->addMonths(3);
                                break;
                            case 'half-year':
                                $current->addMonths(6);
                                break;
                            case 'yearly':
                                $current->addYear();
                                break;
                        }

                    }
                }
            }

        }
    }
}

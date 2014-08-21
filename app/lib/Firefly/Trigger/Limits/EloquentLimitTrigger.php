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
     * This trigger checks if any budgets have limits that repeat every period.
     * If there are, this method will create any missing repetitions.
     */
    public function checkRepeatingLimits()
    {
        if (\Auth::check()) {
            $limits = \Limit::leftJoin('components', 'components.id', '=', 'limits.component_id')
                ->where('components.user_id', \Auth::user()->id)
                ->where('limits.repeats', 1)
                ->get(['limits.*']);
        } else {
            $limits = [];
        }

        /** @var \Limit $limit */
        foreach ($limits as $limit) {
            // the limit repeats, and there should be at least one repetition already.
            /** @var \LimitRepetition $primer */
            $primer = $limit->limitrepetitions()->orderBy('startdate', 'DESC')->first();
            $today = new Carbon;
            $start = clone $primer->enddate;
            // from the primer onwards.
            while ($start <= $today) {
                $start->addDay();
                $end = clone $start;

                // add period to determin end of limitrepetition:
                switch ($limit->repeat_freq) {
                    case 'daily':
                        $end->addDay();
                        break;
                    case 'weekly':
                        $end->addWeek();
                        break;
                    case 'monthly':
                        $end->addMonth();
                        break;
                    case 'quarterly':
                        $end->addMonths(3);
                        break;
                    case 'half-year':
                        $end->addMonths(6);
                        break;
                    case 'yearly':
                        $end->addYear();
                        break;
                }
                $end->subDay();
                // create repetition:
                $limit->createRepetition($start);
                $start = clone $end;
            }
        }
    }

    /**
     * @param \Limit $limit
     *
     * @return bool
     */
    public function destroy(\Limit $limit)
    {
        return true;

    }

    /**
     * @param \Limit $limit
     *
     * @return bool
     */
    public function store(\Limit $limit)
    {
        // create a repetition (repetitions) for this limit (we ignore "repeats"):
        $limit->createRepetition($limit->startdate);

        return true;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        //$events->listen('budgets.change', 'Firefly\Trigger\Limits\EloquentLimitTrigger@updateLimitRepetitions');
        $events->listen('limits.destroy', 'Firefly\Trigger\Limits\EloquentLimitTrigger@destroy');
        $events->listen('limits.store', 'Firefly\Trigger\Limits\EloquentLimitTrigger@store');
        $events->listen('limits.update', 'Firefly\Trigger\Limits\EloquentLimitTrigger@update');
        $events->listen('limits.check', 'Firefly\Trigger\Limits\EloquentLimitTrigger@checkRepeatingLimits');

    }

    /**
     * @param \Limit $limit
     *
     * @return bool
     */
    public function update(\Limit $limit)
    {
        // remove and recreate limit repetitions.
        // if limit is not repeating, simply update the repetition to match the limit,
        // even though deleting everything is easier.
        foreach ($limit->limitrepetitions()->get() as $l) {
            $l->delete();
        }
        $limit->createRepetition($limit->startdate);

        return true;
    }
}

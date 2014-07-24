<?php

namespace Firefly\Trigger\Limits;

/**
 * Class EloquentLimitTrigger
 *
 * @package Firefly\Trigger\Limits
 */
class EloquentLimitTrigger
{

    public function updateLimitRepetitions()
    {
        if (!\Auth::check()) {
            return;
        }

        // get budgets with limits:
        $budgets = \Auth::user()->budgets()
            ->with(['limits', 'limits.limitrepetitions'])
            ->whereNotNull('limits.id')
            ->leftJoin('limits', 'components.id', '=', 'limits.component_id')->get(['components.*']);

        // get todays date.

        foreach ($budgets as $budget) {
            // loop limits:
            foreach ($budget->limits as $limit) {
                // should have a repetition, at the very least
                // for the period it starts (startdate and onwards).
                if (count($limit->limitrepetitions) == 0) {
                    // create such a repetition:
                    $repetition = new \LimitRepetition();
                    $start = clone $limit->startdate;
                    $end = clone $start;

                    // go to end:
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
                    $repetition->startdate = $start;
                    $repetition->enddate = $end;
                    $repetition->amount = $limit->amount;
                    $repetition->limit()->associate($limit);

                    try {
                        $repetition->save();
                    } catch (\Illuminate\Database\QueryException $e) {
                        // do nothing
                        \Log::error($e->getMessage());
                    }
                } else {
                    // there are limits already, do they
                    // fall into the range surrounding today?
                    $today = new \Carbon\Carbon;
                    $today->addMonths(2);
                    if ($limit->repeats == 1 && $today >= $limit->startdate) {

                        /** @var \Carbon\Carbon $flowStart */
                        $flowStart = clone $today;
                        /** @var \Carbon\Carbon $flowEnd */
                        $flowEnd = clone $today;

                        switch ($limit->repeat_freq) {
                            case 'daily':
                                $flowStart->startOfDay();
                                $flowEnd->endOfDay();
                                break;
                            case 'weekly':
                                $flowStart->startOfWeek();
                                $flowEnd->endOfWeek();
                                break;
                            case 'monthly':
                                $flowStart->startOfMonth();
                                $flowEnd->endOfMonth();
                                break;
                            case 'quarterly':
                                $flowStart->firstOfQuarter();
                                $flowEnd->startOfMonth()->lastOfQuarter()->endOfDay();
                                break;
                            case 'half-year':

                                if (intval($flowStart->format('m')) >= 7) {
                                    $flowStart->startOfYear();
                                    $flowStart->addMonths(6);
                                } else {
                                    $flowStart->startOfYear();
                                }

                                $flowEnd->endOfYear();
                                if (intval($start->format('m')) <= 6) {
                                    $flowEnd->subMonths(6);
                                    $flowEnd->subDay();

                                }
                                break;
                            case 'yearly':
                                $flowStart->startOfYear();
                                $flowEnd->endOfYear();
                                break;
                        }

                        $inRange = false;
                        foreach ($limit->limitrepetitions as $rep) {
                            if ($rep->startdate->format('dmY') == $flowStart->format('dmY')
                                && $rep->enddate->format('dmY') == $flowEnd->format('dmY')
                            ) {
                                // falls in current range, do nothing?
                                $inRange = true;
                            }
                        }
                        // if there is none that fall in range, create!
                        if ($inRange === false) {
                            // create (but check first)!
                            $count = \LimitRepetition::where('limit_id', $limit->id)->where('startdate', $flowStart)
                                ->where('enddate', $flowEnd)->count();
                            if ($count == 0) {
                                $repetition = new \LimitRepetition;
                                $repetition->startdate = $flowStart;
                                $repetition->enddate = $flowEnd;
                                $repetition->amount = $limit->amount;
                                $repetition->limit()->associate($limit);
                                try {
                                    $repetition->save();
                                } catch (\Illuminate\Database\QueryException $e) {
                                    // do nothing
                                    \Log::error($e->getMessage());
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function subscribe(\Illuminate\Events\Dispatcher $events)
    {
        $events->listen('app.before', 'Firefly\Trigger\Limits\EloquentLimitTrigger@updateLimitRepetitions');

    }

}

\Limit::observe(new EloquentLimitTrigger);
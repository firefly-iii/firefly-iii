<?php

namespace Firefly\Trigger\Piggybanks;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Events\Dispatcher;

/**
 * Class EloquentPiggybankTrigger
 *
 * @package Firefly\Trigger\Piggybanks
 */
class EloquentPiggybankTrigger
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            'piggybanks.change', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@updatePiggybankRepetitions'
        );

    }

    /**
     *
     */
    public function updatePiggybankRepetitions()
    {
        // grab all piggy banks.
        $piggybanks = \Auth::user()->piggybanks()->with(['piggybankrepetitions'])->where('repeats', 0)->get();
        $today = new Carbon;
        /** @var \Piggybank $piggy */
        foreach ($piggybanks as $piggy) {
            if (count($piggy->piggybankrepetitions) == 0) {
                $rep = new \PiggybankRepetition;
                $rep->piggybank()->associate($piggy);
                $rep->targetdate = $piggy->targetdate;
                $rep->startdate = $piggy->startdate;
                $rep->currentamount = 0;
                try {
                    $rep->save();
                } catch (QueryException $e) {
                }
            }
        }
        unset($piggy, $piggybanks, $rep);

        // grab all repeated transactions.
        $repeatedExpenses = \Auth::user()->piggybanks()->with(['piggybankrepetitions'])->where('repeats', 1)->get();
        /** @var \Piggybank $repeated */
        foreach ($repeatedExpenses as $repeated) {
            // loop from start to today or something
            $rep = new \PiggybankRepetition;
            $rep->piggybank()->associate($repeated);
            $rep->startdate = $repeated->startdate;
            $rep->targetdate = $repeated->targetdate;
            $rep->currentamount = 0;
            try {
                \Log::debug(
                    'Creating initial rep ('.$repeated->name.') (from ' . ($rep->startdate ? $rep->startdate->format('d-m-Y')
                        : 'NULL') . ' to '
                    . ($rep->targetdate ? $rep->targetdate->format('d-m-Y') : 'NULL') . ')'
                );
                $rep->save();
            } catch (QueryException $e) {
                \Log::error('FAILED initital repetition.');
            }
            unset($rep);

            if ($repeated->targetdate <= $today) {
                // add 1 month to startdate, or maybe X period, like 3 weeks.
                $startTarget = clone $repeated->targetdate;
                while ($startTarget <= $today) {
                    $startCurrent = clone $startTarget;

                    // add some kind of period to start current making $endCurrent.
                    $endCurrent = clone $startCurrent;
                    switch ($repeated->rep_length) {
                        default:
                            die('No rep lengt!');
                            break;
                        case 'day':
                            $endCurrent->addDays($repeated->rep_every);
                            break;
                        case 'week':
                            $endCurrent->addWeeks($repeated->rep_every);
                            break;
                        case 'month':
                            $endCurrent->addMonths($repeated->rep_every);
                            break;
                        case 'year':
                            $endCurrent->addYears($repeated->rep_every);
                            break;
                    }

                    $rep = new \PiggybankRepetition;
                    $rep->piggybank()->associate($repeated);
                    $rep->startdate = $startCurrent;
                    $rep->targetdate = $endCurrent;
                    $rep->currentamount = 0;
                    $startTarget = $endCurrent;
                    try {
                        $rep->save();
                    } catch (QueryException $e) {

                    }
                }
            }
        }
    }
}
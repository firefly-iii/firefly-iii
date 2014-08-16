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

            // whatever we did here, we now have all repetitions for this
            // piggy bank, and we can find transactions that fall within
            // that repetition (to fix the "saved amount".
            $reps = $piggy->piggybankrepetitions()->get();
            /** @var \PiggybankRepetition $rep */
            foreach ($reps as $rep) {
                $sum = \Transaction::where('piggybank_id', $piggy->id)->leftJoin(
                        'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
                    )->where('transaction_journals.date', '>=', $rep->startdate->format('Y-m-d'))->where(
                        'transaction_journals.date', '<=', $rep->targetdate->format('Y-m-d')
                    )->sum('transactions.amount');
                $rep->currentamount = floatval($sum);
                $rep->save();


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
                $rep->save();
            } catch (QueryException $e) {
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
            $reps = $repeated->piggybankrepetitions()->get();
            /** @var \PiggybankRepetition $rep */
            foreach ($reps as $rep) {
                $sum = \Transaction::where('piggybank_id', $repeated->id)->leftJoin(
                    'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
                )->where('transaction_journals.date', '>=', $rep->startdate->format('Y-m-d'))->where(
                        'transaction_journals.date', '<=', $rep->targetdate->format('Y-m-d')
                    )->sum('transactions.amount');
                $rep->currentamount = floatval($sum);
                $rep->save();


            }
        }
    }
}
<?php

namespace Firefly\Trigger\Piggybanks;

use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

/**
 * Class EloquentPiggybankTrigger
 *
 * @package Firefly\Trigger\Piggybanks
 */
class EloquentPiggybankTrigger
{
    /**
     * @param \Piggybank $piggyBank
     * @param \TransactionJournal $journal
     */
    public function createRelatedTransfer(
        \Piggybank $piggyBank, \TransactionJournal $journal, \Transaction $transaction
    )
    {
        $repetition = $piggyBank->repetitionForDate($journal->date);
        if (!is_null($repetition)) {
            // get the amount transferred TO this
            $amount = floatval($transaction->amount);
            $repetition->currentamount += $amount;
            $repetition->save();
        } else {
            \Session::flash('warning', 'Cannot add transfer to piggy, outside of scope.');
        }

        return true;
    }

    /**
     * @param \Piggybank $piggyBank
     *
     * @return bool
     */
    public function destroy(\Piggybank $piggyBank)
    {
        return true;
    }

    /**
     * @param \Piggybank $piggyBank
     * @param            $amount
     */
    public function modifyAmountAdd(\Piggybank $piggyBank, $amount)
    {
        $rep = $piggyBank->currentRelevantRep();
        $today = new Carbon;

        // create event:
        $event = new \PiggybankEvent;
        $event->date = new Carbon;
        $event->amount = $amount;
        $event->piggybank()->associate($piggyBank);

        // for future / past repetitions.
        if (!($rep->startdate >= $today && $rep->targetdate <= $today)) {
            $event->date = $rep->startdate;
        }


        $event->save();
    }

    /**
     * @param \Piggybank $piggyBank
     * @param            $amount
     */
    public function modifyAmountRemove(\Piggybank $piggyBank, $amount)
    {
        // create event:
        $event = new \PiggybankEvent;
        $event->date = new Carbon;
        $event->amount = $amount;
        $event->piggybank()->associate($piggyBank);
        $event->save();
    }

    /**
     * @param \Piggybank $piggyBank
     */
    public function storePiggy(\Piggybank $piggyBank)
    {
        $rep = new \PiggybankRepetition;
        $rep->piggybank()->associate($piggyBank);
        $rep->targetdate = $piggyBank->targetdate;
        $rep->startdate = $piggyBank->startdate;
        $rep->currentamount = 0;
        $rep->save();

        return true;

    }

    /**
     * @param \Piggybank $piggyBank
     *
     * @return bool
     */
    public function storeRepeated(\Piggybank $piggyBank)
    {
        // loop from start to today or something
        $rep = new \PiggybankRepetition;
        $rep->piggybank()->associate($piggyBank);
        $rep->startdate = $piggyBank->startdate;
        $rep->targetdate = $piggyBank->targetdate;
        $rep->currentamount = 0;
        $rep->save();
        unset($rep);
        $today = new Carbon;

        if ($piggyBank->targetdate <= $today) {
            // add 1 month to startdate, or maybe X period, like 3 weeks.
            $startTarget = clone $piggyBank->targetdate;
            while ($startTarget <= $today) {
                $startCurrent = clone $startTarget;

                // add some kind of period to start current making $endCurrent.
                $endCurrent = clone $startCurrent;
                switch ($piggyBank->rep_length) {
                    default:
                        return true;
                        break;
                    case 'day':
                        $endCurrent->addDays($piggyBank->rep_every);
                        break;
                    case 'week':
                        $endCurrent->addWeeks($piggyBank->rep_every);
                        break;
                    case 'month':
                        $endCurrent->addMonths($piggyBank->rep_every);
                        break;
                    case 'year':
                        $endCurrent->addYears($piggyBank->rep_every);
                        break;
                }

                $rep = new \PiggybankRepetition;
                $rep->piggybank()->associate($piggyBank);
                $rep->startdate = $startCurrent;
                $rep->targetdate = $endCurrent;
                $rep->currentamount = 0;
                $startTarget = $endCurrent;
                $rep->save();
            }
        }

        return true;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('piggybanks.destroy', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@destroy');

        $events->listen(
            'piggybanks.modifyAmountAdd', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@modifyAmountAdd'
        );
        $events->listen(
            'piggybanks.modifyAmountRemove', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@modifyAmountRemove'
        );
        $events->listen('piggybanks.storePiggy', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@storePiggy');
        $events->listen(
            'piggybanks.storeRepeated', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@storeRepeated'
        );
        $events->listen('piggybanks.update', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@update');
        $events->listen(
            'piggybanks.createRelatedTransfer',
            'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@createRelatedTransfer'
        );
        $events->listen(
            'piggybanks.updateRelatedTransfer',
            'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@updateRelatedTransfer'
        );
    }

    public function update(\Piggybank $piggyBank)
    {
        // delete all repetitions:
        foreach ($piggyBank->piggybankrepetitions()->get() as $rep) {
            $rep->delete();
        }
        unset($rep);

        // trigger "new" piggy bank to recreate them.
        if ($piggyBank->repeats == 1) {
            \Event::fire('piggybanks.storeRepeated', [$piggyBank]);
        } else {
            \Event::fire('piggybanks.storePiggy', [$piggyBank]);
        }
        // loop the repetitions and update them according to the events and the transactions:
        foreach ($piggyBank->piggybankrepetitions()->get() as $rep) {
            // SUM for transactions
            $query = \Transaction::where('piggybank_id', $piggyBank->id)->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=',
                'transactions.transaction_journal_id'
            );
            if (!is_null($rep->startdate)) {
                $query->where('transaction_journals.date', '>=', $rep->startdate->format('Y-m-d'));
            }
            if (!is_null($rep->targetdate)) {
                $query->where(
                    'transaction_journals.date', '<=', $rep->targetdate->format('Y-m-d')
                );
            }
            $sum = $query->sum('transactions.amount');

            // get events for piggy bank, save those as well:
            $eventSumQuery = $piggyBank->piggybankevents();
            if (!is_null($rep->startdate)) {
                $eventSumQuery->where('date', '>=', $rep->startdate->format('Y-m-d'));
            }
            if (!is_null($rep->targetdate)) {
                $eventSumQuery->where('date', '<=', $rep->targetdate->format('Y-m-d'));
            }
            $eventSum = floatval($eventSumQuery->sum('amount'));
            $rep->currentamount = floatval($sum) + $eventSum;
            $rep->save();

        }
    }

    public function updateRelatedTransfer(\Piggybank $piggyBank)
    {
        // fire the "update" trigger which should handle things just fine:
        \Event::fire('piggybanks.update', [$piggyBank]);
    }






//
//    /**
//     *
//     */
//    public function updatePiggybankRepetitions()
//    {
//        // grab all piggy banks.
//        if (\Auth::check()) {
//            $piggybanks = \Auth::user()->piggybanks()->with(['piggybankrepetitions'])->where('repeats', 0)->get();
//            $today = new Carbon;
//            /** @var \Piggybank $piggy */
//            foreach ($piggybanks as $piggy) {
//                if (count($piggy->piggybankrepetitions) == 0) {
//                    $rep = new \PiggybankRepetition;
//                    $rep->piggybank()->associate($piggy);
//                    $rep->targetdate = $piggy->targetdate;
//                    $rep->startdate = $piggy->startdate;
//                    $rep->currentamount = 0;
//                    try {
//                        $rep->save();
//                    } catch (QueryException $e) {
//                    }
//                }
//
//                // whatever we did here, we now have all repetitions for this
//                // piggy bank, and we can find transactions that fall within
//                // that repetition (to fix the "saved amount".
//                $reps = $piggy->piggybankrepetitions()->get();
//
//                /** @var \PiggybankRepetition $rep */
//                foreach ($reps as $rep) {
//                    $query = \Transaction::where('piggybank_id', $piggy->id)->leftJoin(
//                        'transaction_journals', 'transaction_journals.id', '=',
//                        'transactions.transaction_journal_id'
//                    );
//                    if (!is_null($rep->startdate)) {
//                        $query->where('transaction_journals.date', '>=', $rep->startdate->format('Y-m-d'));
//                    }
//                    if (!is_null($rep->targetdate)) {
//                        $query->where(
//                            'transaction_journals.date', '<=', $rep->targetdate->format('Y-m-d')
//                        );
//                    }
//
//                    // get events for piggy bank, save those as well:
//                    $eventSumQuery = $piggy->piggybankevents();
//                    if(!is_null($rep->startdate)) {
//                        $eventSumQuery->where('date','>=',$rep->startdate->format('Y-m-d'));
//                    }
//                    if(!is_null($rep->targetdate)) {
//                        $eventSumQuery->where('date','<=',$rep->targetdate->format('Y-m-d'));
//                    }
//                    $eventSum = floatval($eventSumQuery->sum('amount'));
//
//
//                    $sum = $query->sum('transactions.amount');
//                    $rep->currentamount = floatval($sum) + $eventSum;
//                    $rep->save();
//
//
//                }
//
//            }
//            unset($piggy, $piggybanks, $rep);
//
//            // grab all repeated transactions.
//            $repeatedExpenses = \Auth::user()->piggybanks()->with(['piggybankrepetitions'])->where('repeats', 1)->get();
//            /** @var \Piggybank $repeated */
//            foreach ($repeatedExpenses as $repeated) {
//                // loop from start to today or something
//                $rep = new \PiggybankRepetition;
//                $rep->piggybank()->associate($repeated);
//                $rep->startdate = $repeated->startdate;
//                $rep->targetdate = $repeated->targetdate;
//                $rep->currentamount = 0;
//                try {
//                    $rep->save();
//                } catch (QueryException $e) {
//                }
//                unset($rep);
//
//                if ($repeated->targetdate <= $today) {
//                    // add 1 month to startdate, or maybe X period, like 3 weeks.
//                    $startTarget = clone $repeated->targetdate;
//                    while ($startTarget <= $today) {
//                        $startCurrent = clone $startTarget;
//
//                        // add some kind of period to start current making $endCurrent.
//                        $endCurrent = clone $startCurrent;
//                        switch ($repeated->rep_length) {
//                            default:
//                                die('No rep lengt!');
//                                break;
//                            case 'day':
//                                $endCurrent->addDays($repeated->rep_every);
//                                break;
//                            case 'week':
//                                $endCurrent->addWeeks($repeated->rep_every);
//                                break;
//                            case 'month':
//                                $endCurrent->addMonths($repeated->rep_every);
//                                break;
//                            case 'year':
//                                $endCurrent->addYears($repeated->rep_every);
//                                break;
//                        }
//
//                        $rep = new \PiggybankRepetition;
//                        $rep->piggybank()->associate($repeated);
//                        $rep->startdate = $startCurrent;
//                        $rep->targetdate = $endCurrent;
//                        $rep->currentamount = 0;
//                        $startTarget = $endCurrent;
//                        try {
//                            $rep->save();
//                        } catch (QueryException $e) {
//
//                        }
//                    }
//                }
//                $reps = $repeated->piggybankrepetitions()->get();
//                /** @var \PiggybankRepetition $rep */
//                foreach ($reps as $rep) {
//                    $sum = \Transaction::where('piggybank_id', $repeated->id)->leftJoin(
//                        'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
//                    )->where('transaction_journals.date', '>=', $rep->startdate->format('Y-m-d'))->where(
//                        'transaction_journals.date', '<=', $rep->targetdate->format('Y-m-d')
//                    )->sum('transactions.amount');
//                    $rep->currentamount = floatval($sum);
//                    $rep->save();
//
//
//                }
//            }
//        }
//    }
}
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
     * This method checks every repeating piggy bank the user has (these are called repeated expenses) and makes
     * sure each repeated expense has a "repetition" for the current time period. For example, if the user has
     * a weekly repeated expense of E 40,- this method will fire every week and create a new repetition.
     */
    public function checkRepeatingPiggies()
    {

        if (\Auth::check()) {
            $piggies = \Auth::user()->piggybanks()->where('repeats', 1)->get();
        } else {
            $piggies = [];
        }

        \Log::debug('Now in checkRepeatingPiggies with ' . count($piggies) . ' piggies found.');

        /** @var \Piggybank $piggyBank */
        foreach ($piggies as $piggyBank) {
            \Log::debug('Now working on ' . $piggyBank->name);

            /*
             * Get the latest repetition, see if Firefly needs to create more.
             */
            /** @var \PiggybankRepetition $primer */
            $primer = $piggyBank->piggybankrepetitions()->orderBy('targetdate', 'DESC')->first();
            \Log::debug('Last target date is: ' . $primer->targetdate);

            $today = new Carbon;

            // the next repetition must be created starting at the day after the target date of the previous one.
            /*
             * A repeated expense runs from day 1 to day X. Since it repeats, the next repetition starts at day X+1
             * until however often the repeated expense is set to repeat: a month, a week, a year.
             */
            $start = clone $primer->targetdate;
            $start->addDay();

            while ($start <= $today) {
                \Log::debug('Looping! Start is: ' . $start);

                // to get to the end of the current repetition, we switch on the piggy bank's
                // repetition period:
                $end = clone $start;
                switch ($piggyBank->rep_length) {
                    case 'day':
                        $end->addDays($piggyBank->rep_every);
                        break;
                    case 'week':
                        $end->addWeeks($piggyBank->rep_every);
                        break;
                    case 'month':
                        $end->addMonths($piggyBank->rep_every);
                        break;
                    case 'year':
                        $end->addYears($piggyBank->rep_every);
                        break;
                }
                $end->subDay();

                // create repetition:
                $piggyBank->createRepetition($start, $end);

                $start = clone $end;
                $start->addDay();


            }

        }
    }

    /**
     * @param \Piggybank          $piggyBank
     * @param \TransactionJournal $journal
     * @param \Transaction        $transaction
     *
     * @return bool
     */
    public function createRelatedTransfer(
        \Piggybank $piggyBank, \TransactionJournal $journal, \Transaction $transaction
    ) {
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
     * @param            $amount
     */
    public function modifyAmountAdd(\Piggybank $piggyBank, $amount)
    {
        $rep   = $piggyBank->currentRelevantRep();
        $today = new Carbon;

        // create event:
        $event         = new \PiggybankEvent;
        $event->date   = new Carbon;
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
        $event         = new \PiggybankEvent;
        $event->date   = new Carbon;
        $event->amount = $amount;
        $event->piggybank()->associate($piggyBank);
        $event->save();
    }

    /**
     * This method is called when a piggy bank or repeated expense is created. It will create the first
     * repetition which by default is equal to the PB / RE itself. After that, other triggers will take over.
     *
     * @param \Piggybank $piggyBank
     *
     * @return bool
     */
    public function store(\Piggybank $piggyBank)
    {
        $piggyBank->createRepetition($piggyBank->startdate, $piggyBank->targetdate);

        return true;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            'piggybanks.modifyAmountAdd', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@modifyAmountAdd'
        );
        $events->listen(
            'piggybanks.modifyAmountRemove', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@modifyAmountRemove'
        );
        $events->listen('piggybanks.store', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@store');
        $events->listen('piggybanks.update', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@update');
        $events->listen(
            'piggybanks.createRelatedTransfer',
            'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@createRelatedTransfer'
        );
        $events->listen(
            'piggybanks.updateRelatedTransfer',
            'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@updateRelatedTransfer'
        );
        $events->listen(
            'piggybanks.storepiggybanks.check', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@checkRepeatingPiggies'
        );

    }

    /**
     * When the user updates a piggy bank the repetitions, past and now, may be wrong. The best bet
     * would be to delete everything and start over, but that also means past repetitions will be gone.
     *
     * Instead, we have disabled changing the dates when the piggy bank is repeating: a repeated expense cannot
     * have its dates changed. This will prevent many problems I don't want to deal with.
     *
     * @param \Piggybank $piggyBank
     */
    public function update(\Piggybank $piggyBank)
    {
        // delete all repetitions:
        foreach ($piggyBank->piggybankrepetitions()->get() as $rep) {

            $rep->delete();
        }
        unset($rep);

        // trigger "new" piggy bank to recreate them.
        \Event::fire('piggybanks.store', [$piggyBank]);


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
            $eventSum           = floatval($eventSumQuery->sum('amount'));
            $rep->currentamount = floatval($sum) + $eventSum;
            $rep->save();

        }
    }

    public function updateRelatedTransfer(\Piggybank $piggyBank)
    {
        // fire the "update" trigger which should handle things just fine:
        \Event::fire('piggybanks.update', [$piggyBank]);
    }


}
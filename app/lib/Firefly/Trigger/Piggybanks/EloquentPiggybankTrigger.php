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
     *
     */
    public function checkRepeatingPiggies()
    {

        if (\Auth::check()) {
            $piggies = \Auth::user()->piggybanks()->where('repeats', 1)->get();
        } else {
            $piggies = [];
        }

        \Log::debug('Now in checkRepeatingPiggies with ' . count($piggies) . ' piggies');


        /** @var \Piggybank $piggyBank */
        foreach ($piggies as $piggyBank) {
            \Log::debug('Now working on ' . $piggyBank->name);

            // get the latest repetition, see if we need to "append" more:
            /** @var \PiggybankRepetition $primer */
            $primer = $piggyBank->piggybankrepetitions()->orderBy('targetdate', 'DESC')->first();
            \Log::debug('Last target date is: ' . $primer->targetdate);

            // for repeating piggy banks, the target date is mandatory:

            $today = new Carbon;
            $end = clone $primer->targetdate;

            // the next repetition must be created starting at the day after the target date:
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
     * Whenever a repetition is made, the decision is there to make reminders for it. Or not.
     *
     * Some combinations are "invalid" or impossible and will never trigger reminders. Others do.
     *
     * @param \PiggybankRepetition $rep
     *
     * @return null
     */
    public function createdRepetition(\PiggybankRepetition $repetition)
    {

        $piggyBank = $repetition->piggybank;

        // first, exclude all combinations that will not generate (valid) reminders

        // no reminders needed (duh)
        if (is_null(($piggyBank->reminder))) {
            return null;
        }

        // no start, no target, no repeat (#1):
        if (is_null($piggyBank->startdate) && is_null($piggyBank->targetdate) && $piggyBank->repeats == 0) {
            return null;
        }

        // no start, but repeats (#5):
        if (is_null($piggyBank->startdate) && $piggyBank->repeats == 1) {
            return null;
        }

        // no start, no end, but repeats (#6)
        if (is_null($piggyBank->startdate) && is_null($piggyBank->targetdate) && $piggyBank->repeats == 1) {
            return null;
        }

        // no end, but repeats (#7)
        if (is_null($piggyBank->targetdate) && $piggyBank->repeats == 1) {
            return null;
        }

        // #2, #3, #4 and #8 are valid combo's.
        if (is_null($repetition->targetdate)) {
            $end = new Carbon;
            $end->addYears(2);
        } else {
            $end = $repetition->targetdate;
        }
        if (is_null($repetition->startdate)) {
            $start = new Carbon;
        } else {
            $start = $repetition->startdate;
        }


        $current = $start;
        $today = new Carbon;
        while ($current <= $end) {

            // when do we start reminding?
            // X days before $current:
            $reminderStart = clone $current;
            switch ($piggyBank->reminder) {
                case 'day':
                    $reminderStart->subDay();
                    break;
                case 'week':
                    $reminderStart->subDays(4);
                    break;
                case 'month':
                    $reminderStart->subDays(21);
                    break;
                case 'year':
                    $reminderStart->subMonths(9);
                    break;
            }

            if ($current >= $today) {
                $reminder = new \PiggybankReminder;
                $reminder->piggybank()->associate($piggyBank);
                $reminder->user()->associate(\Auth::user());
                $reminder->startdate = $reminderStart;
                $reminder->enddate = $current;
                try {
                    $reminder->save();

                } catch (QueryException $e) {
                }
            }


            switch ($piggyBank->reminder) {
                case 'day':
                    $current->addDays($piggyBank->reminder_skip);
                    break;
                case 'week':
                    $current->addWeeks($piggyBank->reminder_skip);
                    break;
                case 'month':
                    $current->addMonths($piggyBank->reminder_skip);
                    break;
                case 'year':
                    $current->addYears($piggyBank->reminder_skip);
                    break;
            }
        }

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
        $events->listen('piggybanks.destroy', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@destroy');
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
            'piggybanks.check', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@checkRepeatingPiggies'
        );

        $events->listen(
            'piggybanks.repetition', 'Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger@createdRepetition'
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


}
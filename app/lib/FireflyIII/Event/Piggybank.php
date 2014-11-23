<?php

namespace FireflyIII\Event;


use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

class Piggybank
{

    /**
     * @param \Piggybank $piggybank
     * @param float      $amount
     */
    public function addMoney(\Piggybank $piggybank, $amount = 0.0)
    {
        if ($amount > 0) {
            $event = new \PiggybankEvent;
            $event->piggybank()->associate($piggybank);
            $event->amount = floatval($amount);
            $event->date   = new Carbon;
            if (!$event->validate()) {
                var_dump($event->errors());
                exit();
            }
            $event->save();
        }
    }

    public function destroyTransfer(\TransactionJournal $journal)
    {
        if ($journal->piggybankevents()->count() > 0) {

            /** @var \FireflyIII\Database\Piggybank $repository */
            $repository = \App::make('FireflyIII\Database\Piggybank');

            /** @var \Piggybank $piggyBank */
            $piggyBank = $journal->piggybankevents()->first()->piggybank()->first();

            /** @var \PiggybankRepetition $repetition */
            $repetition = $repository->findRepetitionByDate($piggyBank, $journal->date);

            $relevantTransaction = null;
            /** @var \Transaction $transaction */
            foreach ($journal->transactions as $transaction) {
                if ($transaction->account_id == $piggyBank->account_id) {
                    $relevantTransaction = $transaction;
                }
            }
            if (is_null($relevantTransaction)) {
                return;
            }

            $repetition->currentamount += floatval($relevantTransaction->amount * -1);
            $repetition->save();


            $event = new \PiggybankEvent;
            $event->piggybank()->associate($piggyBank);
            $event->amount = floatval($relevantTransaction->amount * -1);
            $event->date   = new Carbon;
            if (!$event->validate()) {
                var_dump($event->errors());
                exit();
            }
            $event->save();
        }
    }

    /**
     * @param \Piggybank $piggybank
     * @param float      $amount
     */
    public function removeMoney(\Piggybank $piggybank, $amount = 0.0)
    {
        $amount = $amount * -1;
        if ($amount < 0) {
            $event = new \PiggybankEvent;
            $event->piggybank()->associate($piggybank);
            $event->amount = floatval($amount);
            $event->date   = new Carbon;
            if (!$event->validate()) {
                var_dump($event->errors());
                exit();
            }
            $event->save();
        }
    }

    public function storePiggybank(\Piggybank $piggybank)
    {
        if (intval($piggybank->repeats) == 0) {
            $repetition = new \PiggybankRepetition;
            $repetition->piggybank()->associate($piggybank);
            $repetition->startdate     = $piggybank->startdate;
            $repetition->targetdate    = $piggybank->targetdate;
            $repetition->currentamount = 0;
            $repetition->save();
        }
    }

    /*
     *
     */

    /**
     * @param \TransactionJournal $journal
     * @param int                 $piggybankId
     */
    public function storeTransfer(\TransactionJournal $journal, $piggybankId = 0)
    {
        if ($piggybankId == 0 || is_null($piggybankId)) {
            return;
        }
        /** @var \FireflyIII\Database\Piggybank $repository */
        $repository = \App::make('FireflyIII\Database\Piggybank');

        /** @var \Piggybank $piggyBank */
        $piggyBank = $repository->find($piggybankId);

        /** @var \PiggybankRepetition $repetition */
        $repetition = $repository->findRepetitionByDate($piggyBank, $journal->date);

        \Log::debug(
            'Connecting transfer "' . $journal->description . '" (#' . $journal->id . ') to piggy bank "' . $piggyBank->name . '" (#' . $piggyBank->id . ').'
        );

        // some variables to double check the connection.
        $start               = $piggyBank->startdate;
        $end                 = $piggyBank->targetdate;
        $amount              = floatval($piggyBank->targetamount);
        $leftToSave          = $amount - floatval($repetition->currentamount);
        $relevantTransaction = null;
        /** @var \Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            if ($transaction->account_id == $piggyBank->account_id) {
                $relevantTransaction = $transaction;
            }
        }
        if (is_null($relevantTransaction)) {
            return;
        }
        \Log::debug('Relevant transaction is #' . $relevantTransaction->id . ' with amount ' . $relevantTransaction->amount);

        // if FF3 should save this connection depends on some variables:
        if ($start && $end && $journal->date >= $start && $journal->date <= $end) {
            if ($relevantTransaction->amount < 0) { // amount removed from account, so removed from piggy bank.
                \Log::debug('Remove from piggy bank.');
                $continue = ($relevantTransaction->amount * -1 <= floatval($repetition->currentamount));
                \Log::debug(
                    'relevantTransaction.amount *-1 = ' . ($relevantTransaction->amount * -1) . ' >= current = ' . floatval($repetition->currentamount)
                );
            } else { // amount added
                \Log::debug('Add from piggy bank.');
                $continue = $relevantTransaction->amount <= $leftToSave;
            }
            if ($continue) {
                \Log::debug('Update repetition.');
                $repetition->currentamount += floatval($relevantTransaction->amount);
                $repetition->save();

                $event = new \PiggybankEvent;
                $event->piggybank()->associate($piggyBank);
                $event->transactionjournal()->associate($journal);
                $event->amount = floatval($relevantTransaction->amount);
                $event->date   = new Carbon;
                if (!$event->validate()) {
                    var_dump($event->errors());
                    exit();
                }
                $event->save();
            }
        }
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        // triggers on piggy bank events:
        $events->listen('piggybank.addMoney', 'FireflyIII\Event\Piggybank@addMoney');
        $events->listen('piggybank.removeMoney', 'FireflyIII\Event\Piggybank@removeMoney');
        $events->listen('piggybank.store', 'FireflyIII\Event\Piggybank@storePiggybank');
        $events->listen('piggybank.update', 'FireflyIII\Event\Piggybank@updatePiggybank');

        \App::before(
            function ($request) {
                $this->validateRepeatedExpenses();
            }
        );

        //$events->listen('piggybank.boo', 'FireflyIII\Event\Piggybank@updatePiggybank');


        // triggers when others are updated.
        $events->listen('transactionJournal.store', 'FireflyIII\Event\Piggybank@storeTransfer');
        $events->listen('transactionJournal.update', 'FireflyIII\Event\Piggybank@updateTransfer');
        $events->listen('transactionJournal.destroy', 'FireflyIII\Event\Piggybank@destroyTransfer');
    }

    /**
     * Validates the presence of repetitions for all repeated expenses!
     */
    public function validateRepeatedExpenses()
    {
        if(!\Auth::check()) {
            return;
        }
        /** @var \FireflyIII\Database\RepeatedExpense $repository */
        $repository = \App::make('FireflyIII\Database\RepeatedExpense');

        $list  = $repository->get();
        $today = Carbon::now();

        /** @var \Piggybank $entry */
        foreach ($list as $entry) {
            $start  = $entry->startdate;
            $target = $entry->targetdate;
            // find a repetition on this date:
            $count = $entry->piggybankrepetitions()->starts($start)->targets($target)->count();
            if ($count == 0) {
                $repetition = new \PiggybankRepetition;
                $repetition->piggybank()->associate($entry);
                $repetition->startdate     = $start;
                $repetition->targetdate    = $target;
                $repetition->currentamount = 0;
                $repetition->save();
            }
            // then continue and do something in the current relevant timeframe.

            $currentTarget = clone $target;
            $currentStart  = null;
            while ($currentTarget < $today) {
                $currentStart  = \DateKit::subtractPeriod($currentTarget, $entry->rep_length, 0);
                $currentTarget = \DateKit::addPeriod($currentTarget, $entry->rep_length, 0);
                // create if not exists:
                $count = $entry->piggybankrepetitions()->starts($currentStart)->targets($currentTarget)->count();
                if ($count == 0) {
                    $repetition = new \PiggybankRepetition;
                    $repetition->piggybank()->associate($entry);
                    $repetition->startdate     = $currentStart;
                    $repetition->targetdate    = $currentTarget;
                    $repetition->currentamount = 0;
                    $repetition->save();
                }

            }
        }
    }

    public function updatePiggybank(\Piggybank $piggyBank)
    {
        // get the repetition:
        $repetition             = $piggyBank->currentRelevantRep();
        $repetition->startdate  = $piggyBank->startdate;
        $repetition->targetdate = $piggyBank->targetdate;
        $repetition->save();
    }

    public function updateTransfer(\TransactionJournal $journal)
    {

        if ($journal->piggybankevents()->count() > 0) {

            $event    = $journal->piggybankevents()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->first();
            $eventSum = floatval($journal->piggybankevents()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->sum('amount'));

            /** @var \FireflyIII\Database\Piggybank $repository */
            $repository = \App::make('FireflyIII\Database\Piggybank');

            /** @var \Piggybank $piggyBank */
            $piggyBank = $journal->piggybankevents()->first()->piggybank()->first();

            /** @var \PiggybankRepetition $repetition */
            $repetition = $repository->findRepetitionByDate($piggyBank, $journal->date);

            $relevantTransaction = null;
            /** @var \Transaction $transaction */
            foreach ($journal->transactions as $transaction) {
                if ($transaction->account_id == $piggyBank->account_id) {
                    $relevantTransaction = $transaction;
                }
            }
            if (is_null($relevantTransaction)) {
                return;
            }

            $diff = floatval($relevantTransaction->amount) - floatval($eventSum);
            /*
             * Create an event to remove /add the difference from the piggy
             */
            $repetition->currentamount += $diff;
            $repetition->save();


            $event = new \PiggybankEvent;
            $event->piggybank()->associate($piggyBank);
            $event->transactionJournal()->associate($journal);
            $event->amount = $diff;
            $event->date   = new Carbon;
            if (!$event->validate()) {
                var_dump($event->errors());
                exit();
            }
            $event->save();
        }

    }
} 
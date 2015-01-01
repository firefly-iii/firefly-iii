<?php

namespace FireflyIII\Event;


use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

/**
 * Class PiggyBank
 *
 * @package FireflyIII\Event
 */
class PiggyBank
{

    /**
     * @param \PiggyBank $piggyBank
     * @param float      $amount
     */
    public function addMoney(\PiggyBank $piggyBank, $amount = 0.0)
    {
        if ($amount > 0) {
            $event = new \PiggyBankEvent;
            $event->piggyBank()->associate($piggyBank);
            $event->amount = floatval($amount);
            $event->date   = new Carbon;
            if (!$event->isValid()) {
                \Log::error($event->getErrors());
                \App::abort(500);
            }
            $event->save();
        }
    }

    /**
     * @param \TransactionJournal $journal
     *
     * @throws \FireflyIII\Exception\FireflyException
     * @throws \FireflyIII\Exception\NotImplementedException
     */
    public function destroyTransfer(\TransactionJournal $journal)
    {
        if ($journal->piggyBankEvents()->count() > 0) {

            /** @var \FireflyIII\Database\PiggyBank\PiggyBank $repository */
            $repository = \App::make('FireflyIII\Database\PiggyBank\PiggyBank');

            /** @var \PiggyBank $piggyBank */
            $piggyBank = $journal->piggyBankEvents()->first()->piggyBank()->first();

            /** @var \PiggyBankRepetition $repetition */
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


            $event = new \PiggyBankEvent;
            $event->piggyBank()->associate($piggyBank);
            $event->amount = floatval($relevantTransaction->amount * -1);
            $event->date   = new Carbon;
            $event->save();
        }
    }

    /**
     * @param \PiggyBank $piggyBank
     * @param float      $amount
     */
    public function removeMoney(\PiggyBank $piggyBank, $amount = 0.0)
    {
        $amount = $amount * -1;
        if ($amount < 0) {
            $event = new \PiggyBankEvent;
            $event->piggyBank()->associate($piggyBank);
            $event->amount = floatval($amount);
            $event->date   = new Carbon;
            $event->save();
        }
    }

    /**
     * @param \PiggyBank $piggyBank
     */
    public function storePiggyBank(\PiggyBank $piggyBank)
    {
        $repetition = new \PiggyBankRepetition;
        $repetition->piggyBank()->associate($piggyBank);
        $repetition->startdate     = $piggyBank->startdate;
        $repetition->targetdate    = $piggyBank->targetdate;
        $repetition->currentamount = 0;
        $repetition->save();
    }

    /*
     *
     */

    /**
     * @param \TransactionJournal $journal
     * @param int                 $piggyBankId
     */
    public function storeTransfer(\TransactionJournal $journal, $piggyBankId = 0)
    {
        if (intval($piggyBankId) == 0) {
            return;
        }
        /** @var \FireflyIII\Database\PiggyBank\PiggyBank $repository */
        $repository = \App::make('FireflyIII\Database\PiggyBank\PiggyBank');

        /** @var \PiggyBank $piggyBank */
        $piggyBank = $repository->find($piggyBankId);

        if ($journal->transactions()->where('account_id', $piggyBank->account_id)->count() == 0) {
            return;
        }
        /** @var \PiggyBankRepetition $repetition */
        $repetition  = $repository->findRepetitionByDate($piggyBank, $journal->date);
        $amount      = floatval($piggyBank->targetamount);
        $leftToSave  = $amount - floatval($repetition->currentamount);
        $transaction = $journal->transactions()->where('account_id', $piggyBank->account_id)->first();
        // must be in range of journal. Continue determines if we can move it.
        if (floatval($transaction->amount < 0)) {
            // amount removed from account, so removed from piggy bank.
            $continue = ($transaction->amount * -1 <= floatval($repetition->currentamount));
        } else {
            // amount added
            $continue = $transaction->amount <= $leftToSave;
        }
        if ($continue) {
            \Log::debug('Update repetition.');
            $repetition->currentamount += floatval($transaction->amount);
            $repetition->save();
            $event = new \PiggyBankEvent;
            $event->piggyBank()->associate($piggyBank);
            $event->transactionjournal()->associate($journal);
            $event->amount = floatval($transaction->amount);
            $event->date   = new Carbon;
            $event->save();
        }
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        // triggers on piggy bank events:
        $events->listen('piggy_bank.addMoney', 'FireflyIII\Event\PiggyBank@addMoney');
        $events->listen('piggy_bank.removeMoney', 'FireflyIII\Event\PiggyBank@removeMoney');
        $events->listen('piggy_bank.store', 'FireflyIII\Event\PiggyBank@storePiggyBank');
        $events->listen('piggy_bank.update', 'FireflyIII\Event\PiggyBank@updatePiggyBank');

        \App::before(
            function () {
                $this->validateRepeatedExpenses();
            }
        );

        // triggers when others are updated.
        $events->listen('transactionJournal.store', 'FireflyIII\Event\PiggyBank@storeTransfer');
        $events->listen('transactionJournal.update', 'FireflyIII\Event\PiggyBank@updateTransfer');
        $events->listen('transactionJournal.destroy', 'FireflyIII\Event\PiggyBank@destroyTransfer');
    }

    /**
     * Validates the presence of repetitions for all repeated expenses!
     */
    public function validateRepeatedExpenses()
    {
        if (!\Auth::check()) {
            return;
        }
        /** @var \FireflyIII\Database\PiggyBank\RepeatedExpense $repository */
        $repository = \App::make('FireflyIII\Database\PiggyBank\RepeatedExpense');

        $list  = $repository->get();
        $today = Carbon::now();

        /** @var \PiggyBank $entry */
        foreach ($list as $entry) {
            $start  = $entry->startdate;
            $target = $entry->targetdate;
            // find a repetition on this date:
            $count = $entry->piggyBankrepetitions()->starts($start)->targets($target)->count();
            if ($count == 0) {
                $repetition = new \PiggyBankRepetition;
                $repetition->piggyBank()->associate($entry);
                $repetition->startdate     = $start;
                $repetition->targetdate    = $target;
                $repetition->currentamount = 0;
                $repetition->save();
            }
            // then continue and do something in the current relevant time frame.

            $currentTarget = clone $target;
            $currentStart  = null;
            while ($currentTarget < $today) {
                $currentStart  = \DateKit::subtractPeriod($currentTarget, $entry->rep_length, 0);
                $currentTarget = \DateKit::addPeriod($currentTarget, $entry->rep_length, 0);
                // create if not exists:
                $count = $entry->piggyBankRepetitions()->starts($currentStart)->targets($currentTarget)->count();
                if ($count == 0) {
                    $repetition = new \PiggyBankRepetition;
                    $repetition->piggyBank()->associate($entry);
                    $repetition->startdate     = $currentStart;
                    $repetition->targetdate    = $currentTarget;
                    $repetition->currentamount = 0;
                    $repetition->save();
                }

            }
        }
    }

    /**
     * @param \PiggyBank $piggyBank
     */
    public function updatePiggyBank(\PiggyBank $piggyBank)
    {
        // get the repetition:
        $repetition             = $piggyBank->currentRelevantRep();
        $repetition->startdate  = $piggyBank->startdate;
        $repetition->targetdate = $piggyBank->targetdate;
        $repetition->save();
    }

    /**
     * @param \TransactionJournal $journal
     *
     * @throws \FireflyIII\Exception\FireflyException
     * @throws \FireflyIII\Exception\NotImplementedException
     */
    public function updateTransfer(\TransactionJournal $journal)
    {

        if ($journal->piggyBankEvents()->count() > 0) {

            $event    = $journal->piggyBankEvents()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->first();
            $eventSum = floatval($journal->piggyBankEvents()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->sum('amount'));

            /** @var \FireflyIII\Database\PiggyBank\PiggyBank $repository */
            $repository = \App::make('FireflyIII\Database\PiggyBank\PiggyBank');

            /** @var \PiggyBank $piggyBank */
            $piggyBank = $journal->piggyBankEvents()->first()->piggyBank()->first();

            /** @var \PiggyBankRepetition $repetition */
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


            $event = new \PiggyBankEvent;
            $event->piggyBank()->associate($piggyBank);
            $event->transactionJournal()->associate($journal);
            $event->amount = $diff;
            $event->date   = new Carbon;
            if (!$event->isValid()) {
                \Log::error($event->getErrors());
                \App::abort(500);
            }
            $event->save();
        }

    }
} 
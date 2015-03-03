<?php namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\JournalSaved;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;

class UpdateJournalConnection
{

    /**
     * Create the event handler.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  JournalSaved $event
     *
     * @return void
     */
    public function handle(JournalSaved $event)
    {
        $journal = $event->journal;

        // get the event connected to this journal:
        /** @var PiggyBankEvent $event */
        $event      = PiggyBankEvent::where('transaction_journal_id', $journal->id)->first();
        if(is_null($event)) {
            return;
        }
        $piggyBank  = $event->piggyBank()->first();
        $repetition = $piggyBank->piggyBankRepetitions()->relevantOnDate($journal->date)->first();

        if (is_null($repetition)) {
            return;
        }
        $amount = 0;
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            if ($transaction->account_id === $piggyBank->account_id) {
                // this transaction is the relevant one.
                $amount = floatval($transaction->amount);
            }
        }

        // update current repetition:
        $diff = $amount - $event->amount;

        $repetition->currentamount += $diff;
        $repetition->save();


        $event->amount = $amount;
        $event->save();
    }

}

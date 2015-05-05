<?php namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\JournalSaved;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;

/**
 * Class UpdateJournalConnection
 *
 * @package FireflyIII\Handlers\Events
 */
class UpdateJournalConnection
{

    /**
     * Create the event handler.
     *
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
        $event = PiggyBankEvent::where('transaction_journal_id', $journal->id)->first();
        if (is_null($event)) {
            return;
        }
        $piggyBank  = $event->piggyBank()->first();
        $repetition = $piggyBank->piggyBankRepetitions()->relevantOnDate($journal->date)->first();

        if (is_null($repetition)) {
            return;
        }
        $amount = $journal->amount;
        $diff   = $amount - $event->amount;// update current repetition

        $repetition->currentamount += $diff;
        $repetition->save();


        $event->amount = $amount;
        $event->save();
    }

}

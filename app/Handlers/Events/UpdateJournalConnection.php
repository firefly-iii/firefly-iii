<?php
declare(strict_types = 1);
namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\TransactionJournalUpdated;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\PiggyBankRepetition;

/**
 * Class UpdateJournalConnection
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Handlers\Events
 */
class UpdateJournalConnection
{

    /**
     * Handle the event.
     *
     * @param  TransactionJournalUpdated $event
     *
     * @return bool
     */
    public function handle(TransactionJournalUpdated $event):bool
    {
        $journal = $event->journal;

        // get the event connected to this journal:
        /** @var PiggyBankEvent $event */
        $event = PiggyBankEvent::where('transaction_journal_id', $journal->id)->first();
        if (is_null($event)) {
            return false;
        }
        $piggyBank  = $event->piggyBank()->first();
        $repetition = null;
        if ($piggyBank) {
            /** @var PiggyBankRepetition $repetition */
            $repetition = $piggyBank->piggyBankRepetitions()->relevantOnDate($journal->date)->first();
        }

        if (is_null($repetition)) {
            return false;
        }
        bcscale(2);

        $amount = $journal->amount; // TODO TransactionJournal cannot deliver "amount".
        $diff   = bcsub($amount, $event->amount); // update current repetition

        $repetition->currentamount = bcadd($repetition->currentamount, $diff);
        $repetition->save();


        $event->amount = $amount;
        $event->save();

        return true;
    }

}

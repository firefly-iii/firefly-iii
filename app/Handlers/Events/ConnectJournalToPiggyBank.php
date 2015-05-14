<?php namespace FireflyIII\Handlers\Events;

use Auth;
use FireflyIII\Events\JournalCreated;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class ConnectJournalToPiggyBank
 *
 * @package FireflyIII\Handlers\Events
 */
class ConnectJournalToPiggyBank
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
     * Handle the event when journal is saved.
     *
     * @param  JournalCreated $event
     *
     * @return boolean
     */
    public function handle(JournalCreated $event)
    {
        /** @var TransactionJournal $journal */
        $journal     = $event->journal;
        $piggyBankId = $event->piggyBankId;
        if (intval($piggyBankId) < 1) {
            return false;
        }

        Log::debug('JournalCreated event: ' . $journal->id . ', ' . $piggyBankId);

        /** @var PiggyBank $piggyBank */
        $piggyBank = Auth::user()->piggybanks()->where('piggy_banks.id', $piggyBankId)->first(['piggy_banks.*']);

        if (is_null($piggyBank) || $journal->transactionType->type != 'Transfer') {
            return false;
        }
        Log::debug('Found a piggy bank');
        $amount = $journal->amount;
        Log::debug('Amount: ' . $amount);
        if ($amount == 0) {
            return false;
        }
        // update piggy bank rep for date of transaction journal.
        $repetition = $piggyBank->piggyBankRepetitions()->relevantOnDate($journal->date)->first();
        if (is_null($repetition)) {
            Log::debug('Found no repetition for piggy bank for date ' . $journal->date->format('Y M d'));

            return false;
        }

        Log::debug('Found rep! ' . $repetition->id);

        /*
         * Add amount when
         */
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            if ($transaction->account_id == $piggyBank->account_id) {
                if ($transaction->amount < 0) {
                    $amount = $amount * -1;
                    Log::debug('Transaction is away from piggy, so amount becomes ' . $amount);
                } else {
                    Log::debug('Transaction is to from piggy, so amount stays ' . $amount);
                }
            }
        }

        $repetition->currentamount += $amount;
        $repetition->save();

        PiggyBankEvent::create(
            [
                'piggy_bank_id'          => $piggyBank->id,
                'transaction_journal_id' => $journal->id,
                'date'                   => $journal->date,
                'amount'                 => $amount
            ]
        );

        return true;

    }


}

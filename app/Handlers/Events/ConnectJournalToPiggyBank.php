<?php
declare(strict_types = 1);

namespace FireflyIII\Handlers\Events;

use Auth;
use FireflyIII\Events\TransactionJournalStored;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\TransactionJournal;

/**
 * Class ConnectJournalToPiggyBank
 *
 * @package FireflyIII\Handlers\Events
 */
class ConnectJournalToPiggyBank
{

    /**
     * Connect a new transaction journal to any related piggy banks.
     *
     * @param  TransactionJournalStored $event
     *
     * @return bool
     */
    public function handle(TransactionJournalStored $event): bool
    {
        /** @var TransactionJournal $journal */
        $journal     = $event->journal;
        $piggyBankId = $event->piggyBankId;

        /** @var PiggyBank $piggyBank */
        $piggyBank = Auth::user()->piggybanks()->where('piggy_banks.id', $piggyBankId)->first(['piggy_banks.*']);

        if (is_null($piggyBank)) {
            return true;
        }
        // update piggy bank rep for date of transaction journal.
        $repetition = $piggyBank->piggyBankRepetitions()->relevantOnDate($journal->date)->first();
        if (is_null($repetition)) {
            return true;
        }

        $amount = TransactionJournal::amountPositive($journal);
        // if piggy account matches source account, the amount is positive
        // TODO support split journals
        if ($piggyBank->account_id == TransactionJournal::sourceAccount($journal)->id) {
            $amount = bcmul($amount, '-1');
        }


        $repetition->currentamount = bcadd($repetition->currentamount, $amount);
        $repetition->save();

        PiggyBankEvent::create(['piggy_bank_id' => $piggyBank->id, 'transaction_journal_id' => $journal->id, 'date' => $journal->date, 'amount' => $amount]);

        return true;

    }


}

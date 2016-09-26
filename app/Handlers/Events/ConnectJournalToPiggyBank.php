<?php
/**
 * ConnectJournalToPiggyBank.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Handlers\Events;

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
        $piggyBank = auth()->user()->piggyBanks()->where('piggy_banks.id', $piggyBankId)->first(['piggy_banks.*']);

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
        $sources = TransactionJournal::sourceAccountList($journal)->pluck('id')->toArray();
        if (in_array($piggyBank->account_id, $sources)) {
            $amount = bcmul($amount, '-1');
        }


        $repetition->currentamount = bcadd($repetition->currentamount, $amount);
        $repetition->save();

        PiggyBankEvent::create(['piggy_bank_id' => $piggyBank->id, 'transaction_journal_id' => $journal->id, 'date' => $journal->date, 'amount' => $amount]);

        return true;

    }


}

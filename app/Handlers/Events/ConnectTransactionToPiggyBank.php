<?php
declare(strict_types = 1);


/**
 * ConnectTransactionToPiggyBank.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\TransactionJournalStored;
use FireflyIII\Events\TransactionStored;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;

/**
 * Class ConnectTransactionToPiggyBank
 *
 * @package FireflyIII\Handlers\Events
 */
class ConnectTransactionToPiggyBank
{

    /**
     * Connect a new transaction journal to any related piggy banks.
     *
     * @param  TransactionStored $event
     *
     * @return bool
     */
    public function handle(TransactionStored $event): bool
    {
        echo '<pre>';
        /** @var PiggyBankRepositoryInterface $repository */
        $repository  = app(PiggyBankRepositoryInterface::class);
        $transaction = $event->transaction;
        $piggyBank   = $repository->find($transaction['piggy_bank_id']);

        // valid piggy:
        if (is_null($piggyBank->id)) {
            return true;
        }
        $amount = strval($transaction['amount']);
        // piggy bank account something with amount:
        if ($transaction['source_account_id'] == $piggyBank->account_id) {
            // if the source of this transaction is the same as the piggy bank,
            // the money is being removed from the piggy bank. So the
            // amount must be negative:
            $amount = bcmul($amount, '-1');
        }

        $repetition = $piggyBank->currentRelevantRep();
        // add or remove the money from the piggy bank:
        $newAmount                 = bcadd(strval($repetition->currentamount), $amount);
        $repetition->currentamount = $newAmount;
        $repetition->save();

        // now generate a piggy bank event:
        PiggyBankEvent::create(['piggy_bank_id' => $piggyBank->id, 'date' => $transaction['date'], 'amount' => $newAmount]);

        return true;
    }


}

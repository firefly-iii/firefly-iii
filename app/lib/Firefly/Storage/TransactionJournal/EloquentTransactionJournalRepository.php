<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 03/07/14
 * Time: 15:24
 */

namespace Firefly\Storage\TransactionJournal;


class EloquentTransactionJournalRepository implements TransactionJournalRepositoryInterface
{

    public function createSimpleJournal(\Account $from, \Account $to, $description, $amount, \Carbon\Carbon $date)
    {


        /*
         * We're building this thinking the money goes from A to B.
         * If the amount is negative however, the money still goes
         * from A to B but the balances are reversed.
         *
         * Aka:
         *
         * Amount = 200
         * A loses 200 (-200).  * -1
         * B gains 200 (200).    * 1
         *
         * Final balance: -200 for A, 200 for B.
         *
         * When the amount is negative:
         *
         * Amount = -200
         * A gains 200 (200). * -1
         * B loses 200 (-200). * 1
         *
         */

        // amounts:
        $amountFrom = $amount * -1;
        $amountTo = $amount;

        // account types for both:
        $toAT = $to->accountType->description;
        $fromAT = $from->accountType->description;

        $journalType = null;

        switch (true) {
            // is withdrawal from one of your own accounts:
            case ($fromAT == 'Default account'):
                $journalType = \TransactionType::where('type', 'Withdrawal')->first();
                break;
            // both are yours:
            case ($fromAT == 'Default account' && $toAT == 'Default account'):
                // determin transaction type. If both accounts are new, it's an initial
                // balance transfer.
                $journalType = \TransactionType::where('type', 'Transfer')->first();
                break;
            case ($from->transactions()->count() == 0 && $to->transactions()->count() == 0):
                $journalType = \TransactionType::where('type', 'Opening balance')->first();
                break;
            default:
                // is deposit into one of your own accounts:
            case ($toAT == 'Default account'):
                $journalType = \TransactionType::where('type', 'Deposit')->first();
                break;
        }
        if (is_null($journalType)) {
            \Log::error('Could not figure out transacion type!');
            throw new \Firefly\Exception\FireflyException('Could not figure out transaction type.');
        }

        // always the same currency:
        $currency = \TransactionCurrency::where('code', 'EUR')->first();
        if (is_null($currency)) {
            \Log::error('No currency for journal!');
            throw new \Firefly\Exception\FireflyException('No currency for journal!');
        }

        // new journal:
        $journal = new \TransactionJournal();
        $journal->transactionType()->associate($journalType);
        $journal->transactionCurrency()->associate($currency);
        $journal->completed = false;
        $journal->description = $description;
        $journal->date = $date;
        if (!$journal->isValid()) {
            \Log::error('Cannot create valid journal.');
            \Log::error('Errors: ' . print_r($journal->validator->messages()->all(), true));
            throw new \Firefly\Exception\FireflyException('Cannot create valid journal.');
        }
        $journal->save();

        // create transactions:
        $fromTransaction = new \Transaction;
        $fromTransaction->account()->associate($from);
        $fromTransaction->transactionJournal()->associate($journal);
        $fromTransaction->description = null;
        $fromTransaction->amount = $amountFrom;
        if (!$fromTransaction->isValid()) {
            \Log::error('Cannot create valid transaction (from) for journal #' . $journal->id);
            \Log::error('Errors: ' . print_r($fromTransaction->validator->messages()->all(), true));
            throw new \Firefly\Exception\FireflyException('Cannot create valid transaction (from).');
        }
        $fromTransaction->save();

        $toTransaction = new \Transaction;
        $toTransaction->account()->associate($to);
        $toTransaction->transactionJournal()->associate($journal);
        $toTransaction->description = null;
        $toTransaction->amount = $amountTo;
        if (!$toTransaction->isValid()) {
            if (!$toTransaction->isValid()) {
                \Log::error('Cannot create valid transaction (to) for journal #' . $journal->id);
                \Log::error('Errors: ' . print_r($toTransaction->validator->messages()->all(), true));
                throw new \Firefly\Exception\FireflyException('Cannot create valid transaction (to).');
            }
        }
        $toTransaction->save();

        $journal->completed = true;
        $journal->save();
        return;


        echo 'saved!';

    }
} 
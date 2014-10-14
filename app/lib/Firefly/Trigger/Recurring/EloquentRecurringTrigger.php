<?php

namespace Firefly\Trigger\Recurring;

use Carbon\Carbon;
use Firefly\Exception\FireflyException;
use Illuminate\Events\Dispatcher;

/**
 * Class EloquentRecurringTrigger
 *
 * @package Firefly\Trigger\Recurring
 */
class EloquentRecurringTrigger
{

    /**
     * @param \RecurringTransaction $recurring
     */
    public function destroy(\RecurringTransaction $recurring)
    {
    }

    /**
     * @param \RecurringTransaction $recurring
     */
    public function store(\RecurringTransaction $recurring)
    {

    }

    /**
     * @param \RecurringTransaction $recurring
     * @param \TransactionJournal $journal
     */
    public function rescan(\RecurringTransaction $recurring, \TransactionJournal $journal)
    {
        /*
         * Match words.
         */
        $wordMatch   = false;
        $matches     = explode(' ', $recurring->match);
        $description = strtolower($journal->description);

        /*
         * Attach expense account to description for more narrow matching.
         */
        $transactions = $journal->transactions()->get();
        /** @var \Transaction $transaction */
        foreach ($transactions as $transaction) {
            /** @var \Account $account */
            $account = $transaction->account()->first();
            /** @var \AccountType $type */
            $type = $account->accountType()->first();
            if ($type->type == 'Expense account' || $type->type == 'Beneficiary account') {
                $description .= ' ' . strtolower($account->name);
            }
        }

        $count = 0;
        foreach ($matches as $word) {
            if (!(strpos($description, strtolower($word)) === false)) {
                $count++;
            }
        }
        if ($count >= count($matches)) {
            $wordMatch = true;
        }

        /*
         * Match amount.
         */

        $amountMatch = false;
        if (count($transactions) > 1) {

            $amount = max(floatval($transactions[0]->amount), floatval($transactions[1]->amount));
            $min    = floatval($recurring->amount_min);
            $max    = floatval($recurring->amount_max);
            if ($amount >= $min && $amount <= $max) {
                $amountMatch = true;
            }
        }

        /*
         * If both, update!
         */
        if ($wordMatch && $amountMatch) {
            $journal->recurringTransaction()->associate($recurring);
            $journal->save();
        }

    }

    /**
     * Trigger!
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('recurring.rescan', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@rescan');
    }

    /**
     * @param \RecurringTransaction $recurring
     */
    public function update(\RecurringTransaction $recurring)
    {
    }
} 
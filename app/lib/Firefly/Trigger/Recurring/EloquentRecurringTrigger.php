<?php

namespace Firefly\Trigger\Recurring;

use Carbon\Carbon;
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

    public function createReminders()
    {
    }

    public function rescan(\RecurringTransaction $recurring, \TransactionJournal $journal)
    {
        /*
         * Match words.
         */
        $wordMatch   = false;
        $matches     = explode(' ', $recurring->match);
        $description = strtolower($journal->description);
        $count       = 0;
        foreach ($matches as $word) {
            if (!(strpos($description, $word) === false)) {
                $count++;
            }
        }
        if ($count > 0) {
            $wordMatch = true;
        }

        /*
         * Match amount.
         */
        $transactions = $journal->transactions()->get();
        $amountMatch  = false;
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
        //Event::fire('recurring.rematch', [$recurringTransaction, $journal]);
        $events->listen('recurring.rescan', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@rescan');
//        $events->listen('recurring.destroy', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@destroy');
//        $events->listen('recurring.store', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@store');
//        $events->listen('recurring.update', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@update');
//        $events->listen('recurring.check', 'Firefly\Trigger\Recurring\EloquentRecurringTrigger@createReminders');
    }

    /**
     * @param \RecurringTransaction $recurring
     */
    public function update(\RecurringTransaction $recurring)
    {
    }
} 
<?php

namespace Firefly\Trigger\Journals;

use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

/**
 * Class EloquentJournalTrigger
 *
 * @package Firefly\Trigger\Journals
 */
class EloquentJournalTrigger
{

    /**
     * @param \TransactionJournal $journal
     *
     * @return bool
     */
    public function store(\TransactionJournal $journal)
    {
        // select all reminders for recurring transactions:
        if ($journal->transaction_type->type == 'Withdrawal') {
            \Log::debug('Trigger on the creation of a withdrawal');
            $transaction = $journal->transactions()->orderBy('amount', 'DESC')->first();
            $amount      = floatval($transaction->amount);
            $description = strtolower($journal->description);
            $beneficiary = strtolower($transaction->account->name);

            // make an array of parts:
            $parts   = explode(' ', $description);
            $parts[] = $beneficiary;
            $today   = new Carbon;
            $set     = \RecurringTransactionReminder::
                leftJoin(
                    'recurring_transactions', 'recurring_transactions.id', '=', 'reminders.recurring_transaction_id'
                )
                ->where('startdate', '<', $today->format('Y-m-d'))
                ->where('enddate', '>', $today->format('Y-m-d'))
                ->where('amount_min', '<=', $amount)
                ->where('amount_max', '>=', $amount)->get(['reminders.*']);
            /** @var \RecurringTransctionReminder $reminder */
            \Log::debug('Have these parts to search for: ' . join('/',$parts));
            \Log::debug('Found ' . count($set).' possible matching recurring transactions');
            foreach ($set as $index => $reminder) {
                /** @var \RecurringTransaction $RT */
                $RT         = $reminder->recurring_transaction;
                $matches    = explode(' ', strtolower($RT->match));
                \Log::debug($index.': ' . join('/',$matches));
                $matchCount = 0;
                foreach ($parts as $part) {
                    if (in_array($part, $matches)) {
                        $matchCount++;
                    }
                }
                if ($matchCount >= count($matches)) {
                    // we have a match!
                    \Log::debug(
                        'Match between new journal "' . join('/', $parts) . '" and RT ' . join('/', $matches) . '.'
                    );
                    $journal->recurringTransaction()->associate($RT);
                    $journal->save();
                    // also update the reminder.
                    $reminder->active = 0;
                    $reminder->save();
                    return true;
                }
            }
        }
        return true;

    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('journals.store', 'Firefly\Trigger\Journals\EloquentJournalTrigger@store');
        $events->listen('journals.update', 'Firefly\Trigger\Journals\EloquentJournalTrigger@update');

    }

    /**
     * @param \TransactionJournal $journal
     *
     * @return bool
     */
    public function update(\TransactionJournal $journal)
    {
        return true;

    }

} 
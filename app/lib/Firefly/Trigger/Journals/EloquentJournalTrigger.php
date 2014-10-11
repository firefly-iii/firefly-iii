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
        /*
         * Grab all recurring events.
         */
        $set = $journal->user()->first()->recurringtransactions()->get();
        $result = [];
        /*
         * Prep vars
         */
        $description = strtolower($journal->description);

        /** @var \RecurringTransaction $recurring */
        foreach ($set as $recurring) {
            $matches = explode(' ', $recurring->match);

            /*
             * Count the number of matches.
             */
            $count = 0;
            foreach ($matches as $word) {
                if (!(strpos($description, $word) === false)) {
                    $count++;
                    \Log::debug('Recurring transaction #' . $recurring->id . ': word "' . $word . '" found in "' . $description . '".');
                }
            }
            $result[$recurring->id] = $count;
        }
        /*
         * The one with the highest value is the winrar!
         */
        $index = array_search(max($result), $result);

        /*
         * Find the recurring transaction:
         */
        if (count($result[$index]) > 0) {
            $winner = $journal->user()->first()->recurringtransactions()->find($index);
            if ($winner) {
                $journal->recurringTransaction()->associate($winner);
                $journal->save();
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
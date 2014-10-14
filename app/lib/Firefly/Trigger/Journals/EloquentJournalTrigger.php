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
        $set    = $journal->user()->first()->recurringtransactions()->get();
        $result = [];
        /*
         * Prep vars
         */
        $description = strtolower($journal->description);
        $result      = [0 => 0];

        /** @var \RecurringTransaction $recurring */
        foreach ($set as $recurring) {
            \Event::fire('recurring.rescan', [$recurring, $journal]);
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
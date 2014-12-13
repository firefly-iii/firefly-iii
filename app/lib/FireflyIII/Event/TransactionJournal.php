<?php
namespace FireflyIII\Event;


use Illuminate\Events\Dispatcher;

/**
 * Class TransactionJournal
 *
 * @package FireflyIII\Event
 */
class TransactionJournal
{

    /**
     * @param \TransactionJournal $journal
     * @param int                 $id
     */
    public function store(\TransactionJournal $journal)
    {
        /** @var \FireflyIII\Database\Recurring $repository */
        $repository = \App::make('FireflyIII\Database\Recurring');
        $set        = $repository->get();


        /** @var \RecurringTransaction $entry */
        foreach ($set as $entry) {
            $repository->scan($entry, $journal);
        }
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        // triggers when others are updated.
        $events->listen('transactionJournal.store', 'FireflyIII\Event\TransactionJournal@store');
        $events->listen('transactionJournal.update', 'FireflyIII\Event\TransactionJournal@update');
    }

    /**
     * @param \TransactionJournal $journal
     */
    public function update(\TransactionJournal $journal)
    {
        /** @var \FireflyIII\Database\Recurring $repository */
        $repository = \App::make('FireflyIII\Database\Recurring');
        $set        = $repository->get();
        $journal->recurring_transaction_id = null;
        $journal->save();

        /** @var \RecurringTransaction $entry */
        foreach ($set as $entry) {
            $repository->scan($entry, $journal);
        }
    }
} 
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
     */
    public function store(\TransactionJournal $journal)
    {
        /** @var \FireflyIII\Database\Bill\Bill $repository */
        $repository = \App::make('FireflyIII\Database\Bill\Bill');
        $set        = $repository->get();


        /** @var \Bill $entry */
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
        /** @var \FireflyIII\Database\Bill\Bill $repository */
        $repository = \App::make('FireflyIII\Database\Bill\Bill');
        $set        = $repository->get();
        $journal->bill_id = null;
        $journal->save();

        /** @var \Bill $entry */
        foreach ($set as $entry) {
            $repository->scan($entry, $journal);
        }
    }
} 
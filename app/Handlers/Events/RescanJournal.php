<?php namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\TransactionJournalUpdated;
use Log;

/**
 * Class RescanJournal
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Handlers\Events
 */
class RescanJournal
{

    /**
     * Create the event handler.
     *
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  TransactionJournalUpdated $event
     *
     * @return void
     */
    public function handle(TransactionJournalUpdated $event)
    {
        $journal = $event->journal;

        Log::debug('Triggered saved event for journal #' . $journal->id . ' (' . $journal->description . ')');

        /** @var \FireflyIII\Repositories\Bill\BillRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $list       = $journal->user->bills()->where('active', 1)->where('automatch', 1)->get();

        Log::debug('Found ' . $list->count() . ' bills to check.');

        /** @var \FireflyIII\Models\Bill $bill */
        foreach ($list as $bill) {
            Log::debug('Now calling bill #' . $bill->id . ' (' . $bill->name . ')');
            $repository->scan($bill, $journal);
        }

        Log::debug('Done!');
    }

}

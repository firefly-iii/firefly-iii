<?php namespace FireflyIII\Handlers\Events;

use App;
use FireflyIII\Events\JournalSaved;
use Log;

/**
 * Class RescanJournal
 *
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
     * @param  JournalSaved $event
     *
     * @return void
     */
    public function handle(JournalSaved $event)
    {
        $journal = $event->journal;

        Log::debug('Triggered saved event for journal #' . $journal->id . ' (' . $journal->description . ')');

        /** @var \FireflyIII\Repositories\Bill\BillRepositoryInterface $repository */
        $repository = App::make('FireflyIII\Repositories\Bill\BillRepositoryInterface');
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

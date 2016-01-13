<?php
/**
 * RescanJournalAfterStore.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\TransactionJournalStored;
use Log;

/**
 * Class RescanJournal
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Handlers\Events
 */
class ScanForBillsAfterStore
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
     * Scan a transaction journal for possible links to bills, right after storing.
     *
     * @param  TransactionJournalStored $event
     *
     * @return void
     */
    public function handle(TransactionJournalStored $event)
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

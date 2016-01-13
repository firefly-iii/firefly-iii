<?php
/**
 * ScanForBillsAfterUpdate.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\TransactionJournalUpdated;
use Log;

/**
 * Class RescanJournal
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Handlers\Events
 */
class ScanForBillsAfterUpdate
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
     * Scan a transaction journal for possibly related bills after it has been updated.
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

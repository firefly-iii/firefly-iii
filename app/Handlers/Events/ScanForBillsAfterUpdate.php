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
use FireflyIII\Support\Events\BillScanner;
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
        BillScanner::scan($journal);
    }

}

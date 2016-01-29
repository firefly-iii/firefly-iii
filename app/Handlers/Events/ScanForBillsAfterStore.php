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
use FireflyIII\Support\Events\BillScanner;
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
        BillScanner::scan($journal);
    }

}

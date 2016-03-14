<?php
declare(strict_types = 1);
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

/**
 * Class RescanJournal
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Handlers\Events
 */
class ScanForBillsAfterUpdate
{
    /**
     * Scan a transaction journal for possibly related bills after it has been updated.
     *
     * @param  TransactionJournalUpdated $event
     *
     * @return bool
     */
    public function handle(TransactionJournalUpdated $event): bool
    {
        $journal = $event->journal;
        BillScanner::scan($journal);

        return true;
    }

}

<?php
/**
 * ScanForBillsAfterUpdate.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\TransactionJournalUpdated;
use FireflyIII\Support\Events\BillScanner;

/**
 * Class RescanJournal
 *
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

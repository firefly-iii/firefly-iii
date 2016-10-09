<?php
/**
 * ScanForBillsAfterStore.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\TransactionJournalStored;
use FireflyIII\Support\Events\BillScanner;

/**
 * Class RescanJournal
 *
 * @package FireflyIII\Handlers\Events
 */
class ScanForBillsAfterStore
{

    /**
     * Scan a transaction journal for possible links to bills, right after storing.
     *
     * @param  TransactionJournalStored $event
     *
     * @return bool
     */
    public function handle(TransactionJournalStored $event): bool
    {
        $journal = $event->journal;
        BillScanner::scan($journal);

        return true;
    }

}

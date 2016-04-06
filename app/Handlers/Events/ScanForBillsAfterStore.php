<?php
declare(strict_types = 1);
/**
 * RescanJournalAfterStore.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

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

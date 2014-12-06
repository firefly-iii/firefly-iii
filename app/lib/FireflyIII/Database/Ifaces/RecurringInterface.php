<?php

namespace FireflyIII\Database\Ifaces;

use Carbon\Carbon;

/**
 * Interface RecurringInterface
 *
 * @package FireflyIII\Database
 */
interface RecurringInterface
{
    /**
     * @param \RecurringTransaction $recurring
     * @param Carbon                $current
     * @param Carbon                $currentEnd
     *
     * @return \TransactionJournal|null
     */
    public function getJournalForRecurringInRange(\RecurringTransaction $recurring, Carbon $start, Carbon $end);

    /**
     * @param \RecurringTransaction $recurring
     * @param \TransactionJournal   $journal
     *
     * @return bool
     */
    public function scan(\RecurringTransaction $recurring, \TransactionJournal $journal);

    /**
     * @param \RecurringTransaction $recurring
     *
     * @return bool
     */
    public function scanEverything(\RecurringTransaction $recurring);

} 
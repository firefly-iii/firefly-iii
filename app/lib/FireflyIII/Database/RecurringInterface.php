<?php

namespace FireflyIII\Database;
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

} 
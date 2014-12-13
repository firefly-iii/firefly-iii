<?php

namespace FireflyIII\Database\RecurringTransaction;

use Carbon\Carbon;

/**
 * Interface RecurringInterface
 *
 * @package FireflyIII\Database
 */
interface RecurringTransactionInterface
{
    /**
     * @param \RecurringTransaction $recurring
     * @param Carbon                $start
     * @param Carbon                $end
     *
     * @return null|\TransactionJournal
     * @internal param Carbon $current
     * @internal param Carbon $currentEnd
     *
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
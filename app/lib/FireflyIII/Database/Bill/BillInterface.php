<?php

namespace FireflyIII\Database\Bill;

use Carbon\Carbon;

/**
 * Interface BillInterface
 *
 * @package FireflyIII\Database
 */
interface BillInterface
{
    /**
     * @param \Bill  $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return null|\TransactionJournal
     * @internal param Carbon $current
     * @internal param Carbon $currentEnd
     *
     */
    public function getJournalForBillInRange(\Bill $bill, Carbon $start, Carbon $end);

    /**
     * @param \Bill $bill
     *
     * @return Carbon|null
     */
    public function lastFoundMatch(\Bill $bill);

    /**
     * @param \Bill $bill
     *
     * @return Carbon|null
     */
    public function nextExpectedMatch(\Bill $bill);

    /**
     * @param \Bill               $bill
     * @param \TransactionJournal $journal
     *
     * @return bool
     */
    public function scan(\Bill $bill, \TransactionJournal $journal);

    /**
     * @param \Bill $bill
     *
     * @return bool
     */
    public function scanEverything(\Bill $bill);

} 

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

<?php

namespace FireflyIII\Repositories\Bill;

use Carbon\Carbon;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;

/**
 * Interface BillRepositoryInterface
 *
 * @package FireflyIII\Repositories\Bill
 */
interface BillRepositoryInterface {

    /**
     * @param Bill $bill
     *
     * @return Carbon|null
     */
    public function nextExpectedMatch(Bill $bill);

    /**
     * Every bill repeats itself weekly, monthly or yearly (or whatever). This method takes a date-range (usually the view-range of Firefly itself)
     * and returns date ranges that fall within the given range; those ranges are the bills expected. When a bill is due on the 14th of the month and
     * you give 1st and the 31st of that month as argument, you'll get one response, matching the range of your bill.
     *
     * @param Bill $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return mixed
     */
    public function getRanges(Bill $bill, Carbon $start, Carbon $end);

    /**
     * @param array $data
     *
     * @return Bill
     */
    public function store(array $data);

    /**
     * @param Bill  $bill
     * @param array $data
     *
     * @return mixed
     */
    public function update(Bill $bill, array $data);

    /**
     * @param Bill               $bill
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function scan(Bill $bill, TransactionJournal $journal);

}

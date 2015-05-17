<?php

namespace FireflyIII\Repositories\Bill;

use Carbon\Carbon;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;

/**
 * Interface BillRepositoryInterface
 *
 * @package FireflyIII\Repositories\Bill
 */
interface BillRepositoryInterface
{

    /**
     * Returns the sum of all payments connected to this bill between the dates.
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return float
     */
    public function billPaymentsInRange(Bill $bill, Carbon $start, Carbon $end);

    /**
     * Create a fake bill to help the chart controller.
     *
     * @param string $description
     * @param Carbon $date
     * @param float  $amount
     *
     * @return Bill
     */
    public function createFakeBill($description, Carbon $date, $amount);

    /**
     * @param Bill $bill
     *
     * @return mixed
     */
    public function destroy(Bill $bill);

    /**
     * @return Collection
     */
    public function getActiveBills();

    /**
     * @return Collection
     */
    public function getBills();

    /**
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getJournals(Bill $bill);

    /**
     * Get all journals that were recorded on this bill between these dates.
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getJournalsInRange(Bill $bill, Carbon $start, Carbon $end);

    /**
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getPossiblyRelatedJournals(Bill $bill);

    /**
     * Every bill repeats itself weekly, monthly or yearly (or whatever). This method takes a date-range (usually the view-range of Firefly itself)
     * and returns date ranges that fall within the given range; those ranges are the bills expected. When a bill is due on the 14th of the month and
     * you give 1st and the 31st of that month as argument, you'll get one response, matching the range of your bill.
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return mixed
     */
    public function getRanges(Bill $bill, Carbon $start, Carbon $end);

    /**
     * @param Bill $bill
     *
     * @return Carbon|null
     */
    public function lastFoundMatch(Bill $bill);


    /**
     * @param Bill $bill
     *
     * @return Carbon|null
     */
    public function nextExpectedMatch(Bill $bill);

    /**
     * @param Bill               $bill
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function scan(Bill $bill, TransactionJournal $journal);

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

}

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
     * This method will tell you if you still have a CC bill to pay. Amount will be negative if the amount
     * has been paid
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getCreditCardBill(Carbon $start, Carbon $end);

    /**
     * Get the total amount of money paid for the users active bills in the date range given.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsPaidInRange(Carbon $start, Carbon $end);

    /**
     * Get the total amount of money due for the users active bills in the date range given.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsUnpaidInRange(Carbon $start, Carbon $end);

    /**
     * @return Collection
     */
    public function getActiveBills();


    /**
     * @param Bill $bill
     *
     * @return mixed
     */
    public function destroy(Bill $bill);

    /**
     * Returns all journals connected to these bills in the given range. Amount paid
     * is stored in "journalAmount" as a negative number.
     *
     * @param Collection $bills
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function getAllJournalsInRange(Collection $bills, Carbon $start, Carbon $end);


    /**
     * @return Collection
     */
    public function getBills();

    /**
     * Gets the bills which have some kind of relevance to the accounts mentioned.
     *
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function getBillsForAccounts(Collection $accounts);

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
     * you give 1st and the 31st of that month as argument, you'll get one response, matching the range of your bill (from the 14th to the 31th).
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
     * @return \Carbon\Carbon
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

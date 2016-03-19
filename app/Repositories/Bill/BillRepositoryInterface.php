<?php
declare(strict_types = 1);

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
     * @param Bill $bill
     *
     * @return bool
     */
    public function destroy(Bill $bill): bool;

    /**
     * @return Collection
     */
    public function getActiveBills(): Collection;

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
    public function getAllJournalsInRange(Collection $bills, Carbon $start, Carbon $end): Collection;

    /**
     * @return Collection
     */
    public function getBills(): Collection;

    /**
     * Gets the bills which have some kind of relevance to the accounts mentioned.
     *
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function getBillsForAccounts(Collection $accounts): Collection;

    /**
     * Get the total amount of money paid for the users active bills in the date range given.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsPaidInRange(Carbon $start, Carbon $end): string;

    /**
     * Get the total amount of money due for the users active bills in the date range given.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsUnpaidInRange(Carbon $start, Carbon $end): string;

    /**
     * This method will tell you if you still have a CC bill to pay. Amount will be negative if the amount
     * has been paid
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getCreditCardBill(Carbon $start, Carbon $end): string;

    /**
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getJournals(Bill $bill): Collection;

    /**
     * Get all journals that were recorded on this bill between these dates.
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getJournalsInRange(Bill $bill, Carbon $start, Carbon $end): Collection;

    /**
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getPossiblyRelatedJournals(Bill $bill): Collection;

    /**
     * Every bill repeats itself weekly, monthly or yearly (or whatever). This method takes a date-range (usually the view-range of Firefly itself)
     * and returns date ranges that fall within the given range; those ranges are the bills expected. When a bill is due on the 14th of the month and
     * you give 1st and the 31st of that month as argument, you'll get one response, matching the range of your bill (from the 14th to the 31th).
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function getRanges(Bill $bill, Carbon $start, Carbon $end): array;

    /**
     * @param Bill $bill
     *
     * @return \Carbon\Carbon
     */
    public function lastFoundMatch(Bill $bill): Carbon;


    /**
     * @param Bill $bill
     *
     * @return \Carbon\Carbon
     */
    public function nextExpectedMatch(Bill $bill): Carbon;

    /**
     * @param Bill               $bill
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function scan(Bill $bill, TransactionJournal $journal): bool;

    /**
     * @param array $data
     *
     * @return Bill
     */
    public function store(array $data): Bill;

    /**
     * @param Bill  $bill
     * @param array $data
     *
     * @return Bill
     */
    public function update(Bill $bill, array $data): Bill;

}

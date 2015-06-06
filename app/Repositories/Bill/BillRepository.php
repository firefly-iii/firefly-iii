<?php

namespace FireflyIII\Repositories\Bill;

use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;
use Navigation;

/**
 * Class BillRepository
 *
 * @package FireflyIII\Repositories\Bill
 */
class BillRepository implements BillRepositoryInterface
{
    /**
     * Returns the sum of all payments connected to this bill between the dates.
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return integer
     */
    public function billPaymentsInRange(Bill $bill, Carbon $start, Carbon $end)
    {
        $amount   = 0;
        $journals = $bill->transactionjournals()->before($end)->after($start)->get();
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $amount += $journal->amount;
        }

        return $amount;
    }

    /**
     * Create a fake bill to help the chart controller.
     *
     * @param string $description
     * @param Carbon $date
     * @param float  $amount
     *
     * @return Bill
     */
    public function createFakeBill($description, Carbon $date, $amount)
    {
        $bill              = new Bill;
        $bill->name        = $description;
        $bill->match       = $description;
        $bill->amount_min  = $amount;
        $bill->amount_max  = $amount;
        $bill->date        = $date;
        $bill->repeat_freq = 'monthly';
        $bill->skip        = 0;
        $bill->automatch   = false;
        $bill->active      = false;

        return $bill;
    }

    /**
     * @param Bill $bill
     *
     * @return boolean|null
     */
    public function destroy(Bill $bill)
    {
        return $bill->delete();
    }

    /**
     * @return Collection
     */
    public function getActiveBills()
    {
        /** @var Collection $set */
        $set = Auth::user()->bills()->orderBy('name', 'ASC')->where('active', 1)->get()->sortBy('name');

        return $set;
    }

    /**
     * @return Collection
     */
    public function getBills()
    {
        /** @var Collection $set */
        $set = Auth::user()->bills()->orderBy('name', 'ASC')->get()->sortBy('name');

        return $set;
    }

    /**
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getJournals(Bill $bill)
    {
        return $bill->transactionjournals()->withRelevantData()
                    ->orderBy('transaction_journals.date', 'DESC')
                    ->orderBy('transaction_journals.order', 'ASC')
                    ->orderBy('transaction_journals.id', 'DESC')
                    ->get(['transaction_journals.*']);
    }

    /**
     * Get all journals that were recorded on this bill between these dates.
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getJournalsInRange(Bill $bill, Carbon $start, Carbon $end)
    {
        return $bill->transactionjournals()->before($end)->after($start)->get();
    }

    /**
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getPossiblyRelatedJournals(Bill $bill)
    {
        $set = DB::table('transactions')->where('amount', '>', 0)->where('amount', '>=', $bill->amount_min)->where('amount', '<=', $bill->amount_max)->get(
            ['transaction_journal_id']
        );
        $ids = [];

        /** @var Transaction $entry */
        foreach ($set as $entry) {
            $ids[] = intval($entry->transaction_journal_id);
        }
        $journals = new Collection;
        if (count($ids) > 0) {
            $journals = Auth::user()->transactionjournals()->whereIn('id', $ids)->get();
        }

        return $journals;
    }

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
    public function getRanges(Bill $bill, Carbon $start, Carbon $end)
    {
        $startOfBill = $bill->date;
        $startOfBill = Navigation::startOfPeriod($startOfBill, $bill->repeat_freq);


        // all periods of this bill up until the current period:
        $billStarts = [];
        while ($startOfBill < $end) {

            $endOfBill = Navigation::endOfPeriod($startOfBill, $bill->repeat_freq);

            $billStarts[] = [
                'start' => clone $startOfBill,
                'end'   => clone $endOfBill,
            ];
            // actually the next one:
            $startOfBill = Navigation::addPeriod($startOfBill, $bill->repeat_freq, $bill->skip);

        }
        // for each
        $validRanges = [];
        foreach ($billStarts as $dateEntry) {
            if ($dateEntry['end'] > $start && $dateEntry['start'] < $end) {
                // count transactions for bill in this range (not relevant yet!):
                $validRanges[] = $dateEntry;
            }
        }

        return $validRanges;
    }

    /**
     * @param Bill $bill
     *
     * @return Carbon|null
     */
    public function lastFoundMatch(Bill $bill)
    {
        $last = $bill->transactionjournals()->orderBy('date', 'DESC')->first();
        if ($last) {
            return $last->date;
        }

        return null;
    }

    /**
     * @param Bill $bill
     *
     * @return \Carbon\Carbon
     */
    public function nextExpectedMatch(Bill $bill)
    {

        $finalDate = null;
        if ($bill->active == 0) {
            return $finalDate;
        }

        /*
         * $today is the start of the next period, to make sure FF3 won't miss anything
         * when the current period has a transaction journal.
         */
        /** @var \Carbon\Carbon $obj */
        $obj   = new Carbon;
        $today = Navigation::addPeriod($obj, $bill->repeat_freq, 0);

        $skip  = $bill->skip + 1;
        $start = Navigation::startOfPeriod($obj, $bill->repeat_freq);
        /*
         * go back exactly one month/week/etc because FF3 does not care about 'next'
         * bills if they're too far into the past.
         */

        $counter = 0;
        while ($start <= $today) {
            if (($counter % $skip) == 0) {
                // do something.
                $end          = Navigation::endOfPeriod(clone $start, $bill->repeat_freq);
                $journalCount = $bill->transactionjournals()->before($end)->after($start)->count();
                if ($journalCount == 0) {
                    $finalDate = new Carbon($start->format('Y-m-d'));
                    break;
                }
            }

            // add period for next round!
            $start = Navigation::addPeriod($start, $bill->repeat_freq, 0);
            $counter++;
        }

        return $finalDate;
    }

    /**
     * @param Bill               $bill
     * @param TransactionJournal $journal
     *
     * @return boolean|null
     */
    public function scan(Bill $bill, TransactionJournal $journal)
    {
        $amountMatch = false;
        $wordMatch   = false;
        $matches     = explode(',', $bill->match);
        $description = strtolower($journal->description) . ' ' . strtolower($journal->destination_account->name);
        $count       = 0;
        foreach ($matches as $word) {
            if (!(strpos($description, strtolower($word)) === false)) {
                $count++;
            }
        }
        if ($count >= count($matches)) {
            $wordMatch = true;
        }


        /*
         * Match amount.
         */
        if ($journal->amount >= $bill->amount_min && $journal->amount <= $bill->amount_max) {
            $amountMatch = true;
        }

        /*
         * If both, update!
         */
        if ($wordMatch && $amountMatch) {
            $journal->bill()->associate($bill);
            $journal->save();
        } else {
            if ($bill->id == $journal->bill_id) {
                // if no match, but bill used to match, remove it:
                $journal->bill_id = null;
                $journal->save();
            }
        }
    }

    /**
     * @param array $data
     *
     * @return Bill
     */
    public function store(array $data)
    {


        $bill = Bill::create(
            [
                'name'        => $data['name'],
                'match'       => $data['match'],
                'amount_min'  => $data['amount_min'],
                'user_id'     => $data['user'],
                'amount_max'  => $data['amount_max'],
                'date'        => $data['date'],
                'repeat_freq' => $data['repeat_freq'],
                'skip'        => $data['skip'],
                'automatch'   => $data['automatch'],
                'active'      => $data['active'],

            ]
        );

        return $bill;
    }

    /**
     * @param Bill  $bill
     * @param array $data
     *
     * @return Bill
     */
    public function update(Bill $bill, array $data)
    {


        $bill->name        = $data['name'];
        $bill->match       = $data['match'];
        $bill->amount_min  = $data['amount_min'];
        $bill->amount_max  = $data['amount_max'];
        $bill->date        = $data['date'];
        $bill->repeat_freq = $data['repeat_freq'];
        $bill->skip        = $data['skip'];
        $bill->automatch   = $data['automatch'];
        $bill->active      = $data['active'];
        $bill->save();

        return $bill;
    }
}

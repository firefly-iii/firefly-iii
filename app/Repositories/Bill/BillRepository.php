<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Bill;

use Carbon\Carbon;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Navigation;

/**
 * Class BillRepository
 *
 * @package FireflyIII\Repositories\Bill
 */
class BillRepository implements BillRepositoryInterface
{

    /** @var User */
    private $user;

    /**
     * BillRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param Bill $bill
     *
     * @return boolean
     */
    public function destroy(Bill $bill): bool
    {
        $bill->delete();

        return true;
    }

    /**
     * @return Collection
     */
    public function getActiveBills(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->bills()
                          ->where('active', 1)
                          ->get(
                              [
                                  'bills.*',
                                  DB::raw('(`bills`.`amount_min` + `bills`.`amount_max` / 2) as `expectedAmount`'),
                              ]
                          )->sortBy('name');

        return $set;
    }

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
    public function getAllJournalsInRange(Collection $bills, Carbon $start, Carbon $end): Collection
    {
        $ids = $bills->pluck('id')->toArray();

        $set = $this->user->transactionjournals()
                          ->leftJoin(
                              'transactions', function (JoinClause $join) {
                              $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '<', 0);
                          }
                          )
                          ->whereIn('bill_id', $ids)
                          ->before($end)
                          ->after($start)
                          ->groupBy('transaction_journals.bill_id')
                          ->get(
                              [
                                  'transaction_journals.bill_id',
                                  'transaction_journals.id',
                                  DB::raw('SUM(`transactions`.`amount`) as `journalAmount`'),
                              ]
                          );

        return $set;
    }

    /**
     * @return Collection
     */
    public function getBills(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->bills()->orderBy('name', 'ASC')->get();

        $set = $set->sortBy(
            function (Bill $bill) {

                $int = $bill->active == 1 ? 0 : 1;

                return $int . strtolower($bill->name);
            }
        );

        return $set;
    }

    /**
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function getBillsForAccounts(Collection $accounts): Collection
    {
        $ids = $accounts->pluck('id')->toArray();
        $set = $this->user->bills()
                          ->leftJoin(
                              'transaction_journals', function (JoinClause $join) {
                              $join->on('transaction_journals.bill_id', '=', 'bills.id')->whereNull('transaction_journals.deleted_at');
                          }
                          )
                          ->leftJoin(
                              'transactions', function (JoinClause $join) {
                              $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('transactions.amount', '<', 0);
                          }
                          )
                          ->whereIn('transactions.account_id', $ids)
                          ->whereNull('transaction_journals.deleted_at')
                          ->groupBy('bills.id')
                          ->get(['bills.*']);

        $set = $set->sortBy(
            function (Bill $bill) {

                $int = $bill->active == 1 ? 0 : 1;

                return $int . strtolower($bill->name);
            }
        );

        return $set;
    }

    /**
     * Get the total amount of money paid for the users active bills in the date range given.
     * This amount will be negative (they're expenses).
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsPaidInRange(Carbon $start, Carbon $end): string
    {
        $amount = '0';
        $bills  = $this->getActiveBills();

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $ranges = $this->getRanges($bill, $start, $end);

            foreach ($ranges as $range) {
                $paid      = $bill->transactionjournals()
                                  ->before($range['end'])
                                  ->after($range['start'])
                                  ->leftJoin(
                                      'transactions', function (JoinClause $join) {
                                      $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '<', 0);
                                  }
                                  )
                                  ->first([DB::raw('SUM(`transactions`.`amount`) as `sum_amount`')]);
                $sumAmount = $paid->sum_amount ?? '0';
                $amount    = bcadd($amount, $sumAmount);
            }
        }

        return $amount;
    }

    /**
     * Get the total amount of money due for the users active bills in the date range given. This amount will be positive.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsUnpaidInRange(Carbon $start, Carbon $end): string
    {
        $amount = '0';
        $bills  = $this->getActiveBills();

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $ranges   = $this->getRanges($bill, $start, $end);
            $paidBill = '0';
            foreach ($ranges as $range) {
                $paid      = $bill->transactionjournals()
                                  ->before($range['end'])
                                  ->after($range['start'])
                                  ->leftJoin(
                                      'transactions', function (JoinClause $join) {
                                      $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '>', 0);
                                  }
                                  )
                                  ->first([DB::raw('SUM(`transactions`.`amount`) as `sum_amount`')]);
                $sumAmount = $paid->sum_amount ?? '0';
                $paidBill  = bcadd($sumAmount, $paidBill);
            }
            if ($paidBill == 0) {
                $amount = bcadd($amount, $bill->expectedAmount);
            }
        }

        return $amount;
    }

    /**
     * This method will tell you if you still have a CC bill to pay. Amount will be positive if the amount
     * has been paid, otherwise it will be negative.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getCreditCardBill(Carbon $start, Carbon $end): string
    {

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $amount            = '0';
        $creditCards       = $accountRepository->getCreditCards($end); // Find credit card accounts and possibly unpaid credit card bills.
        /** @var Account $creditCard */
        foreach ($creditCards as $creditCard) {
            if ($creditCard->balance == 0) {
                // find a transfer TO the credit card which should account for anything paid. If not, the CC is not yet used.
                $set = TransactionJournal::whereIn(
                    'transaction_journals.id', function (Builder $q) use ($creditCard, $start, $end) {
                    $q->select('transaction_journals.id')
                      ->from('transactions')
                      ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                      ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                      ->where('transactions.account_id', $creditCard->id)
                      ->where('transactions.amount', '>', 0)// this makes the filter unnecessary.
                      ->where('transaction_journals.user_id', $this->user->id)
                      ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                      ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                      ->where('transaction_types.type', TransactionType::TRANSFER);
                }
                )->leftJoin(
                    'transactions', function (JoinClause $join) {
                    $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '>', 0);
                }
                )->first([DB::raw('SUM(`transactions`.`amount`) as `sum_amount`')]);

                $amount = bcadd($amount, $set->sum_amount);
            } else {
                $amount = bcadd($amount, $creditCard->balance);
            }
        }

        return $amount;

    }

    /**
     * This method also returns the amount of the journal in "journalAmount"
     * for easy access.
     *
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getJournals(Bill $bill): Collection
    {
        $set = $bill->transactionjournals()
                    ->expanded()
                    ->orderBy('transaction_journals.date', 'DESC')
                    ->orderBy('transaction_journals.order', 'ASC')
                    ->orderBy('transaction_journals.id', 'DESC')
                    ->get(TransactionJournal::QUERYFIELDS);

        return $set;
    }

    /**
     * Get all journals that were recorded on this bill between these dates.
     *
     * @deprecated
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getJournalsInRange(Bill $bill, Carbon $start, Carbon $end): Collection
    {
        return $bill->transactionjournals()->before($end)->after($start)->get();
    }

    /**
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getPossiblyRelatedJournals(Bill $bill): Collection
    {
        $set = new Collection(
            DB::table('transactions')->where('amount', '>', 0)->where('amount', '>=', $bill->amount_min)->where('amount', '<=', $bill->amount_max)
              ->get(['transaction_journal_id'])
        );
        $ids = $set->pluck('transaction_journal_id')->toArray();

        $journals = new Collection;
        if (count($ids) > 0) {
            $journals = $this->user->transactionjournals()->transactionTypes([TransactionType::WITHDRAWAL])->whereIn('transaction_journals.id', $ids)->get(
                ['transaction_journals.*']
            );
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
     * @return array
     */
    public function getRanges(Bill $bill, Carbon $start, Carbon $end): array
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
     * @return \Carbon\Carbon
     */
    public function lastFoundMatch(Bill $bill): Carbon
    {
        $last = $bill->transactionjournals()->orderBy('date', 'DESC')->first();
        if ($last) {
            return $last->date;
        }

        return Carbon::now()->addDays(2); // in the future!
    }

    /**
     * @param Bill $bill
     *
     * @return \Carbon\Carbon
     */
    public function nextExpectedMatch(Bill $bill): Carbon
    {

        $finalDate       = Carbon::now();
        $finalDate->year = 1900;
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
     * @return bool
     */
    public function scan(Bill $bill, TransactionJournal $journal): bool
    {
        /*
         * Can only support withdrawals.
         */
        if (false === $journal->isWithdrawal()) {
            return false;
        }

        $matches     = explode(',', $bill->match);
        $description = strtolower($journal->description) . ' ' . strtolower(TransactionJournal::destinationAccount($journal)->name);

        // new: add source to word match:
        $description .= ' '.strtolower(TransactionJournal::sourceAccount($journal)->name);

        $wordMatch   = $this->doWordMatch($matches, $description);
        $amountMatch = $this->doAmountMatch(TransactionJournal::amountPositive($journal), $bill->amount_min, $bill->amount_max);


        /*
         * If both, update!
         */
        if ($wordMatch && $amountMatch) {
            $journal->bill()->associate($bill);
            $journal->save();

            return true;
        }
        if ($bill->id == $journal->bill_id) {
            // if no match, but bill used to match, remove it:
            $journal->bill_id = null;
            $journal->save();

            return true;
        }

        return false;

    }

    /**
     * @param array $data
     *
     * @return Bill
     */
    public function store(array $data): Bill
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
    public function update(Bill $bill, array $data): Bill
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

    /**
     * @param float $amount
     * @param float $min
     * @param float $max
     *
     * @return bool
     */
    protected function doAmountMatch($amount, $min, $max): bool
    {
        if ($amount >= $min && $amount <= $max) {
            return true;
        }

        return false;
    }

    /**
     * @param array $matches
     * @param       $description
     *
     * @return bool
     */
    protected function doWordMatch(array $matches, $description): bool
    {
        $wordMatch = false;
        $count     = 0;
        foreach ($matches as $word) {
            if (!(strpos($description, strtolower($word)) === false)) {
                $count++;
            }
        }
        if ($count >= count($matches)) {
            $wordMatch = true;
        }

        return $wordMatch;
    }
}

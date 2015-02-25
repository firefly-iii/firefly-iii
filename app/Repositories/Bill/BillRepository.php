<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 25/02/15
 * Time: 07:40
 */

namespace FireflyIII\Repositories\Bill;

use Carbon\Carbon;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;
use Navigation;
use Log;

/**
 * Class BillRepository
 *
 * @package FireflyIII\Repositories\Bill
 */
class BillRepository implements BillRepositoryInterface
{
    /**
     * @param Bill $bill
     *
     * @return Carbon
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
        $today = Navigation::addPeriod(new Carbon, $bill->repeat_freq, 0);

        $skip  = $bill->skip + 1;
        $start = Navigation::startOfPeriod(new Carbon, $bill->repeat_freq);
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
                    $finalDate = clone $start;
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
    public function scan(Bill $bill, TransactionJournal $journal)
    {
        /*
         * Match words.
         */
        $wordMatch   = false;
        $matches     = explode(',', $bill->match);
        $description = strtolower($journal->description);
        Log::debug('Now scanning ' . $description);

        /*
         * Attach expense account to description for more narrow matching.
         */
        if (count($journal->transactions) < 2) {
            $transactions = $journal->transactions()->get();
        } else {
            $transactions = $journal->transactions;
        }
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            /** @var Account $account */
            $account = $transaction->account()->first();
            /** @var AccountType $type */
            $type = $account->accountType()->first();
            if ($type->type == 'Expense account' || $type->type == 'Beneficiary account') {
                $description .= ' ' . strtolower($account->name);
            }
        }
        Log::debug('Final description: ' . $description);
        Log::debug('Matches searched: ' . join(':', $matches));

        $count = 0;
        foreach ($matches as $word) {
            if (!(strpos($description, strtolower($word)) === false)) {
                $count++;
            }
        }
        if ($count >= count($matches)) {
            $wordMatch = true;
            Log::debug('word match is true');
        } else {
            Log::debug('Count: ' . $count.', count(matches): ' . count($matches));
        }


        /*
         * Match amount.
         */

        $amountMatch = false;
        if (count($transactions) > 1) {

            $amount = max(floatval($transactions[0]->amount), floatval($transactions[1]->amount));
            $min    = floatval($bill->amount_min);
            $max    = floatval($bill->amount_max);
            if ($amount >= $min && $amount <= $max) {
                $amountMatch = true;
                Log::debug('Amount match is true!');
            }
        }


        /*
         * If both, update!
         */
        if ($wordMatch && $amountMatch) {
            Log::debug('TOTAL match is true!');
            $journal->bill()->associate($bill);
            $journal->save();
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
     * @return Bill|static
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

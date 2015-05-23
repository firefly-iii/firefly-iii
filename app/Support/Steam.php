<?php

namespace FireflyIII\Support;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use Illuminate\Support\Collection;

/**
 * Class Steam
 *
 * @package FireflyIII\Support
 */
class Steam
{
    /**
     *
     * @param \FireflyIII\Models\Account $account
     * @param \Carbon\Carbon             $date
     * @param bool                       $ignoreVirtualBalance
     *
     * @return float
     */
    public function balance(Account $account, Carbon $date, $ignoreVirtualBalance = false)
    {
        // find the first known transaction on this account:
        $firstDateObject = $account
            ->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->orderBy('transaction_journals.date', 'ASC')->first(['transaction_journals.date']);

        $firstDate = is_null($firstDateObject) ? clone $date : new Carbon($firstDateObject->date);
        $date      = $date < $firstDate ? $firstDate : $date;


        $set = $account->transactions()->leftJoin(
            'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
        )->where('transaction_journals.date', '<=', $date->format('Y-m-d'))->get(['transactions.*']);
        $balance = 0;
        foreach($set as $entry) {
            $balance += $entry->amount;
        }

        if (!$ignoreVirtualBalance) {
            $balance += floatval($account->virtual_balance);
        }

        return $balance;
    }

    /**
     * Only return the top X entries, group the rest by amount
     * and described as 'Others'. id = 0 as well
     *
     * @param array $array
     * @param int   $limit
     *
     * @return array
     */
    public function limitArray(array $array, $limit = 10)
    {
        $others = [
            'name'        => 'Others',
            'queryAmount' => 0
        ];
        $return = [];
        $count  = 0;
        foreach ($array as $id => $entry) {
            if ($count < ($limit - 1)) {
                $return[$id] = $entry;
            } else {
                $others['queryAmount'] += $entry['queryAmount'];
            }

            $count++;
        }
        $return[0] = $others;

        return $return;

    }

    /**
     * Turns a collection into an array. Needs the field 'id' for the key,
     * and saves only 'name' and 'amount' as a sub array.
     *
     * @param \Illuminate\Support\Collection $collection
     *
     * @return array
     */
    public function makeArray(Collection $collection)
    {
        $array = [];
        foreach ($collection as $entry) {
            $entry->spent = isset($entry->spent) ? floatval($entry->spent) : 0.0;
            $id           = intval($entry->id);
            if (isset($array[$id])) {
                $array[$id]['amount'] += floatval($entry->amount);
                $array[$id]['queryAmount'] += floatval($entry->queryAmount);
                $array[$id]['spent'] += floatval($entry->spent);
                $array[$id]['encrypted'] = intval($entry->encrypted);
            } else {
                $array[$id] = [
                    'amount'      => floatval($entry->amount),
                    'queryAmount' => floatval($entry->queryAmount),
                    'spent'       => floatval($entry->spent),
                    'encrypted'   => intval($entry->encrypted),
                    'name'        => $entry->name
                ];
            }
        }

        return $array;
    }

    /**
     * Merges two of the arrays as defined above. Can't handle more (yet)
     *
     * @param array $one
     * @param array $two
     *
     * @return array
     */
    public function mergeArrays(array $one, array $two)
    {
        foreach ($two as $id => $value) {
            // $otherId also exists in $one:
            if (isset($one[$id])) {
                $one[$id]['queryAmount'] += $value['queryAmount'];
                $one[$id]['spent'] += $value['spent'];
            } else {
                $one[$id] = $value;
            }
        }

        return $one;
    }

    /**
     * @param PiggyBank           $piggyBank
     * @param PiggyBankRepetition $repetition
     *
     * @return int
     */
    public function percentage(PiggyBank $piggyBank, PiggyBankRepetition $repetition)
    {
        $pct = $repetition->currentamount / $piggyBank->targetamount * 100;
        if ($pct > 100) {
            // @codeCoverageIgnoreStart
            return 100;
            // @codeCoverageIgnoreEnd
        } else {
            return floor($pct);
        }
    }

    /**
     * Sort an array where all 'amount' keys are positive floats.
     *
     * @param array $array
     *
     * @return array
     */
    public function sortArray(array $array)
    {
        uasort(
            $array, function ($left, $right) {
            if ($left['queryAmount'] == $right['queryAmount']) {
                return 0;
            }

            return ($left['queryAmount'] < $right['queryAmount']) ? 1 : -1;
        }
        );

        return $array;

    }

    /**
     * Sort an array where all 'amount' keys are negative floats.
     *
     * @param array $array
     *
     * @return array
     */
    public function sortNegativeArray(array $array)
    {
        uasort(
            $array, function ($left, $right) {
            if ($left['queryAmount'] == $right['queryAmount']) {
                return 0;
            }

            return ($left['queryAmount'] < $right['queryAmount']) ? -1 : 1;
        }
        );

        return $array;
    }

}

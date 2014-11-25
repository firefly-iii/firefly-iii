<?php

namespace FireflyIII\Shared\Toolkit;

use Carbon\Carbon;

/**
 *
 * Steam is a special class used for those small often occurring things you need your application to do.
 *
 * Class Steam
 *
 * @package FireflyIII\Shared\Toolkit
 */
class Steam
{

    /**
     * @param \Account $account
     * @param Carbon   $date
     *
     * @return float
     */
    public function balance(\Account $account, Carbon $date = null)
    {
        $latest = false;
        if (is_null($date)) {
            $latest = true;
            if (\Cache::has('account.' . $account->id . '.latestBalance')) {
                \Log::debug('Cache has latest balance for ' . $account->name . ', and it is: ' . \Cache::get('account.' . $account->id . '.latestBalance'));

                return \Cache::get('account.' . $account->id . '.latestBalance');
            }
        }
        $date    = is_null($date) ? Carbon::now() : $date;
        $balance = floatval(
            $account->transactions()->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            )->where('transaction_journals.date', '<=', $date->format('Y-m-d'))->sum('transactions.amount')
        );
        if ($latest === true) {
            \Cache::forever('account.' . $account->id . '.latestBalance', $balance);
        }

        return $balance;
    }

    /**
     * @param \Piggybank           $piggyBank
     * @param \PiggybankRepetition $repetition
     *
     * @return int
     */
    public function percentage(\Piggybank $piggyBank, \PiggybankRepetition $repetition)
    {
        $pct = $repetition->currentamount / $piggyBank->targetamount * 100;
        if ($pct > 100) {
            return 100;
        } else {
            return floor($pct);
        }
    }

} 
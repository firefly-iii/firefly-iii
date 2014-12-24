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
        \Log::debug('Now in Steam::balance() for account #' . $account->id.' ('.$account->name.')');
        if (is_null($date)) {
            $key = 'account.' . $account->id . '.latestBalance';
        } else {
            $key = 'account.' . $account->id . '.balanceOn' . $date->format('dmy');
        }
        if (\Cache::has($key)) {
            // TODO find a way to reliably remove cache entries for accounts.
            #return \Cache::get($key);
        }
        $date    = is_null($date) ? Carbon::now() : $date;
        \Log::debug('Now reached the moment we fire the query.');
        $balance = floatval(
            $account->transactions()->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            )->where('transaction_journals.date', '<=', $date->format('Y-m-d'))->sum('transactions.amount')
        );
        \Cache::put($key, $balance, 20160);

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

    public function removeEmptyBudgetLimits()
    {
        $user = \Auth::user();
        if ($user) {
            \BudgetLimit::where('amount', 0)->delete();
        }
    }

} 
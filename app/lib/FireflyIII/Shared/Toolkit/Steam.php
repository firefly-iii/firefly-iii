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
     *
     * @param \Account $account
     * @param Carbon   $date
     *
     * @return float
     */
    public function balance(\Account $account, Carbon $date = null)
    {
        $date    = is_null($date) ? Carbon::now() : $date;
        $balance = floatval(
            $account->transactions()->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            )->where('transaction_journals.date', '<=', $date->format('Y-m-d'))->sum('transactions.amount')
        );

        return $balance;
    }

    /**
     * @param $boolean
     *
     * @return string
     */
    public function boolString($boolean)
    {
        if ($boolean === true) {
            return 'BOOLEAN TRUE';
        }
        if ($boolean === false) {
            return 'BOOLEAN FALSE';
        }

        return 'NO BOOLEAN: ' . $boolean;
    }

    /**
     * @param \PiggyBank           $piggyBank
     * @param \PiggyBankRepetition $repetition
     *
     * @return int
     */
    public function percentage(\PiggyBank $piggyBank, \PiggyBankRepetition $repetition)
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

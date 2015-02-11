<?php

namespace FireflyIII\Support;

use Carbon\Carbon;
use FireflyIII\Models\Account;

/**
 * Class Steam
 *
 * @package FireflyIII\Support
 */
class Steam
{
    /**
     *
     * @param Account $account
     * @param Carbon  $date
     *
     * @return float
     */
    public function balance(Account $account, Carbon $date = null)
    {
        $date    = is_null($date) ? Carbon::now() : $date;
        $balance = floatval(
            $account->transactions()->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            )->where('transaction_journals.date', '<=', $date->format('Y-m-d'))->sum('transactions.amount')
        );

        return $balance;
    }

}
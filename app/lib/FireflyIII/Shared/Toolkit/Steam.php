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
        $date = is_null($date) ? Carbon::now() : $date;

        return floatval(
            $account->transactions()->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            )->where('transaction_journals.date', '<=', $date->format('Y-m-d'))->sum('transactions.amount')
        );
    }

} 
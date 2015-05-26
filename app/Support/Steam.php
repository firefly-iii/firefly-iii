<?php

namespace FireflyIII\Support;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;

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

        bcscale(2);
        $set     = $account->transactions()->leftJoin(
            'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
        )->where('transaction_journals.date', '<=', $date->format('Y-m-d'))->get(['transactions.*']);
        $balance = '0';
        foreach ($set as $entry) {
            $balance = bcadd($balance, $entry->amount);
        }

        if (!$ignoreVirtualBalance) {
            $balance = bcadd($balance, $account->virtual_balance);
        }

        return round($balance, 2);
    }

}

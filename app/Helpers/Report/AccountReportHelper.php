<?php
/**
 * AccountReportHelper.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use DB;
use FireflyIII\Helpers\Collection\Account as AccountCollection;
use FireflyIII\Models\Account;
use Illuminate\Support\Collection;


/**
 * Class AccountReportHelper
 *
 * @package FireflyIII\Helpers\Report
 */
class AccountReportHelper implements AccountReportHelperInterface
{
    /**
     * This method generates a full report for the given period on all
     * given accounts
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return AccountCollection
     */
    public function getAccountReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        $startAmount = '0';
        $endAmount   = '0';
        $diff        = '0';
        $ids         = $accounts->pluck('id')->toArray();

        $yesterday = clone $start;
        $yesterday->subDay();

        bcscale(2);

        // get balances for start.
        $startSet = Account::leftJoin('transactions', 'transactions.account_id', '=', 'accounts.id')
                           ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                           ->whereIn('accounts.id', $ids)
                           ->whereNull('transaction_journals.deleted_at')
                           ->whereNull('transactions.deleted_at')
                           ->where('transaction_journals.date', '<=', $yesterday->format('Y-m-d'))
                           ->groupBy('accounts.id')
                           ->get(['accounts.id', DB::Raw('SUM(`transactions`.`amount`) as `balance`')]);

        // and end:
        $endSet = Account::leftJoin('transactions', 'transactions.account_id', '=', 'accounts.id')
                         ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                         ->whereIn('accounts.id', $ids)
                         ->whereNull('transaction_journals.deleted_at')
                         ->whereNull('transactions.deleted_at')
                         ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                         ->groupBy('accounts.id')
                         ->get(['accounts.id', DB::Raw('SUM(`transactions`.`amount`) as `balance`')]);


        $accounts->each(
            function (Account $account) use ($startSet, $endSet) {
                /**
                 * The balance for today always incorporates transactions
                 * made on today. So to get todays "start" balance, we sub one
                 * day.
                 */
                //
                $currentStart = $startSet->filter(
                    function (Account $entry) use ($account) {
                        return $account->id == $entry->id;
                    }
                );
                if ($currentStart->first()) {
                    $account->startBalance = $currentStart->first()->balance;
                }

                $currentEnd = $endSet->filter(
                    function (Account $entry) use ($account) {
                        return $account->id == $entry->id;
                    }
                );
                if ($currentEnd->first()) {
                    $account->endBalance = $currentEnd->first()->balance;
                }
            }
        );


        // summarize:
        foreach ($accounts as $account) {
            $startAmount = bcadd($startAmount, $account->startBalance);
            $endAmount   = bcadd($endAmount, $account->endBalance);
            $diff        = bcadd($diff, bcsub($account->endBalance, $account->startBalance));
        }

        $object = new AccountCollection;
        $object->setStart($startAmount);
        $object->setEnd($endAmount);
        $object->setDifference($diff);
        $object->setAccounts($accounts);

        return $object;
    }
}
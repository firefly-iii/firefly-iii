<?php
/**
 * AccountReportHelper.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

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
     * @param Account    $account
     * @param Collection $startSet
     * @param Collection $endSet
     * @param Collection $backupSet
     *
     * @return Account
     */
    public static function reportFilter(Account $account, Collection $startSet, Collection $endSet, Collection $backupSet)
    {
        // The balance for today always incorporates transactions made on today. So to get todays "start" balance, we sub one day.
        $account->startBalance = '0';
        $account->endBalance   = '0';
        $currentStart          = $startSet->filter(
            function (Account $entry) use ($account) {
                return $account->id == $entry->id;
            }
        );

        $currentBackup = $backupSet->filter( // grab entry from current backup as well:
            function (Account $entry) use ($account) {
                return $account->id == $entry->id;
            }
        );

        // first try to set from backup
        if (!is_null($currentBackup->first())) {
            $account->startBalance = $currentBackup->first()->balance;
        }

        // overrule with data from start
        if (!is_null($currentStart->first())) {
            $account->startBalance = $currentStart->first()->balance;
        }

        $currentEnd = $endSet->filter(
            function (Account $entry) use ($account) {
                return $account->id == $entry->id;
            }
        );

        if (!is_null($currentEnd->first())) {
            $account->endBalance = $currentEnd->first()->balance;
        }

        return $account;
    }

    /**
     * This method generates a full report for the given period on all
     * given accounts.
     *
     * a special consideration for accounts that did exist on this exact day.
     * we also grab the balance from today just in case, to see if that changes things.
     * it's a fall back for users who (rightly so) start keeping score at the first of
     * the month and find the first report lacking / broken.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return AccountCollection
     */
    public function getAccountReport(Carbon $start, Carbon $end, Collection $accounts): AccountCollection
    {
        $startAmount = '0';
        $endAmount   = '0';
        $diff        = '0';
        $ids         = $accounts->pluck('id')->toArray();
        $yesterday   = clone $start;
        $yesterday->subDay();
        $startSet  = $this->getSet($ids, $yesterday); // get balances for start.
        $backupSet = $this->getSet($ids, $start);
        $endSet    = $this->getSet($ids, $end);

        $accounts->each(
            function (Account $account) use ($startSet, $endSet, $backupSet) {
                return self::reportFilter($account, $startSet, $endSet, $backupSet);
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

    /**
     * @param array  $ids
     * @param Carbon $date
     *
     * @return Collection
     */
    private function getSet(array $ids, Carbon $date): Collection
    {
        return Account::leftJoin('transactions', 'transactions.account_id', '=', 'accounts.id')
                      ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                      ->whereIn('accounts.id', $ids)
                      ->whereNull('transaction_journals.deleted_at')
                      ->whereNull('transactions.deleted_at')
                      ->where('transaction_journals.date', '<=', $date->format('Y-m-d'))
                      ->groupBy('accounts.id')
                      ->get(['accounts.id', DB::raw('SUM(`transactions`.`amount`) as `balance`')]);
    }
}

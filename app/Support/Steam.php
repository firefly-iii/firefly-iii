<?php

namespace FireflyIII\Support;

use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;

/**
 * Class Steam
 *
 * @package FireflyIII\Support
 */
class Steam
{

    /**
     * @param array $accounts
     *
     * @return array
     */
    public function getLastActivities(array $accounts)
    {
        $list = [];

        $set = Auth::user()->transactions()
                   ->whereIn('account_id', $accounts)
                   ->groupBy('account_id')
                   ->get(['transactions.account_id', DB::Raw('MAX(`transaction_journals`.`date`) as `max_date`')]);

        foreach ($set as $entry) {
            $list[intval($entry->account_id)] = new Carbon($entry->max_date);
        }

        return $list;
    }

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

        // abuse chart properties:
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty('balance');
        $cache->addProperty($date);
        $cache->addProperty($ignoreVirtualBalance);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        bcscale(2);

        $balance = $account->transactions()->leftJoin(
            'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
        )->where('transaction_journals.date', '<=', $date->format('Y-m-d'))->sum('transactions.amount');

        if (!$ignoreVirtualBalance) {
            $balance = bcadd($balance, $account->virtual_balance);
        }
        $cache->store(round($balance, 2));

        return round($balance, 2);
    }

    /**
     *
     * @param array          $ids
     * @param \Carbon\Carbon $date
     *
     * @return float
     */
    public function balancesById(array $ids, Carbon $date)
    {

        // abuse chart properties:
        $cache = new CacheProperties;
        $cache->addProperty($ids);
        $cache->addProperty('balances');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        bcscale(2);

        $balances = Transaction::
        leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                               ->where('transaction_journals.date', '<=', $date->format('Y-m-d'))
                               ->groupBy('transactions.account_id')
                               ->whereIn('transactions.account_id', $ids)
                               ->get(['transactions.account_id', DB::Raw('sum(`transactions`.`amount`) as aggregate')]);

        $result = [];
        foreach ($balances as $entry) {
            $accountId          = intval($entry->account_id);
            $balance            = round($entry->aggregate, 2);
            $result[$accountId] = $balance;
        }


        $cache->store($result);

        return $result;
    }

    // parse PHP size:
    /**
     * @param $string
     *
     * @return int
     */
    public function phpBytes($string)
    {
        $string = strtolower($string);

        if (!(strpos($string, 'k') === false)) {
            // has a K in it, remove the K and multiply by 1024.
            $bytes = bcmul(rtrim($string, 'k'), 1024);

            return intval($bytes);
        }

        if (!(strpos($string, 'm') === false)) {
            // has a M in it, remove the M and multiply by 1048576.
            $bytes = bcmul(rtrim($string, 'm'), 1048576);

            return intval($bytes);
        }

        return $string;


    }

}

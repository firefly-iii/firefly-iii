<?php

namespace FireflyIII\Support;

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

}

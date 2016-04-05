<?php
declare(strict_types = 1);

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
     *
     * @param \FireflyIII\Models\Account $account
     * @param \Carbon\Carbon             $date
     * @param bool                       $ignoreVirtualBalance
     *
     * @return string
     */
    public function balance(Account $account, Carbon $date, $ignoreVirtualBalance = false): string
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

        $balance = strval(
            $account->transactions()->leftJoin(
                'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
            )->where('transaction_journals.date', '<=', $date->format('Y-m-d'))->sum('transactions.amount')
        );

        if (!$ignoreVirtualBalance) {
            $balance = bcadd($balance, $account->virtual_balance);
        }
        $cache->store($balance);

        return $balance;
    }

    /**
     * Gets the balance for the given account during the whole range, using this format:
     *
     * [yyyy-mm-dd] => 123,2
     *
     * @param \FireflyIII\Models\Account $account
     * @param \Carbon\Carbon             $start
     * @param \Carbon\Carbon             $end
     *
     * @return array
     */
    public function balanceInRange(Account $account, Carbon $start, Carbon $end): array
    {
        // abuse chart properties:
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty('balance-in-range');
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $balances = [];
        $start->subDay();
        $end->addDay();
        $startBalance                      = $this->balance($account, $start);
        $balances[$start->format('Y-m-d')] = $startBalance;
        $start->addDay();

        // query!
        $set            = $account->transactions()
                                  ->leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                  ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                                  ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                                  ->groupBy('transaction_journals.date')
                                  ->get(['transaction_journals.date', DB::raw('SUM(`transactions`.`amount`) as `modified`')]);
        $currentBalance = $startBalance;
        foreach ($set as $entry) {
            $currentBalance         = bcadd($currentBalance, $entry->modified);
            $balances[$entry->date] = $currentBalance;
        }

        $cache->store($balances);

        return $balances;


    }

    /**
     * This method always ignores the virtual balance.
     *
     * @param array          $ids
     * @param \Carbon\Carbon $date
     *
     * @return array
     */
    public function balancesById(array $ids, Carbon $date): array
    {

        // abuse chart properties:
        $cache = new CacheProperties;
        $cache->addProperty($ids);
        $cache->addProperty('balances');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $balances = Transaction::
        leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                               ->where('transaction_journals.date', '<=', $date->format('Y-m-d'))
                               ->groupBy('transactions.account_id')
                               ->whereIn('transactions.account_id', $ids)
                               ->get(['transactions.account_id', DB::raw('sum(`transactions`.`amount`) as aggregate')]);

        $result = [];
        foreach ($balances as $entry) {
            $accountId          = intval($entry->account_id);
            $balance            = $entry->aggregate;
            $result[$accountId] = $balance;
        }


        $cache->store($result);

        return $result;
    }

    /**
     * @param array $accounts
     *
     * @return array
     */
    public function getLastActivities(array $accounts): array
    {
        $list = [];

        $set = Auth::user()->transactions()
                   ->whereIn('account_id', $accounts)
                   ->groupBy('account_id')
                   ->get(['transactions.account_id', DB::raw('MAX(`transaction_journals`.`date`) as `max_date`')]);

        foreach ($set as $entry) {
            $list[intval($entry->account_id)] = new Carbon($entry->max_date);
        }

        return $list;
    }

    // parse PHP size:

    /**
     * @param $string
     *
     * @return int
     */
    public function phpBytes($string): int
    {
        $string = strtolower($string);

        if (!(strpos($string, 'k') === false)) {
            // has a K in it, remove the K and multiply by 1024.
            $bytes = bcmul(rtrim($string, 'k'), '1024');

            return intval($bytes);
        }

        if (!(strpos($string, 'm') === false)) {
            // has a M in it, remove the M and multiply by 1048576.
            $bytes = bcmul(rtrim($string, 'm'), '1048576');

            return intval($bytes);
        }

        return $string;


    }

}

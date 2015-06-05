<?php

namespace FireflyIII\Repositories\Shared;

use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use stdClass;

/**
 * Class ComponentRepository
 *
 * @package FireflyIII\Repositories\Shared
 */
class ComponentRepository
{


    /**
     * @param stdClass   $object
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @param bool     $shared
     *
     * @return string
     */
    protected function spentInPeriod(stdClass $object, Carbon $start, Carbon $end, $shared = false)
    {
        if ($shared === true) {
            // shared is true.
            // always ignore transfers between accounts!
            $sum
                = $object->transactionjournals()
                         ->transactionTypes(['Withdrawal'])
                         ->before($end)->after($start)->get(['transaction_journals.*'])->sum('amount');

        } else {
            // do something else, SEE budgets.
            // get all journals in this month where the asset account is NOT shared.
            $sum = $object->transactionjournals()
                          ->before($end)
                          ->after($start)
                          ->transactionTypes(['Withdrawal'])
                          ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                          ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                          ->leftJoin(
                              'account_meta', function (JoinClause $join) {
                              $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
                          }
                          )
                          ->where('account_meta.data', '!=', '"sharedAsset"')
                          ->get(['transaction_journals.*'])->sum('amount');
        }

        return $sum;
    }
}

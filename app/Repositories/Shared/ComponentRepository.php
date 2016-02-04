<?php

namespace FireflyIII\Repositories\Shared;

use Carbon\Carbon;
use DB;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;

/**
 * Class ComponentRepository
 *
 * @package FireflyIII\Repositories\Shared
 */
class ComponentRepository
{

    /**
     * @param            $object
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return string
     */
    protected function commonBalanceInPeriod($object, Carbon $start, Carbon $end, Collection $accounts)
    {
        $ids = $accounts->pluck('id')->toArray();


        $entry  = $object->transactionjournals()
                         ->transactionTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE])
                         ->before($end)
                         ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                         ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                         ->whereIn('accounts.id', $ids)
                         ->after($start)
                         ->first([DB::raw('SUM(`transactions`.`amount`) as `journalAmount`')]);
        $amount = $entry->journalAmount;

        return $amount;
    }
}

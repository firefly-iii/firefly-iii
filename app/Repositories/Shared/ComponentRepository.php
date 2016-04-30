<?php
declare(strict_types = 1);

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
        // all balances based on transaction journals:
        // TODO somehow exclude those with transactions below?
        // TODO needs a completely new query.
        $ids    = $accounts->pluck('id')->toArray();
        $entry  = $object->transactionjournals()
                         ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                         ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                         ->whereIn('accounts.id', $ids)
                         ->transactionTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE])
                         ->before($end)
                         ->after($start)
                         ->first([DB::raw('SUM(`transactions`.`amount`) as `journalAmount`')]);
        $amount = $entry->journalAmount ?? '0';

        // all balances based on individual transactions (at the moment, it's an "or or"):
        $entry = $object
            ->transactions()
            // left join journals to get some meta-information.
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            // also left join transaction types so we can do the same type of filtering.
            ->leftJoin('transaction_types', 'transaction_journals.transaction_type_id', '=', 'transaction_types.id')
            // need to do these manually.
            ->whereIn('transaction_types.type', [TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE])
            ->where('transaction_journals.date', '>=', $start->format('Y-m-d 00:00:00'))
            ->where('transaction_journals.date', '<=', $end->format('Y-m-d 00:00:00'))
            ->whereIn('transactions.account_id', $ids)
            ->first([DB::raw('SUM(`transactions`.`amount`) as `journalAmount`')]);

        // sum of amount:
        $extraAmount = $entry->journalAmount ?? '0';
        $result      = bcadd($amount, $extraAmount);

        return $result;
    }
}

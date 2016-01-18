<?php

namespace FireflyIII\Helpers\Report;

use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Models\TransactionType;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Class ReportQuery
 *
 * @package FireflyIII\Helpers\Report
 */
class ReportQuery implements ReportQueryInterface
{

    /**
     * Returns an array of the amount of money spent in the given accounts (on withdrawals, opening balances and transfers)
     * grouped by month like so: "2015-01" => '123.45'
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentPerMonth(Collection $accounts, Carbon $start, Carbon $end)
    {
        $ids   = $accounts->pluck('id')->toArray();
        $query = Auth::user()->transactionjournals()
                     ->leftJoin(
                         'transactions AS t_from', function (JoinClause $join) {
                         $join->on('transaction_journals.id', '=', 't_from.transaction_journal_id')->where('t_from.amount', '<', 0);
                     }
                     )
                     ->leftJoin(
                         'transactions AS t_to', function (JoinClause $join) {
                         $join->on('transaction_journals.id', '=', 't_to.transaction_journal_id')->where('t_to.amount', '>', 0);
                     }
                     )
                     ->whereIn('t_from.account_id', $ids)
                     ->whereNotIn('t_to.account_id', $ids)
                     ->after($start)
                     ->before($end)
                     ->transactionTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE])
                     ->groupBy('dateFormatted')
                     ->get(
                         [
                             DB::Raw('DATE_FORMAT(`transaction_journals`.`date`,"%Y-%m") AS `dateFormatted`'),
                             DB::Raw('SUM(`t_from`.`amount`) AS `sum`'),
                         ]
                     );
        $array = [];
        foreach ($query as $result) {
            $array[$result->dateFormatted] = $result->sum;
        }

        return $array;

    }

    /**
     * Returns an array of the amount of money spent in the given accounts (on withdrawals, opening balances and transfers)
     * grouped by month like so: "2015-01" => '123.45'
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function earnedPerMonth(Collection $accounts, Carbon $start, Carbon $end)
    {
        $ids   = $accounts->pluck('id')->toArray();
        $query = Auth::user()->transactionjournals()
                     ->leftJoin(
                         'transactions AS t_from', function (JoinClause $join) {
                         $join->on('transaction_journals.id', '=', 't_from.transaction_journal_id')->where('t_from.amount', '<', 0);
                     }
                     )
                     ->leftJoin(
                         'transactions AS t_to', function (JoinClause $join) {
                         $join->on('transaction_journals.id', '=', 't_to.transaction_journal_id')->where('t_to.amount', '>', 0);
                     }
                     )
                     ->whereIn('t_to.account_id', $ids)
                     ->whereNotIn('t_from.account_id', $ids)
                     ->after($start)
                     ->before($end)
                     ->transactionTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE])
                     ->groupBy('dateFormatted')
                     ->get(
                         [
                             DB::Raw('DATE_FORMAT(`transaction_journals`.`date`,"%Y-%m") AS `dateFormatted`'),
                             DB::Raw('SUM(`t_to`.`amount`) AS `sum`'),
                         ]
                     );
        $array = [];
        foreach ($query as $result) {
            $array[$result->dateFormatted] = $result->sum;
        }

        return $array;
    }

    /**
     * This method returns all the "in" transaction journals for the given account and given period. The amount
     * is stored in "journalAmount".
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function income(Collection $accounts, Carbon $start, Carbon $end)
    {
        $ids = $accounts->pluck('id')->toArray();
        $set = Auth::user()->transactionjournals()
                   ->leftJoin(
                       'transactions as t_from', function (JoinClause $join) {
                       $join->on('t_from.transaction_journal_id', '=', 'transaction_journals.id')->where('t_from.amount', '<', 0);
                   }
                   )
                   ->leftJoin(
                       'transactions as t_to', function (JoinClause $join) {
                       $join->on('t_to.transaction_journal_id', '=', 'transaction_journals.id')->where('t_to.amount', '>', 0);
                   }
                   )
                   ->leftJoin('accounts', 't_from.account_id', '=', 'accounts.id')
                   ->transactionTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE])
                   ->before($end)
                   ->after($start)
                   ->whereIn('t_to.account_id', $ids)
                   ->whereNotIn('t_from.account_id', $ids)
                   ->get(['transaction_journals.*', 't_to.amount as journalAmount', 'accounts.id as account_id', 'accounts.name as account_name']);

        return $set;
    }

    /**
     * This method returns all the "out" transaction journals for the given account and given period. The amount
     * is stored in "journalAmount".
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function expense(Collection $accounts, Carbon $start, Carbon $end)
    {
        $ids = $accounts->pluck('id')->toArray();
        $set = Auth::user()->transactionjournals()
                   ->leftJoin(
                       'transactions as t_from', function (JoinClause $join) {
                       $join->on('t_from.transaction_journal_id', '=', 'transaction_journals.id')->where('t_from.amount', '<', 0);
                   }
                   )
                   ->leftJoin(
                       'transactions as t_to', function (JoinClause $join) {
                       $join->on('t_to.transaction_journal_id', '=', 'transaction_journals.id')->where('t_to.amount', '>', 0);
                   }
                   )
                   ->leftJoin('accounts', 't_to.account_id', '=', 'accounts.id')
                   ->transactionTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE])
                   ->before($end)
                   ->after($start)
                   ->whereIn('t_from.account_id', $ids)
                   ->whereNotIn('t_to.account_id', $ids)
                   ->get(['transaction_journals.*', 't_from.amount as journalAmount', 'accounts.id as account_id', 'accounts.name as account_name']);

        return $set;
    }
}

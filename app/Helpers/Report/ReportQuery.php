<?php
declare(strict_types = 1);

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
    public function earnedPerMonth(Collection $accounts, Carbon $start, Carbon $end): array
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
                             DB::raw('DATE_FORMAT(`transaction_journals`.`date`,"%Y-%m") AS `dateFormatted`'),
                             DB::raw('SUM(`t_to`.`amount`) AS `sum`'),
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
    public function spentPerMonth(Collection $accounts, Carbon $start, Carbon $end): array
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
                             DB::raw('DATE_FORMAT(`transaction_journals`.`date`,"%Y-%m") AS `dateFormatted`'),
                             DB::raw('SUM(`t_from`.`amount`) AS `sum`'),
                         ]
                     );
        $array = [];
        foreach ($query as $result) {
            $array[$result->dateFormatted] = $result->sum;
        }

        return $array;

    }
}

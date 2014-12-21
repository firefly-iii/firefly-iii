<?php

namespace FireflyIII\Chart;


use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Class Chart
 *
 * @package FireflyIII\Chart
 */
class Chart implements ChartInterface
{

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getCategorySummary(Carbon $start, Carbon $end)
    {
        return \TransactionJournal::leftJoin(
            'transactions',
            function (JoinClause $join) {
                $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('amount', '>', 0);
            }
        )
                                  ->leftJoin(
                                      'category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id'
                                  )
                                  ->leftJoin('categories', 'categories.id', '=', 'category_transaction_journal.category_id')
                                  ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                  ->before($end)
                                  ->after($start)
                                  ->where('transaction_types.type', 'Withdrawal')
                                  ->groupBy('categories.id')
                                  ->orderBy('sum', 'DESC')
                                  ->get(['categories.id', 'categories.name', \DB::Raw('SUM(`transactions`.`amount`) AS `sum`')]);
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getRecurringSummary(Carbon $start, Carbon $end)
    {
        return \RecurringTransaction::
        leftJoin(
            'transaction_journals', function (JoinClause $join) use ($start, $end) {
            $join->on('recurring_transactions.id', '=', 'transaction_journals.recurring_transaction_id')
                 ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                 ->where('transaction_journals.date', '<=', $end->format('Y-m-d'));
        }
        )
                                    ->leftJoin(
                                        'transactions', function (JoinClause $join) {
                                        $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('transactions.amount', '>', 0);
                                    }
                                    )
                                    ->where('active', 1)
                                    ->groupBy('recurring_transactions.id')
                                    ->get(
                                        ['recurring_transactions.id', 'recurring_transactions.name', 'transaction_journals.description',
                                         'transaction_journals.id as journalId',
                                         \DB::Raw('SUM(`recurring_transactions`.`amount_min` + `recurring_transactions`.`amount_max`) / 2 as `averageAmount`'),
                                         'transactions.amount AS actualAmount']
                                    );
    }
}
<?php

namespace FireflyIII\Helpers\Report;

use Auth;
use Carbon\Carbon;
use Crypt;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Database\Eloquent\Builder;
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
     * Covers tags
     *
     * @param Account $account
     * @param Budget  $budget
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return float
     */
    public function spentInBudget(Account $account, Budget $budget, Carbon $start, Carbon $end)
    {

        return Auth::user()->transactionjournals()
                   ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                   ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                   ->transactionTypes([TransactionType::WITHDRAWAL])
                   ->where('transactions.account_id', $account->id)
                   ->before($end)
                   ->after($start)
                   ->where('budget_transaction_journal.budget_id', $budget->id)
                   ->get(['transaction_journals.*'])->sum('amount');
    }

    /**
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return string
     */
    public function spentNoBudget(Account $account, Carbon $start, Carbon $end)
    {
        return
            Auth::user()->transactionjournals()
                ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                ->transactionTypes([TransactionType::WITHDRAWAL])
                ->where('transactions.account_id', $account->id)
                ->before($end)
                ->after($start)
                ->whereNull('budget_transaction_journal.budget_id')->get(['transaction_journals.*'])->sum('amount');
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Builder
     */
    protected function queryJournalsWithTransactions(Carbon $start, Carbon $end)
    {
        $query = TransactionJournal::
        leftJoin(
            'transactions as t_from', function (JoinClause $join) {
            $join->on('t_from.transaction_journal_id', '=', 'transaction_journals.id')->where('t_from.amount', '<', 0);
        }
        )
                                   ->leftJoin('accounts as ac_from', 't_from.account_id', '=', 'ac_from.id')
                                   ->leftJoin(
                                       'account_meta as acm_from', function (JoinClause $join) {
                                       $join->on('ac_from.id', '=', 'acm_from.account_id')->where('acm_from.name', '=', 'accountRole');
                                   }
                                   )
                                   ->leftJoin(
                                       'transactions as t_to', function (JoinClause $join) {
                                       $join->on('t_to.transaction_journal_id', '=', 'transaction_journals.id')->where('t_to.amount', '>', 0);
                                   }
                                   )
                                   ->leftJoin('accounts as ac_to', 't_to.account_id', '=', 'ac_to.id')
                                   ->leftJoin(
                                       'account_meta as acm_to', function (JoinClause $join) {
                                       $join->on('ac_to.id', '=', 'acm_to.account_id')->where('acm_to.name', '=', 'accountRole');
                                   }
                                   )
                                   ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id');
        $query->before($end)->after($start)->where('transaction_journals.user_id', Auth::user()->id);

        return $query;
    }


    /**
     * This method works the same way as ReportQueryInterface::incomeInPeriod does, but instead of returning results
     * will simply list the transaction journals only. This should allow any follow up counting to be accurate with
     * regards to tags. It will only get the incomes to the specified accounts.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function incomeInPeriod(Carbon $start, Carbon $end, Collection $accounts)
    {
        $query = $this->queryJournalsWithTransactions($start, $end);

        $ids = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $ids[] = $account->id;
        }

        // OR is a deposit
        // OR any transfer TO the accounts in $accounts, not FROM any of the accounts in $accounts.
        $query->where(
            function (Builder $query) use ($ids) {
                $query->where(
                    function (Builder $q) {
                        $q->where('transaction_types.type', TransactionType::DEPOSIT);
                    }
                );
                $query->orWhere(
                    function (Builder $q) use ($ids) {
                        $q->where('transaction_types.type', TransactionType::TRANSFER);
                        $q->whereNotIn('ac_from.id', $ids);
                        $q->whereIn('ac_to.id', $ids);
                    }
                );
            }
        );

        // only include selected accounts.
        $query->whereIn('ac_to.id', $ids);
        $query->orderBy('transaction_journals.date');

        // get everything
        $data = $query->get(
            ['transaction_journals.*',
             'transaction_types.type', 'ac_from.name as name',
             't_from.amount as from_amount',
             't_to.amount as to_amount',
             'ac_from.id as account_id', 'ac_from.encrypted as account_encrypted']
        );

        $data->each(
            function (TransactionJournal $journal) {
                if (intval($journal->account_encrypted) == 1) {
                    $journal->name = Crypt::decrypt($journal->name);
                }
            }
        );

        return $data;
    }

    /**
     * See ReportQueryInterface::incomeInPeriod
     *
     * This method returns all "expense" journals in a certain period, which are both transfers to a shared account
     * and "ordinary" withdrawals. The query used is almost equal to ReportQueryInterface::journalsByRevenueAccount but it does
     * not group and returns different fields.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Collection
     *
     */
    public function expenseInPeriod(Carbon $start, Carbon $end, Collection $accounts)
    {
        $ids = [];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $ids[] = $account->id;
        }

        $query = $this->queryJournalsWithTransactions($start, $end);

        // withdrawals from any account are an expense.
        // transfers away, from an account in the list, to an account not in the list, are an expense.

        $query->where(
            function (Builder $query) use ($ids) {
                $query->where(
                    function (Builder $q) {
                        $q->where('transaction_types.type', TransactionType::WITHDRAWAL);
                    }
                );
                $query->orWhere(
                    function (Builder $q) use ($ids) {
                        $q->where('transaction_types.type', TransactionType::TRANSFER);
                        $q->whereIn('ac_from.id', $ids);
                        $q->whereNotIn('ac_to.id', $ids);
                    }
                );
            }
        );

        // expense goes from the selected accounts:
        $query->whereIn('ac_from.id', $ids);

        $query->orderBy('transaction_journals.date');
        $data = $query->get( // get everything
            ['transaction_journals.*', 'transaction_types.type',
             't_from.amount as from_amount',
             't_to.amount as to_amount',
             'ac_to.name as name', 'ac_to.id as account_id', 'ac_to.encrypted as account_encrypted']
        );

        $data->each(
            function (TransactionJournal $journal) {
                if (intval($journal->account_encrypted) == 1) {
                    $journal->name = Crypt::decrypt($journal->name);
                }
            }
        );

        return $data;
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
                             DB::Raw('SUM(`t_from`.`amount`) AS `sum`')
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
                             DB::Raw('SUM(`t_from`.`amount`) AS `sum`')
                         ]
                     );
        $array = [];
        foreach ($query as $result) {
            $array[$result->dateFormatted] = $result->sum;
        }

        return $array;
    }

}

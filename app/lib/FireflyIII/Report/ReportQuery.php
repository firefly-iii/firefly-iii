<?php

namespace FireflyIII\Report;

use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Class ReportQuery
 *
 * @package FireflyIII\Report
 */
class ReportQuery implements ReportQueryInterface
{
    /**
     * This query retrieves a list of accounts that are active and not shared.
     *
     * @return Collection
     */
    public function accountList()
    {
        return \Auth::user()->accounts()
                    ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                    ->leftJoin(
                        'account_meta', function (JoinClause $join) {
                        $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', "accountRole");
                    }
                    )
                    ->whereIn('account_types.type', ['Default account', 'Cash account', 'Asset account'])
                    ->where('active', 1)
                    ->where(
                        function ($query) {
                            $query->where('account_meta.data', '!=', '"sharedExpense"');
                            $query->orWhereNull('account_meta.data');
                        }
                    )
                    ->get(['accounts.*']);
    }

    /**
     * Gets a list of all budgets and if present, the amount of the current BudgetLimit
     * as well
     *
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getAllBudgets(Carbon $date)
    {
        return \Auth::user()->budgets()
                    ->leftJoin(
                        'budget_limits', function (JoinClause $join) use ($date) {
                        $join->on('budget_limits.budget_id', '=', 'budgets.id')->where('budget_limits.startdate', '=', $date->format('Y-m-d'));
                    }
                    )
                    ->get(['budgets.*', 'budget_limits.amount as amount']);
    }

    /**
     * Gets a list of expenses grouped by the budget they were filed under.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function journalsByBudget(Carbon $start, Carbon $end)
    {
        return \Auth::user()->transactionjournals()
                    ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                    ->leftJoin('budgets', 'budget_transaction_journal.budget_id', '=', 'budgets.id')
                    ->leftJoin(
                        'transactions', function (JoinClause $join) {
                        $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('transactions.amount', '<', 0);
                    }
                    )
                    ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                    ->leftJoin(
                        'account_meta', function (JoinClause $join) {
                        $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
                    }
                    )
                    ->leftJoin('transaction_types', 'transaction_journals.transaction_type_id', '=', 'transaction_types.id')
                    ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                    ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                    ->where('account_meta.data', '!=', '"sharedExpense"')
                    ->where('transaction_types.type', 'Withdrawal')
                    ->groupBy('budgets.id')
                    ->orderBy('name', 'ASC')
                    ->get(['budgets.id', 'budgets.name', \DB::Raw('SUM(`transactions`.`amount`) AS `spent`')]);
    }

    /**
     * Gets a list of categories and the expenses therein, grouped by the relevant category.
     * This result excludes transfers to shared accounts which are expenses, technically.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function journalsByCategory(Carbon $start, Carbon $end)
    {
        return \Auth::user()->transactionjournals()
                    ->leftJoin(
                        'category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id'
                    )
                    ->leftJoin('categories', 'category_transaction_journal.category_id', '=', 'categories.id')
                    ->leftJoin(
                        'transactions', function (JoinClause $join) {
                        $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('transactions.amount', '<', 0);
                    }
                    )
                    ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                    ->leftJoin(
                        'account_meta', function (JoinClause $join) {
                        $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
                    }
                    )
                    ->leftJoin('transaction_types', 'transaction_journals.transaction_type_id', '=', 'transaction_types.id')
                    ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                    ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                    ->where('account_meta.data', '!=', '"sharedExpense"')
                    ->where('transaction_types.type', 'Withdrawal')
                    ->groupBy('categories.id')
                    ->orderBy('amount')
                    ->get(['categories.id', 'categories.name', \DB::Raw('SUM(`transactions`.`amount`) AS `amount`')]);

    }

    /**
     * Gets a list of expense accounts and the expenses therein, grouped by that expense account.
     * This result excludes transfers to shared accounts which are expenses, technically.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function journalsByExpenseAccount(Carbon $start, Carbon $end)
    {
    }

    /**
     * With an equally misleading name, this query returns are transfers to shared accounts. These are considered
     * expenses.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function sharedExpenses(Carbon $start, Carbon $end)
    {
        return \TransactionJournal::
        leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                  ->leftJoin(
                                      'transactions', function (JoinClause $join) {
                                      $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where(
                                          'transactions.amount', '>', 0
                                      );
                                  }
                                  )
                                  ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                  ->leftJoin(
                                      'account_meta', function (JoinClause $join) {
                                      $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
                                  }
                                  )
                                  ->where('account_meta.data', '"sharedExpense"')
                                  ->after($start)
                                  ->before($end)
                                  ->where('transaction_types.type', 'Transfer')
                                  ->where('transaction_journals.user_id', \Auth::user()->id)
                                  ->get(
                                      ['transaction_journals.id', 'transaction_journals.description', 'transactions.account_id', 'accounts.name',
                                       'transactions.amount']
                                  );

    }

    /**
     * With a slightly misleading name, this query returns all transfers to shared accounts
     * which are technically expenses, since it won't be just your money that gets spend.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function sharedExpensesByCategory(Carbon $start, Carbon $end)
    {
        return \TransactionJournal::
        leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                  ->leftJoin(
                                      'transactions', function (JoinClause $join) {
                                      $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where(
                                          'transactions.amount', '>', 0
                                      );
                                  }
                                  )
                                  ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                  ->leftJoin(
                                      'account_meta', function (JoinClause $join) {
                                      $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
                                  }
                                  )
                                  ->leftJoin(
                                      'category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id'
                                  )
                                  ->leftJoin('categories', 'category_transaction_journal.category_id', '=', 'categories.id')
                                  ->where('account_meta.data', '"sharedExpense"')
                                  ->after($start)
                                  ->before($end)
                                  ->where('transaction_types.type', 'Transfer')
                                  ->where('transaction_journals.user_id', \Auth::user()->id)
                                  ->groupBy('categories.name')
                                  ->get(
                                      [
                                          'categories.id',
                                          'categories.name as name',
                                          \DB::Raw('SUM(`transactions`.`amount`) * -1 AS `amount`')
                                      ]
                                  );
    }
}
<?php

namespace FireflyIII\Report;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
                        function (Builder $query) {
                            $query->where('account_meta.data', '!=', '"sharedExpense"');
                            $query->orWhereNull('account_meta.data');
                        }
                    )
                    ->get(['accounts.*']);
    }

    /**
     * This method will get a list of all expenses in a certain time period that have no budget
     * and are balanced by a transfer to make up for it.
     *
     * @param \Account $account
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return Collection
     */
    public function balancedTransactionsList(\Account $account, Carbon $start, Carbon $end)
    {

        $set = \TransactionJournal::
        leftJoin('transaction_group_transaction_journal', 'transaction_group_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                                  ->leftJoin(
                                      'transaction_group_transaction_journal as otherFromGroup', function (JoinClause $join) {
                                      $join->on('otherFromGroup.transaction_group_id', '=', 'transaction_group_transaction_journal.transaction_group_id')
                                           ->on('otherFromGroup.transaction_journal_id', '!=', 'transaction_journals.id');
                                  }
                                  )
                                  ->leftJoin('transaction_journals as otherJournals', 'otherJournals.id', '=', 'otherFromGroup.transaction_journal_id')
                                  ->leftJoin('transaction_types', 'transaction_types.id', '=', 'otherJournals.transaction_type_id')
                                  ->leftJoin(
                                      'transactions', function (JoinClause $join) {
                                      $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('amount', '>', 0);
                                  }
                                  )
                                  ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'otherJournals.id')
                                  ->before($end)
                                  ->after($start)
                                  ->where('transaction_types.type', 'Withdrawal')
                                  ->where('transaction_journals.user_id', \Auth::user()->id)
                                  ->whereNull('budget_transaction_journal.budget_id')
                                  ->whereNull('transaction_journals.deleted_at')
                                  ->whereNull('otherJournals.deleted_at')
                                  ->where('transactions.account_id', $account->id)
                                  ->whereNotNull('transaction_group_transaction_journal.transaction_group_id')
                                  ->groupBy('transaction_journals.id')
                                  ->get(
                                      [
                                          'transaction_journals.id as transferId',
                                          'transaction_journals.description as transferDescription',
                                          'transaction_group_transaction_journal.transaction_group_id as groupId',
                                          'otherFromGroup.transaction_journal_id as expenseId',
                                          'otherJournals.description as expenseDescription',
                                          'transactions.amount'
                                      ]
                                  );

        return $set;
    }

    /**
     * This method will sum up all expenses in a certain time period that have no budget
     * and are balanced by a transfer to make up for it.
     *
     * @param \Account $account
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return float
     */
    public function balancedTransactionsSum(\Account $account, Carbon $start, Carbon $end)
    {
        $list = $this->balancedTransactionsList($account, $start, $end);
        $sum  = 0;
        foreach ($list as $entry) {
            $sum += floatval($entry->amount);
        }

        return $sum;
    }

    /**
     * Get a users accounts combined with various meta-data related to the start and end date.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getAllAccounts(Carbon $start, Carbon $end)
    {
        $set = \Auth::user()->accounts()
                    ->accountTypeIn(['Default account', 'Asset account', 'Cash account'])
                    ->leftJoin(
                        'account_meta', function (JoinClause $join) {
                        $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
                    }
                    )
                    ->where(
                        function (Builder $query) {
                            $query->where('account_meta.data', '!=', '"sharedExpense"');
                            $query->orWhereNull('account_meta.data');
                        }
                    )
                    ->get(['accounts.*']);
        $set->each(
            function (\Account $account) use ($start, $end) {
                /** @noinspection PhpParamsInspection */
                $account->startBalance = \Steam::balance($account, $start);
                $account->endBalance   = \Steam::balance($account, $end);
            }
        );

        return $set;
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
     * Grabs a summary of all expenses grouped by budget, related to the account.
     *
     * @param \Account $account
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return mixed
     */
    public function getBudgetSummary(\Account $account, Carbon $start, Carbon $end)
    {
        $set = \TransactionJournal::
        leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                                  ->leftJoin('budgets', 'budgets.id', '=', 'budget_transaction_journal.budget_id')
                                  ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                  ->leftJoin(
                                      'transactions', function (JoinClause $join) {
                                      $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '<', 0);
                                  }
                                  )
                                  ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                  ->before($end)
                                  ->after($start)
                                  ->where('accounts.id', $account->id)
                                  ->where('transaction_journals.user_id', \Auth::user()->id)
                                  ->where('transaction_types.type', 'Withdrawal')
                                  ->groupBy('budgets.id')
                                  ->orderBy('budgets.id')
                                  ->get(['budgets.id', 'budgets.name', \DB::Raw('SUM(`transactions`.`amount`) as `amount`')]);

        return $set;


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
                    ->orderBy('budgets.name', 'ASC')
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
        return \TransactionJournal::
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
                                  ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                  ->where('transaction_types.type', 'Withdrawal')
                                  ->where('acm_from.data', '!=', '"sharedExpense"')
                                  ->before($end)
                                  ->after($start)
                                  ->where('transaction_journals.user_id', \Auth::user()->id)
                                  ->groupBy('t_to.account_id')
                                  ->orderBy('amount', 'DESC')
                                  ->get(['t_to.account_id as id', 'ac_to.name as name', \DB::Raw('SUM(t_to.amount) as `amount`')]);
    }

    /**
     * This method returns all deposits into asset accounts, grouped by the revenue account,
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function journalsByRevenueAccount(Carbon $start, Carbon $end)
    {
        return \TransactionJournal::
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
                                  ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                  ->where('transaction_types.type', 'Deposit')
                                  ->where('acm_to.data', '!=', '"sharedExpense"')
                                  ->before($end)->after($start)
                                  ->where('transaction_journals.user_id', \Auth::user()->id)
                                  ->groupBy('t_from.account_id')->orderBy('amount')
                                  ->get(['t_from.account_id as account_id', 'ac_from.name as name', \DB::Raw('SUM(t_from.amount) as `amount`')]);
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

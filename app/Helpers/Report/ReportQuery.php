<?php

namespace FireflyIII\Helpers\Report;

use Auth;
use Carbon\Carbon;
use Crypt;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Steam;

/**
 * Class ReportQuery
 *
 * @package FireflyIII\Helpers\Report
 */
class ReportQuery implements ReportQueryInterface
{

    /**
     * This method will get a list of all expenses in a certain time period that have no budget
     * and are balanced by a transfer to make up for it.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return Collection
     */
    public function balancedTransactionsList(Account $account, Carbon $start, Carbon $end)
    {

        $set = TransactionJournal::
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
                                 ->before($end)->after($start)
                                 ->where('transaction_types.type', 'Withdrawal')
                                 ->where('transaction_journals.user_id', Auth::user()->id)
                                 ->whereNull('budget_transaction_journal.budget_id')->whereNull('transaction_journals.deleted_at')
                                 ->whereNull('otherJournals.deleted_at')
                                 ->where('transactions.account_id', $account->id)
                                 ->orderBy('transaction_journals.date', 'DESC')
                                 ->orderBy('transaction_journals.order', 'ASC')
                                 ->orderBy('transaction_journals.id', 'DESC')
                                 ->whereNotNull('transaction_group_transaction_journal.transaction_group_id')
                                 ->get(
                                     [
                                         'transaction_journals.*',
                                         'transactions.amount as queryAmount'
                                     ]
                                 );

        return $set;
    }

    /**
     * This method will get the sum of all expenses in a certain time period that have no budget
     * and are balanced by a transfer to make up for it.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return float
     */
    public function balancedTransactionsSum(Account $account, Carbon $start, Carbon $end)
    {
        return floatval($this->balancedTransactionsList($account, $start, $end)->sum('queryAmount'));
    }

    /**
     * This method returns all "expense" journals in a certain period, which are both transfers to a shared account
     * and "ordinary" withdrawals. The query used is almost equal to ReportQueryInterface::journalsByRevenueAccount but it does
     * not group and returns different fields.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     *
     */
    public function expenseInPeriod(Carbon $start, Carbon $end, $includeShared = false)
    {
        $query = $this->queryJournalsWithTransactions($start, $end);
        if ($includeShared === false) {
            // only get withdrawals not from a shared account
            // and transfers from a shared account.
            $query->where(
                function (Builder $query) {
                    $query->where(
                        function (Builder $q) {
                            $q->where('transaction_types.type', 'Withdrawal');
                            $q->where('acm_from.data', '!=', '"sharedAsset"');
                        }
                    );
                    $query->orWhere(
                        function (Builder $q) {
                            $q->where('transaction_types.type', 'Transfer');
                            $q->where('acm_to.data', '=', '"sharedAsset"');
                        }
                    );
                }
            );
        } else {
            // any withdrawal is fine.
            $query->where('transaction_types.type', 'Withdrawal');
        }
        $query->groupBy('transaction_journals.id')->orderBy('transaction_journals.date');

        // get everything, decrypt and return
        $data = $query->get(
            ['transaction_journals.id',
             'transaction_journals.description',
             'transaction_journals.encrypted',
             'transaction_types.type',
             DB::Raw('SUM(`t_from`.`amount`) as `queryAmount`'),
             'transaction_journals.date',
             't_to.account_id as account_id',
             'ac_to.name as name',
             'ac_to.encrypted as account_encrypted'
            ]
        );

        $data->each(
            function (Model $object) {
                $object->name = intval($object->account_encrypted) == 1 ? Crypt::decrypt($object->name) : $object->name;
            }
        );
        $data->sortByDesc('queryAmount');

        return $data;
    }

    /**
     * Get a users accounts combined with various meta-data related to the start and end date.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     */
    public function getAllAccounts(Carbon $start, Carbon $end, $includeShared = false)
    {
        $query = Auth::user()->accounts()->orderBy('accounts.name', 'ASC')
                     ->accountTypeIn(['Default account', 'Asset account', 'Cash account']);
        if ($includeShared === false) {
            $query->leftJoin(
                'account_meta', function (JoinClause $join) {
                $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
            }
            )
                  ->orderBy('accounts.name', 'ASC')
                  ->where(
                      function (Builder $query) {

                          $query->where('account_meta.data', '!=', '"sharedAsset"');
                          $query->orWhereNull('account_meta.data');

                      }
                  );
        }
        $set = $query->get(['accounts.*']);
        $set->each(
            function (Account $account) use ($start, $end) {
                /**
                 * The balance for today always incorporates transactions
                 * made on today. So to get todays "start" balance, we sub one
                 * day.
                 */
                $yesterday = clone $start;
                $yesterday->subDay();

                /** @noinspection PhpParamsInspection */
                $account->startBalance = Steam::balance($account, $yesterday);
                $account->endBalance   = Steam::balance($account, $end);
            }
        );

        return $set;
    }

    /**
     * Grabs a summary of all expenses grouped by budget, related to the account.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return mixed
     */
    public function getBudgetSummary(Account $account, Carbon $start, Carbon $end)
    {
        $query = $this->queryJournalsNoBudget($account, $start, $end);

        return $query->get(['budgets.id', 'budgets.name', DB::Raw('SUM(`transactions`.`amount`) as `queryAmount`')]);

    }

    /**
     * Get a list of transaction journals that have no budget, filtered for the specified account
     * and the specified date range.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return Collection
     */
    public function getTransactionsWithoutBudget(Account $account, Carbon $start, Carbon $end)
    {
        $query = $this->queryJournalsNoBudget($account, $start, $end);

        return $query->get(['budgets.name', 'transactions.amount as queryAmount', 'transaction_journals.*']);
    }

    /**
     * This method returns all "income" journals in a certain period, which are both transfers from a shared account
     * and "ordinary" deposits. The query used is almost equal to ReportQueryInterface::journalsByRevenueAccount but it does
     * not group and returns different fields.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     */
    public function incomeInPeriod(Carbon $start, Carbon $end, $includeShared = false)
    {
        $query = $this->queryJournalsWithTransactions($start, $end);
        if ($includeShared === false) {
            // only get deposits not to a shared account
            // and transfers to a shared account.
            $query->where(
                function (Builder $query) {
                    $query->where(
                        function (Builder $q) {
                            $q->where('transaction_types.type', 'Deposit');
                            $q->where('acm_to.data', '!=', '"sharedAsset"');
                        }
                    );
                    $query->orWhere(
                        function (Builder $q) {
                            $q->where('transaction_types.type', 'Transfer');
                            $q->where('acm_from.data', '=', '"sharedAsset"');
                        }
                    );
                }
            );
        } else {
            // any deposit is fine.
            $query->where('transaction_types.type', 'Deposit');
        }
        $query->groupBy('transaction_journals.id')->orderBy('transaction_journals.date');

        // get everything, decrypt and return
        $data = $query->get(
            ['transaction_journals.id',
             'transaction_journals.description',
             'transaction_journals.encrypted',
             'transaction_types.type',
             DB::Raw('SUM(`t_to`.`amount`) as `queryAmount`'),
             'transaction_journals.date',
             't_from.account_id as account_id',
             'ac_from.name as name',
             'ac_from.encrypted as account_encrypted'
            ]
        );

        $data->each(
            function (Model $object) {
                $object->name = intval($object->account_encrypted) == 1 ? Crypt::decrypt($object->name) : $object->name;
            }
        );
        $data->sortByDesc('queryAmount');

        return $data;
    }

    /**
     * Gets a list of expenses grouped by the budget they were filed under.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     */
    public function journalsByBudget(Carbon $start, Carbon $end, $includeShared = false)
    {
        $query = Auth::user()->transactionjournals()
                     ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                     ->leftJoin('budgets', 'budget_transaction_journal.budget_id', '=', 'budgets.id')
                     ->leftJoin(
                         'transactions', function (JoinClause $join) {
                         $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('transactions.amount', '<', 0);
                     }
                     )
                     ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id');
        if ($includeShared === false) {

            $query->leftJoin(
                'account_meta', function (JoinClause $join) {
                $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
            }
            )->where('account_meta.data', '!=', '"sharedAsset"');
        }
        $query->leftJoin('transaction_types', 'transaction_journals.transaction_type_id', '=', 'transaction_types.id')
              ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
              ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
              ->where('transaction_types.type', 'Withdrawal')
              ->groupBy('budgets.id')
              ->orderBy('budgets.name', 'ASC');

        return $query->get(['budgets.id', 'budgets.name', DB::Raw('SUM(`transactions`.`amount`) AS `spent`')]);
    }

    /**
     * Gets a list of categories and the expenses therein, grouped by the relevant category.
     * This result excludes transfers to shared accounts which are expenses, technically.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     */
    public function journalsByCategory(Carbon $start, Carbon $end, $includeShared = false)
    {
        $query = Auth::user()->transactionjournals()
                     ->leftJoin(
                         'category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id'
                     )
                     ->leftJoin('categories', 'category_transaction_journal.category_id', '=', 'categories.id')
                     ->leftJoin(
                         'transactions', function (JoinClause $join) {
                         $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('transactions.amount', '<', 0);
                     }
                     )
                     ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id');
        if ($includeShared === false) {
            $query->leftJoin(
                'account_meta', function (JoinClause $join) {
                $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
            }
            )->where('account_meta.data', '!=', '"sharedAsset"');
        }
        $query->leftJoin('transaction_types', 'transaction_journals.transaction_type_id', '=', 'transaction_types.id')
              ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
              ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
              ->where('transaction_types.type', 'Withdrawal')
              ->groupBy('categories.id')
              ->orderBy('queryAmount');

        $data = $query->get(['categories.id', 'categories.encrypted', 'categories.name', DB::Raw('SUM(`transactions`.`amount`) AS `queryAmount`')]);
        // decrypt data:
        $data->each(
            function (Model $object) {
                $object->name = intval($object->encrypted) == 1 ? Crypt::decrypt($object->name) : $object->name;
            }
        );

        return $data;

    }

    /**
     * Gets a list of expense accounts and the expenses therein, grouped by that expense account.
     * This result excludes transfers to shared accounts which are expenses, technically.
     *
     * So now it will include them!
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     */
    public function journalsByExpenseAccount(Carbon $start, Carbon $end, $includeShared = false)
    {
        $query = $this->queryJournalsWithTransactions($start, $end);
        if ($includeShared === false) {
            // get all withdrawals not from a shared accounts
            // and all transfers to a shared account
            $query->where(
                function (Builder $query) {
                    $query->where(
                        function (Builder $q) {
                            $q->where('transaction_types.type', 'Withdrawal');
                            $q->where('acm_from.data', '!=', '"sharedAsset"');
                        }
                    );
                    $query->orWhere(
                        function (Builder $q) {
                            $q->where('transaction_types.type', 'Transfer');
                            $q->where('acm_to.data', '=', '"sharedAsset"');
                        }
                    );
                }
            );
        } else {
            // any withdrawal goes:
            $query->where('transaction_types.type', 'Withdrawal');
        }
        $query->before($end)->after($start)
              ->where('transaction_journals.user_id', Auth::user()->id)
              ->groupBy('t_to.account_id')
              ->orderBy('queryAmount', 'DESC');

        $data = $query->get(['t_to.account_id as id', 'ac_to.name as name', 'ac_to.encrypted', DB::Raw('SUM(t_to.amount) as `queryAmount`')]);

        // decrypt
        $data->each(
            function (Model $object) {
                $object->name = intval($object->encrypted) == 1 ? Crypt::decrypt($object->name) : $object->name;
            }
        );

        return $data;
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
        return TransactionJournal::
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
                                 ->where('account_meta.data', '"sharedAsset"')
                                 ->after($start)
                                 ->before($end)
                                 ->where('transaction_types.type', 'Transfer')
                                 ->where('transaction_journals.user_id', Auth::user()->id)
                                 ->get(
                                     ['transaction_journals.id', 'transaction_journals.description', 'transactions.account_id', 'accounts.name',
                                      'transactions.amount as queryAmount']
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
        return TransactionJournal::
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
                                 ->where('account_meta.data', '"sharedAsset"')
                                 ->after($start)
                                 ->before($end)
                                 ->where('transaction_types.type', 'Transfer')
                                 ->where('transaction_journals.user_id', Auth::user()->id)
                                 ->groupBy('categories.name')
                                 ->get(
                                     [
                                         'categories.id',
                                         'categories.name as name',
                                         DB::Raw('SUM(`transactions`.`amount`) * -1 AS `queryAmount`')
                                     ]
                                 );
    }

    /**
     * @param Account $account
     * @param Budget  $budget
     * @param Carbon  $start
     * @param Carbon  $end
     * @param bool    $shared
     *
     * @return float
     */
    public function spentInBudget(Account $account, Budget $budget, Carbon $start, Carbon $end, $shared = false)
    {

        return floatval(
            Auth::user()->transactionjournals()
                ->leftJoin('transactions' , 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                ->where('transactions.amount', '<', 0)
                ->where('transactions.account_id', $account->id)
                ->before($end)
                ->after($start)
                ->where('budget_transaction_journal.budget_id', $budget->id)
                ->sum('transactions.amount')
        );
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
     *
     * This query will get all transaction journals and budget information for a specified account
     * in a certain date range, where the transaction journal does not have a budget.
     * There is no get() specified, this is up to the method itself.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return Builder
     */
    protected function queryJournalsNoBudget(Account $account, Carbon $start, Carbon $end)
    {
        return TransactionJournal::
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
                                 ->where('transaction_journals.user_id', Auth::user()->id)
                                 ->where('transaction_types.type', 'Withdrawal')
                                 ->groupBy('budgets.id')
                                 ->orderBy('budgets.name', 'ASC');
    }
}

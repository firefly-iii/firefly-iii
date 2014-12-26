<?php

namespace FireflyIII\Report;

use Carbon\Carbon;
use FireflyIII\Database\Account\Account as AccountRepository;
use FireflyIII\Database\SwitchUser;
use FireflyIII\Database\TransactionJournal\TransactionJournal as JournalRepository;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use stdClass;

// todo add methods to itnerface

/**
 * Class Report
 *
 *
 * @package FireflyIII\Report
 */
class Report implements ReportInterface
{

    use SwitchUser;

    /** @var AccountRepository */
    protected $_accounts;

    /** @var JournalRepository */
    protected $_journals;

    /**
     * @param AccountRepository $accounts
     */
    public function __construct(AccountRepository $accounts, JournalRepository $journals)
    {
        $this->_accounts = $accounts;
        $this->_journals = $journals;

    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param int    $limit
     *
     * @return Collection
     */
    public function expensesGroupedByAccount(Carbon $start, Carbon $end, $limit = 15)
    {
        return \TransactionJournal::
        leftJoin(
            'transactions as t_from', function ($join) {
            $join->on('t_from.transaction_journal_id', '=', 'transaction_journals.id')->where('t_from.amount', '<', 0);
        }
        )
                                  ->leftJoin('accounts as ac_from', 't_from.account_id', '=', 'ac_from.id')
                                  ->leftJoin(
                                      'account_meta as acm_from', function ($join) {
                                      $join->on('ac_from.id', '=', 'acm_from.account_id')->where('acm_from.name', '=', 'accountRole');
                                  }
                                  )
                                  ->leftJoin(
                                      'transactions as t_to', function ($join) {
                                      $join->on('t_to.transaction_journal_id', '=', 'transaction_journals.id')->where('t_to.amount', '>', 0);
                                  }
                                  )
                                  ->leftJoin('accounts as ac_to', 't_to.account_id', '=', 'ac_to.id')
                                  ->leftJoin(
                                      'account_meta as acm_to', function ($join) {
                                      $join->on('ac_to.id', '=', 'acm_to.account_id')->where('acm_to.name', '=', 'accountRole');
                                  }
                                  )
                                  ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                  ->where('transaction_types.type', 'Withdrawal')
                                  ->where('acm_from.data', '!=', '"sharedExpense"')
                                  ->before($end)->after($start)
                                  ->where('transaction_journals.user_id', \Auth::user()->id)
                                  ->groupBy('account_id')->orderBy('sum', 'DESC')->limit(15)
                                  ->get(['t_to.account_id as account_id', 'ac_to.name as name', \DB::Raw('SUM(t_to.amount) as `sum`')]);


    }

    /**
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getAccountsForMonth(Carbon $date)
    {
        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();
        $list = \Auth::user()->accounts()
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
        $list->each(
            function (\Account $account) use ($start, $end) {
                $account->startBalance = \Steam::balance($account, $start);
                $account->endBalance   = \Steam::balance($account, $end);
                $account->difference   = $account->endBalance - $account->startBalance;
            }
        );

        return $list;
    }

    /**
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getBudgetsForMonth(Carbon $date)
    {
        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();
        // all budgets
        /** @var Collection $budgets */
        $budgets = \Auth::user()->budgets()
                        ->leftJoin(
                            'budget_limits', function (JoinClause $join) use ($date) {
                            $join->on('budget_limits.budget_id', '=', 'budgets.id')->where('budget_limits.startdate', '=', $date->format('Y-m-d'));
                        }
                        )
                        ->get(['budgets.*', 'budget_limits.amount as budget_amount']);
        $amounts = \Auth::user()->transactionjournals()
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
                        ->orderBy('name','ASC')
                        ->get(['budgets.id', 'budgets.name', \DB::Raw('SUM(`transactions`.`amount`) AS `sum`')]);


        $spentNoBudget = 0;
        foreach ($budgets as $budget) {
            $budget->spent = 0;
            foreach ($amounts as $amount) {
                if (intval($budget->id) == intval($amount->id)) {
                    $budget->spent = floatval($amount->sum) * -1;
                }
                if (is_null($amount->id)) {
                    $spentNoBudget = floatval($amount->sum) * -1;
                }
            }
        }

        $noBudget                = new stdClass;
        $noBudget->id            = 0;
        $noBudget->name          = '(no budget)';
        $noBudget->budget_amount = 0;
        $noBudget->spent         = $spentNoBudget;

        // also get transfers to expense accounts (which are without a budget, and grouped).
        $transfers = $this->getTransfersToSharedAccounts($date);
        foreach($transfers as $transfer) {
            $noBudget->spent += floatval($transfer->sum) * -1;
        }


        $budgets->push($noBudget);

        return $budgets;
    }

    /**
     * @param Carbon $date
     * @param int    $limit
     *
     * @return Collection
     */
    public function getCategoriesForMonth(Carbon $date, $limit = 15)
    {
        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();
        // all categories.
        $amounts         = \Auth::user()->transactionjournals()
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
                                ->orderBy('sum')
                                ->get(['categories.id', 'categories.name', \DB::Raw('SUM(`transactions`.`amount`) AS `sum`')]);
        $spentNoCategory = 0;
        foreach ($amounts as $amount) {
            if (is_null($amount->id)) {
                $spentNoCategory = floatval($amount->sum) * -1;
            }
        }
        $noCategory       = new stdClass;
        $noCategory->id   = 0;
        $noCategory->name = '(no category)';
        $noCategory->sum  = $spentNoCategory;
        $amounts->push($noCategory);

        $return       = new Collection;
        $bottom       = new stdClass();
        $bottom->name = 'Others';
        $bottom->id   = 0;
        $bottom->sum  = 0;

        foreach ($amounts as $index => $entry) {
            if ($index < $limit) {
                $return->push($entry);
            } else {
                $bottom->sum += floatval($entry->sum);
            }
        }
        $return->push($bottom);

        return $return;
    }

    /**
     * @param Carbon $date
     * @param int    $limit
     *
     * @return Collection
     */
    public function getExpenseGroupedForMonth(Carbon $date, $limit = 15)
    {
        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();
        $userId = $this->_accounts->getUser()->id;

        $set       = \TransactionJournal::
        leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                        ->leftJoin(
                                            'transactions', function (JoinClause $join) {
                                            $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where(
                                                'transactions.amount', '>', 0
                                            );
                                        }
                                        )
                                        ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                        ->leftJoin(
                                            'transactions AS otherTransactions', function (JoinClause $join) {
                                            $join->on('transaction_journals.id', '=', 'otherTransactions.transaction_journal_id')->where(
                                                'otherTransactions.amount', '<', 0
                                            );
                                        }
                                        )
                                        ->leftJoin('accounts as otherAccounts', 'otherAccounts.id', '=', 'otherTransactions.account_id')
                                        ->leftJoin(
                                            'account_meta', function (JoinClause $join) {
                                            $join->on('otherAccounts.id', '=', 'account_meta.account_id')->where('account_meta.name', '=', 'accountRole');
                                        }
                                        )
                                        ->where('date', '>=', $start->format('Y-m-d'))
                                        ->where('date', '<=', $end->format('Y-m-d'))
                                        ->where('account_meta.data', '!=', '"sharedExpense"')
                                        ->where('transaction_types.type', 'Withdrawal')
                                        ->whereNull('transaction_journals.deleted_at')
                                        ->where('transaction_journals.user_id', $userId)
                                        ->groupBy('account_id')
                                        ->orderBy('sum', 'ASC')
                                        ->get(
                                            [
                                                'transactions.account_id',
                                                'accounts.name',
                                                \DB::Raw('SUM(`transactions`.`amount`) * -1 AS `sum`')
                                            ]
                                        );
        $transfers = $this->getTransfersToSharedAccounts($date);
        // merge $transfers into $set
        foreach ($transfers as $transfer) {
            if (!is_null($transfer->account_id)) {
                $set->push($transfer);
            }
        }
        // sort the list.
        $set                = $set->sortBy(
            function ($entry) {
                return floatval($entry->sum);
            }
        );
        $return             = new Collection;
        $bottom             = new stdClass();
        $bottom->name       = 'Others';
        $bottom->account_id = 0;
        $bottom->sum        = 0;

        $count = 0;
        foreach ($set as $entry) {
            if ($count < $limit) {
                $return->push($entry);
            } else {
                $bottom->sum += floatval($entry->sum);
            }
            $count++;
        }

        $return->push($bottom);

        return $return;

    }

    /**
     * @param Carbon $date
     * @param bool   $shared
     *
     * @return Collection
     */
    public function getIncomeForMonth(Carbon $date, $shared = false)
    {
        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();
        $userId = $this->_accounts->getUser()->id;

        $list = \TransactionJournal::withRelevantData()
                                   ->leftJoin('transactions', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                   ->leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
                                   ->leftJoin(
                                       'account_meta', function (JoinClause $join) {
                                       $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
                                   }
                                   )
                                   ->transactionTypes(['Deposit'])
                                   ->where('transaction_journals.user_id', $userId)
                                   ->where('transactions.amount', '>', 0)
                                   ->where('account_meta.data', '!=', '"sharedExpense"')
                                   ->orderBy('date', 'ASC')
                                   ->before($end)->after($start)->get(['transaction_journals.*']);

        // incoming from a shared account: it's profit (income):
        $transfers = \TransactionJournal::withRelevantData()
                                        ->leftJoin('transactions', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                        ->leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
                                        ->leftJoin(
                                            'account_meta', function (JoinClause $join) {
                                            $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
                                        }
                                        )
                                        ->transactionTypes(['Transfer'])
                                        ->where('transaction_journals.user_id', $userId)
                                        ->where('transactions.amount', '<', 0)
                                        ->where('account_meta.data', '=', '"sharedExpense"')
                                        ->orderBy('date', 'ASC')
                                        ->before($end)->after($start)->get(['transaction_journals.*']);

        $list = $list->merge($transfers);
        $list->sort(
            function (\TransactionJournal $journal) {
                return $journal->date->format('U');
            }
        );

        return $list;
    }

    /**
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getTransfersToSharedAccounts(Carbon $date)
    {
        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();

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
                                  ->where('date', '>=', $start->format('Y-m-d'))
                                  ->where('date', '<=', $end->format('Y-m-d'))
                                  ->where('transaction_types.type', 'Transfer')
                                  ->where('transaction_journals.user_id', \Auth::user()->id)
                                  ->get(
                                      [
                                          'transactions.account_id',
                                          'accounts.name',
                                          \DB::Raw('SUM(`transactions`.`amount`) * -1 AS `sum`')
                                      ]
                                  );
    }

    /**
     * @param Carbon $start
     *
     * @return array
     */
    public function listOfMonths(Carbon $start)
    {
        $end    = Carbon::now();
        $months = [];
        while ($start <= $end) {
            $months[] = [
                'formatted' => $start->format('F Y'),
                'month'     => intval($start->format('m')),
                'year'      => intval($start->format('Y')),
            ];
            $start->addMonth();
        }

        return $months;
    }

    /**
     * @param Carbon $start
     *
     * @return array
     */
    public function listOfYears(Carbon $start)
    {
        $end   = Carbon::now();
        $years = [];
        while ($start <= $end) {
            $years[] = $start->format('Y');
            $start->addYear();
        }

        return $years;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param int    $limit
     *
     * @return Collection
     */
    public function revenueGroupedByAccount(Carbon $start, Carbon $end, $limit = 15)
    {
        return \TransactionJournal::
        leftJoin(
            'transactions as t_from', function ($join) {
            $join->on('t_from.transaction_journal_id', '=', 'transaction_journals.id')->where('t_from.amount', '<', 0);
        }
        )
                                  ->leftJoin('accounts as ac_from', 't_from.account_id', '=', 'ac_from.id')
                                  ->leftJoin(
                                      'account_meta as acm_from', function ($join) {
                                      $join->on('ac_from.id', '=', 'acm_from.account_id')->where('acm_from.name', '=', 'accountRole');
                                  }
                                  )
                                  ->leftJoin(
                                      'transactions as t_to', function ($join) {
                                      $join->on('t_to.transaction_journal_id', '=', 'transaction_journals.id')->where('t_to.amount', '>', 0);
                                  }
                                  )
                                  ->leftJoin('accounts as ac_to', 't_to.account_id', '=', 'ac_to.id')
                                  ->leftJoin(
                                      'account_meta as acm_to', function ($join) {
                                      $join->on('ac_to.id', '=', 'acm_to.account_id')->where('acm_to.name', '=', 'accountRole');
                                  }
                                  )
                                  ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                  ->where('transaction_types.type', 'Deposit')
                                  ->where('acm_to.data', '!=', '"sharedExpense"')
                                  ->before($end)->after($start)
                                  ->where('transaction_journals.user_id', \Auth::user()->id)
                                  ->groupBy('account_id')->orderBy('sum')->limit(15)
                                  ->get(['t_from.account_id as account_id', 'ac_from.name as name', \DB::Raw('SUM(t_from.amount) as `sum`')]);


    }

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function yearBalanceReport(Carbon $date)
    {
        $start            = clone $date;
        $end              = clone $date;
        $sharedAccounts   = [];
        $sharedCollection = \Auth::user()->accounts()
                                 ->leftJoin('account_meta', 'account_meta.account_id', '=', 'accounts.id')
                                 ->where('account_meta.name', '=', 'accountRole')
                                 ->where('account_meta.data', '=', json_encode('sharedExpense'))
                                 ->get(['accounts.id']);

        foreach ($sharedCollection as $account) {
            $sharedAccounts[] = $account->id;
        }

        $accounts = $this->_accounts->getAssetAccounts()->filter(
            function (\Account $account) use ($sharedAccounts) {
                if (!in_array($account->id, $sharedAccounts)) {
                    return $account;
                }

                return null;
            }
        );
        $report   = [];
        $start->startOfYear();
        $end->endOfYear();

        foreach ($accounts as $account) {
            $report[] = [
                'start'   => \Steam::balance($account, $start),
                'end'     => \Steam::balance($account, $end),
                'account' => $account,
                'shared'  => $account->accountRole == 'sharedExpense'
            ];
        }

        return $report;
    }

} 
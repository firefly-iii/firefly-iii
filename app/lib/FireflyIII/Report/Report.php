<?php

namespace FireflyIII\Report;

use Carbon\Carbon;
use FireflyIII\Database\Account\Account as AccountRepository;
use FireflyIII\Database\SwitchUser;
use FireflyIII\Database\TransactionJournal\TransactionJournal as JournalRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use stdClass;

// todo add methods to interface

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
    /** @var  \FireflyIII\Report\ReportHelperInterface */
    protected $_helper;
    /** @var JournalRepository */
    protected $_journals;
    /** @var  \FireflyIII\Report\ReportQueryInterface */
    protected $_queries;

    /**
     * @param AccountRepository $accounts
     */
    public function __construct(AccountRepository $accounts, JournalRepository $journals)
    {
        $this->_accounts = $accounts;
        $this->_journals = $journals;
        $this->_queries  = \App::make('FireflyIII\Report\ReportQueryInterface');
        $this->_helper   = \App::make('FireflyIII\Report\ReportHelperInterface');


    }

    /**
     * TODO Used in yearly report, so not ready for cleanup.
     *
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
                                  ->groupBy('account_id')->orderBy('sum', 'DESC')->limit($limit)
                                  ->get(['t_to.account_id as account_id', 'ac_to.name as name', \DB::Raw('SUM(t_to.amount) as `sum`')]);


    }

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function getAccountsForMonth(Carbon $date)
    {
        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();
        $list     = $this->_queries->accountList();
        $accounts = [];
        /** @var \Account $account */
        foreach ($list as $account) {
            $id            = intval($account->id);
            $accounts[$id] = [
                'name'         => $account->name,
                'startBalance' => \Steam::balance($account, $start),
                'endBalance'   => \Steam::balance($account, $end)
            ];

            $accounts[$id]['difference'] = $accounts[$id]['endBalance'] - $accounts[$id]['startBalance'];
        }

        return $accounts;
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
        $set                   = $this->_queries->getAllBudgets($date);
        $budgets               = $this->_helper->makeArray($set);
        $amountSet             = $this->_queries->journalsByBudget($start, $end);
        $amounts               = $this->_helper->makeArray($amountSet);
        $combined              = $this->_helper->mergeArrays($budgets, $amounts);
        $combined[0]['spent']  = isset($combined[0]['spent']) ? $combined[0]['spent'] : 0.0;
        $combined[0]['amount'] = isset($combined[0]['amount']) ? $combined[0]['amount'] : 0.0;
        $combined[0]['name']   = 'No budget';

        // find transactions to shared expense accounts, which are without a budget by default:
        $transfers = $this->_queries->sharedExpenses($start, $end);
        foreach ($transfers as $transfer) {
            $combined[0]['spent'] += floatval($transfer->amount) * -1;
        }

        return $combined;
    }

    /**
     * @param Carbon $date
     * @param int    $limit
     *
     * @return array
     */
    public function getCategoriesForMonth(Carbon $date, $limit = 15)
    {
        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();
        // all categories.
        $result     = $this->_queries->journalsByCategory($start, $end);
        $categories = $this->_helper->makeArray($result);

        // all transfers
        $result    = $this->_queries->sharedExpensesByCategory($start, $end);
        $transfers = $this->_helper->makeArray($result);
        $merged    = $this->_helper->mergeArrays($categories, $transfers);

        // sort.
        $sorted = $this->_helper->sortNegativeArray($merged);

        // limit to $limit:
        $cut = $this->_helper->limitArray($sorted, $limit);

        return $cut;
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
        $transfers = $this->getTransfersToSharedGroupedByAccounts($date);
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
    public function getPiggyBanksForMonth(Carbon $date)
    {
        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();

        $set = \PiggyBank::
        leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
                         ->where('accounts.user_id', \Auth::user()->id)
                         ->where('repeats', 0)
                         ->where(
                             function (Builder $query) use ($start, $end) {
                                 $query->whereNull('piggy_banks.deleted_at');
                                 $query->orWhere(
                                     function (Builder $query) use ($start, $end) {
                                         $query->whereNotNull('piggy_banks.deleted_at');
                                         $query->where('piggy_banks.deleted_at', '>=', $start->format('Y-m-d 00:00:00'));
                                         $query->where('piggy_banks.deleted_at', '<=', $end->format('Y-m-d 00:00:00'));
                                     }
                                 );
                             }
                         )
                         ->get(['piggy_banks.*']);


    }

    /**
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getTransfersToSharedGroupedByAccounts(Carbon $date)
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
                                  ->groupBy('accounts.name')
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
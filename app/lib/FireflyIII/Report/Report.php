<?php

namespace FireflyIII\Report;

use Carbon\Carbon;
use FireflyIII\Database\Account\Account as AccountRepository;
use Illuminate\Support\Collection;

// todo add methods to itnerface

/**
 * Class Report
 *
 * @SuppressWarnings("CamelCase")
 *
 * @package FireflyIII\Report
 */
class Report implements ReportInterface
{
    /** @var AccountRepository */
    protected $_accounts;

    /**
     * @param AccountRepository $accounts
     */
    public function __construct(AccountRepository $accounts)
    {
        $this->_accounts = $accounts;

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

    use SwitchUser;


    /**
     * @param \Account $account
     * @param Carbon   $month
     *
     * @return float
     */
    public function getExpenseByMonth(\Account $account, Carbon $month)
    {
        if (isset($account->sharedExpense) && $account->sharedExpense === true) {
            $shared = true;
        } else {
            if (isset($account->sharedExpense) && $account->sharedExpense === false) {
                $shared = false;
            } else {
                $shared = ($account->getMeta('accountRole') == 'sharedExpense');
            }
        }

        $start = clone $month;
        $end   = clone $month;
        $start->startOfMonth();
        $end->endOfMonth();
        $sum = 0;

        // get all journals.
        $journals = \TransactionJournal::with(['transactionType', 'transactions'])->whereIn(
            'id', function ($query) use ($account, $start, $end) {
            $query->select('transaction_journal_id')
                  ->from('transactions')
                  ->where('account_id', $account->id);
        }
        )->before($end)->after($start)->get();


        if ($shared) {
            $expenses = $journals->filter(
                function (\TransactionJournal $journal) use ($account) {
                    // any withdrawal is an expense:
                    if ($journal->transactionType->type == 'Withdrawal') {
                        return $journal;
                    }
                    // any transfer away from this account is an expense.
                    if ($journal->transactionType->type == 'Transfer') {
                        /** @var \Transaction $t */
                        foreach ($journal->transactions as $t) {
                            if ($t->account_id == $account->id && floatval($t->amount) < 0) {
                                return $journal;
                            }
                        }
                    }

                    return null;
                }
            );
        } else {
            $expenses = $journals->filter(
                function (\TransactionJournal $journal) use ($account) {
                    // only withdrawals are expenses:
                    if ($journal->transactionType->type == 'Withdrawal') {
                        return $journal;
                    }
                    // transfers TO a shared account are also expenses.
                    if ($journal->transactionType->type == 'Transfer') {
                        /** @var \Transaction $t */
                        foreach ($journal->transactions() as $t) {
                            if ($t->account->getMeta('accountRole') == 'sharedExpense') {
                                echo '#' . $journal->id . ' is a shared expense!<br>';

                                return $journal;
                            }
                        }
                    }

                    return null;
                }
            );
        }
        /** @var \TransactionJournal $expense */
        foreach ($expenses as $expense) {
            $sum += $expense->getAmount();
        }


        return $sum;
    }

    /**
     * @param \Account $account
     * @param Carbon   $month
     *
     * @return float
     */
    public function getIncomeByMonth(\Account $account, Carbon $month)
    {
        if (isset($account->sharedExpense) && $account->sharedExpense === true) {
            $shared = true;
        } else {
            if (isset($account->sharedExpense) && $account->sharedExpense === false) {
                $shared = false;
            } else {
                $shared = ($account->getMeta('accountRole') == 'sharedExpense');
            }
        }

        $start = clone $month;
        $end   = clone $month;
        $start->startOfMonth();
        $end->endOfMonth();
        $sum = 0;

        // get all journals.
        $journals = \TransactionJournal::with(['transactionType', 'transactions'])->whereIn(
            'id', function ($query) use ($account, $start, $end) {
            $query->select('transaction_journal_id')
                  ->from('transactions')
                  ->where('account_id', $account->id);
        }
        )->before($end)->after($start)->get();


        if ($shared) {
            $incomes = $journals->filter(
                function (\TransactionJournal $journal) use ($account) {
                    // any deposit is an income:
                    if ($journal->transactionType->type == 'Deposit') {
                        return $journal;
                    }
                    // any transfer TO this account is an income.
                    if ($journal->transactionType->type == 'Transfer') {
                        /** @var \Transaction $t */
                        foreach ($journal->transactions as $t) {
                            if ($t->account_id == $account->id && floatval($t->amount) > 0) {
                                return $journal;
                            }
                        }
                    }

                    return null;
                }
            );
        } else {
            $incomes = $journals->filter(
                function (\TransactionJournal $journal) use ($account) {
                    // only deposits are incomes:
                    if ($journal->transactionType->type == 'Deposit') {
                        return $journal;
                    }

                    return null;
                }
            );
        }
        /** @var \TransactionJournal $expense */
        foreach ($incomes as $income) {
            $sum += $income->getAmount();
        }


        return $sum;
    }

} 
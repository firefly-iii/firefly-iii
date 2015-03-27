<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Exception;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Http\Requests;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Database\Query\JoinClause;
use Preferences;
use Session;
use Steam;
use View;

/**
 * Class ReportController
 *
 * @package FireflyIII\Http\Controllers
 */
class ReportController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        View::share('title', 'Reports');
        View::share('mainTitleIcon', 'fa-line-chart');

    }

    /**
     * @param string $year
     * @param string $month
     *
     * @return \Illuminate\View\View
     */
    public function budget($year = '2014', $month = '1', ReportQueryInterface $query)
    {
        try {
            new Carbon($year . '-' . $month . '-01');
        } catch (Exception $e) {
            return view('error')->with('message', 'Invalid date');
        }
        $date  = new Carbon($year . '-' . $month . '-01');
        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();
        $start->subDay();

        // shared accounts preference:
        $pref              = Preferences::get('showSharedReports', false);
        $showSharedReports = $pref->data;


        $dayEarly     = clone $date;
        $subTitle     = 'Budget report for ' . $date->format('F Y');
        $subTitleIcon = 'fa-calendar';
        $dayEarly     = $dayEarly->subDay();
        $accounts     = $query->getAllAccounts($start, $end, $showSharedReports);
        $start->addDay();

        $accounts->each(
            function (Account $account) use ($start, $end, $query) {
                $budgets        = $query->getBudgetSummary($account, $start, $end);
                $balancedAmount = $query->balancedTransactionsSum($account, $start, $end);
                $array          = [];
                $hide           = true;
                foreach ($budgets as $budget) {
                    $id         = intval($budget->id);
                    $data       = $budget->toArray();
                    $array[$id] = $data;
                    if (floatval($data['amount']) != 0) {
                        $hide = false;
                    }
                }
                $account->hide              = $hide;
                $account->budgetInformation = $array;
                $account->balancedAmount    = $balancedAmount;

            }
        );

        $start = clone $date;
        $start->startOfMonth();

        /**
         * Start getBudgetsForMonth DONE
         */
        $set                  = Auth::user()->budgets()->orderBy('budgets.name', 'ASC')
                                    ->leftJoin(
                                        'budget_limits', function (JoinClause $join) use ($date) {
                                        $join->on('budget_limits.budget_id', '=', 'budgets.id')->where('budget_limits.startdate', '=', $date->format('Y-m-d'));
                                    }
                                    )
                                    ->get(['budgets.*', 'budget_limits.amount as amount']);
        $budgets              = Steam::makeArray($set);
        $amountSet            = $query->journalsByBudget($start, $end, $showSharedReports);
        $amounts              = Steam::makeArray($amountSet);
        $budgets              = Steam::mergeArrays($budgets, $amounts);
        $budgets[0]['spent']  = isset($budgets[0]['spent']) ? $budgets[0]['spent'] : 0.0;
        $budgets[0]['amount'] = isset($budgets[0]['amount']) ? $budgets[0]['amount'] : 0.0;
        $budgets[0]['name']   = 'No budget';

        // find transactions to shared asset accounts, which are without a budget by default:
        // which is only relevant when shared asset accounts are hidden.
        if ($showSharedReports === false) {
            $transfers = $query->sharedExpenses($start, $end);
            foreach ($transfers as $transfer) {
                $budgets[0]['spent'] += floatval($transfer->amount) * -1;
            }
        }

        /**
         * End getBudgetsForMonth DONE
         */

        return view('reports.budget', compact('subTitle', 'year', 'month', 'subTitleIcon', 'date', 'accounts', 'budgets', 'dayEarly'));

    }

    /**
     * @param ReportHelperInterface $helper
     *
     * @return View
     */
    public function index(ReportHelperInterface $helper)
    {
        $start         = Session::get('first');
        $months        = $helper->listOfMonths($start);
        $years         = $helper->listOfYears($start);
        $title         = 'Reports';
        $mainTitleIcon = 'fa-line-chart';

        return view('reports.index', compact('years', 'months', 'title', 'mainTitleIcon'));
    }

    /**
     * @param Account $account
     * @param string  $year
     * @param string  $month
     *
     * @return \Illuminate\View\View
     */
    public function modalBalancedTransfers(Account $account, $year = '2014', $month = '1', ReportQueryInterface $query)
    {

        try {
            new Carbon($year . '-' . $month . '-01');
        } catch (Exception $e) {
            return view('error')->with('message', 'Invalid date');
        }
        $start = new Carbon($year . '-' . $month . '-01');
        $end   = clone $start;
        $end->endOfMonth();

        $journals = $query->balancedTransactionsList($account, $start, $end);

        return view('reports.modal-journal-list', compact('journals'));


    }

    /**
     * @param Account              $account
     * @param string               $year
     * @param string               $month
     * @param ReportQueryInterface $query
     *
     * @return View
     */
    public function modalLeftUnbalanced(Account $account, $year = '2014', $month = '1', ReportQueryInterface $query)
    {
        try {
            new Carbon($year . '-' . $month . '-01');
        } catch (Exception $e) {
            return view('error')->with('message', 'Invalid date');
        }
        $start = new Carbon($year . '-' . $month . '-01');
        $end   = clone $start;
        $end->endOfMonth();
        $set = $query->getTransactionsWithoutBudget($account, $start, $end);

        $journals = $set->filter(
            function (TransactionJournal $journal) {
                $count = $journal->transactiongroups()->where('relation', 'balance')->count();
                if ($count == 0) {
                    return $journal;
                }
            }
        );

        return view('reports.modal-journal-list', compact('journals'));
    }

    /**
     * @param Account $account
     * @param string  $year
     * @param string  $month
     *
     * @return \Illuminate\View\View
     */
    public function modalNoBudget(Account $account, $year = '2014', $month = '1', ReportQueryInterface $query)
    {
        try {
            new Carbon($year . '-' . $month . '-01');
        } catch (Exception $e) {
            return view('error')->with('message', 'Invalid date');
        }
        $start = new Carbon($year . '-' . $month . '-01');
        $end   = clone $start;
        $end->endOfMonth();
        $journals = $query->getTransactionsWithoutBudget($account, $start, $end);

        return view('reports.modal-journal-list', compact('journals'));

    }

    /**
     * @param string $year
     * @param string $month
     *
     * @return \Illuminate\View\View
     */
    public function month($year = '2014', $month = '1', ReportQueryInterface $query)
    {
        try {
            new Carbon($year . '-' . $month . '-01');
        } catch (Exception $e) {
            return view('error')->with('message', 'Invalid date.');
        }
        $date         = new Carbon($year . '-' . $month . '-01');
        $subTitle     = 'Report for ' . $date->format('F Y');
        $subTitleIcon = 'fa-calendar';
        $displaySum   = true; // to show sums in report.

        $pref              = Preferences::get('showSharedReports', false);
        $showSharedReports = $pref->data;


        /**
         *
         * get income for month (date)
         *
         */

        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();

        /**
         * Start getIncomeForMonth DONE
         */
        $income = $query->incomeByPeriod($start, $end, $showSharedReports);
        /**
         * End getIncomeForMonth DONE
         */
        /**
         * Start getExpenseGroupedForMonth DONE
         */
        $set      = $query->journalsByExpenseAccount($start, $end, $showSharedReports);
        $expenses = Steam::makeArray($set);
        $expenses = Steam::sortArray($expenses);
        $expenses = Steam::limitArray($expenses, 10);
        /**
         * End getExpenseGroupedForMonth DONE
         */
        /**
         * Start getBudgetsForMonth DONE
         */
        $set                  = Auth::user()->budgets()
                                    ->leftJoin(
                                        'budget_limits', function (JoinClause $join) use ($date) {
                                        $join->on('budget_limits.budget_id', '=', 'budgets.id')->where('budget_limits.startdate', '=', $date->format('Y-m-d'));
                                    }
                                    )
                                    ->get(['budgets.*', 'budget_limits.amount as amount']);
        $budgets              = Steam::makeArray($set);
        $amountSet            = $query->journalsByBudget($start, $end, $showSharedReports);
        $amounts              = Steam::makeArray($amountSet);
        $budgets              = Steam::mergeArrays($budgets, $amounts);
        $budgets[0]['spent']  = isset($budgets[0]['spent']) ? $budgets[0]['spent'] : 0.0;
        $budgets[0]['amount'] = isset($budgets[0]['amount']) ? $budgets[0]['amount'] : 0.0;
        $budgets[0]['name']   = 'No budget';

        // find transactions to shared expense accounts, which are without a budget by default:
        if ($showSharedReports === false) {
            $transfers = $query->sharedExpenses($start, $end);
            foreach ($transfers as $transfer) {
                $budgets[0]['spent'] += floatval($transfer->amount) * -1;
            }
        }

        /**
         * End getBudgetsForMonth DONE
         */
        /**
         * Start getCategoriesForMonth DONE
         */
        // all categories.
        $result     = $query->journalsByCategory($start, $end);
        $categories = Steam::makeArray($result);

        // all transfers
        if ($showSharedReports === false) {
            $result    = $query->sharedExpensesByCategory($start, $end);
            $transfers = Steam::makeArray($result);
            $merged    = Steam::mergeArrays($categories, $transfers);
        } else {
            $merged = $categories;
        }

        // sort.
        $sorted = Steam::sortNegativeArray($merged);

        // limit to $limit:
        $categories = Steam::limitArray($sorted, 10);
        /**
         * End getCategoriesForMonth DONE
         */
        /**
         * Start getAccountsForMonth
         */
        $list     = $query->accountList($showSharedReports);
        $accounts = [];
        /** @var Account $account */
        foreach ($list as $account) {
            $id = intval($account->id);
            /** @noinspection PhpParamsInspection */
            $accounts[$id] = [
                'name'         => $account->name,
                'startBalance' => Steam::balance($account, $start),
                'endBalance'   => Steam::balance($account, $end)
            ];

            $accounts[$id]['difference'] = $accounts[$id]['endBalance'] - $accounts[$id]['startBalance'];
        }

        /**
         * End getAccountsForMonth
         */


        return view(
            'reports.month',
            compact(
                'income', 'expenses', 'budgets', 'accounts', 'categories',
                'date', 'subTitle', 'displaySum', 'subTitleIcon'
            )
        );
    }

    /**
     * @param $year
     *
     * @return $this
     */
    public function year($year, ReportHelperInterface $helper, ReportQueryInterface $query)
    {
        try {
            new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            return view('error')->with('message', 'Invalid date.');
        }

        $pref              = Preferences::get('showSharedReports', false);
        $showSharedReports = $pref->data;
        $date              = new Carbon('01-01-' . $year);
        $end               = clone $date;
        $end->endOfYear();
        $title           = 'Reports';
        $subTitle        = $year;
        $subTitleIcon    = 'fa-bar-chart';
        $mainTitleIcon   = 'fa-line-chart';
        $balances        = $helper->yearBalanceReport($date, $showSharedReports);
        $groupedIncomes  = $query->journalsByRevenueAccount($date, $end, $showSharedReports);
        $groupedExpenses = $query->journalsByExpenseAccount($date, $end, $showSharedReports);

        //$groupedExpenses = $helper-> expensesGroupedByAccount($date, $end, 15);

        return view(
            'reports.year', compact('date', 'groupedIncomes', 'groupedExpenses', 'year', 'balances', 'title', 'subTitle', 'subTitleIcon', 'mainTitleIcon')
        );
    }


}

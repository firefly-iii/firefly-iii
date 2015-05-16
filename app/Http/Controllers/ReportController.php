<?php namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
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

    /** @var ReportHelperInterface */
    protected $helper;
    /** @var ReportQueryInterface */
    protected $query;

    /**
     * @param ReportHelperInterface $helper
     * @param ReportQueryInterface  $query
     */
    public function __construct(ReportHelperInterface $helper, ReportQueryInterface $query)
    {
        parent::__construct();
        $this->query  = $query;
        $this->helper = $helper;

        View::share('title', trans('firefly.reports'));
        View::share('mainTitleIcon', 'fa-line-chart');

    }

    /**
     * @return View
     * @internal param ReportHelperInterface $helper
     *
     */
    public function index(AccountRepositoryInterface $repository)
    {
        $start         = Session::get('first');
        $months        = $this->helper->listOfMonths($start);
        $title         = 'Reports';
        $mainTitleIcon = 'fa-line-chart';

        // does the user have shared accounts?
        $accounts  = $repository->getAccounts(['Default account', 'Asset account']);
        $hasShared = false;

        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->getMeta('accountRole') == 'sharedAsset') {
                $hasShared = true;
            }
        }


        return view('reports.index', compact('months', 'title', 'mainTitleIcon', 'hasShared'));
    }

    /**
     * @param Account $account
     * @param string  $year
     * @param string  $month
     *
     * @return \Illuminate\View\View
     */
    public function modalBalancedTransfers(Account $account, $year = '2014', $month = '1')
    {

        $start = new Carbon($year . '-' . $month . '-01');
        $end   = clone $start;
        $end->endOfMonth();

        $journals = $this->query->balancedTransactionsList($account, $start, $end);

        return view('reports.modal-journal-list', compact('journals'));


    }

    /**
     * @param Account $account
     * @param string  $year
     * @param string  $month
     *
     * @return View
     * @internal param ReportQueryInterface $query
     *
     */
    public function modalLeftUnbalanced(Account $account, $year = '2014', $month = '1')
    {
        $start = new Carbon($year . '-' . $month . '-01');
        $end   = clone $start;
        $end->endOfMonth();
        $set = $this->query->getTransactionsWithoutBudget($account, $start, $end);

        $journals = $set->filter(
            function (TransactionJournal $journal) {
                $count = $journal->transactiongroups()->where('relation', 'balance')->count();
                if ($count == 0) {
                    return $journal;
                }

                return null;
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
    public function modalNoBudget(Account $account, $year = '2014', $month = '1')
    {
        $start = new Carbon($year . '-' . $month . '-01');
        $end   = clone $start;
        $end->endOfMonth();
        $journals = $this->query->getTransactionsWithoutBudget($account, $start, $end);

        return view('reports.modal-journal-list', compact('journals'));

    }

    /**
     * @param string $year
     * @param string $month
     *
     * @return \Illuminate\View\View
     */
    public function month($year = '2014', $month = '1', $shared = false)
    {
        $date         = new Carbon($year . '-' . $month . '-01');
        $subTitle     = 'Report for ' . $date->format('F Y');
        $subTitleIcon = 'fa-calendar';
        $displaySum   = true; // to show sums in report.
        $end          = clone $date;
        $start        = clone $date;
        if ($shared == 'shared') {
            $shared = true;
        }

        // set start and end.
        $start->startOfMonth();
        $end->endOfMonth();

        // get all income and expenses. it's OK.
        $income      = $this->query->incomeInPeriod($start, $end, $shared);
        $expensesSet = $this->query->journalsByExpenseAccount($start, $end, $shared);

        /**
         * INCLUDE ORIGINAL BUDGET REPORT HERE:
         */
        // should show shared reports?
        /** @var Preference $pref */
        $accountAmounts = []; // array with sums of spent amounts on each account.
        $accounts       = $this->query->getAllAccounts($start, $end, $shared); // all accounts and some data.

        foreach ($accounts as $account) {

            $budgets                      = $this->query->getBudgetSummary($account, $start, $end);// get budget summary for this account:
            $balancedAmount               = $this->query->balancedTransactionsSum($account, $start, $end);
            $accountAmounts[$account->id] = $balancedAmount;
            // balance out the transactions (see transaction groups & tags) ^^

            // array with budget information for each account:
            $array = [];
            // should always hide account
            $hide = true;
            // loop all budgets
            /** @var \FireflyIII\Models\Budget $budget */
            foreach ($budgets as $budget) {
                $id         = intval($budget->id);
                $data       = $budget->toArray();
                $array[$id] = $data;

                // no longer hide account if any budget has money in it.
                if (floatval($data['queryAmount']) != 0) {
                    $hide = false;
                }
                $accountAmounts[$account->id] += $data['queryAmount'];
            }
            $account->hide              = $hide;
            $account->budgetInformation = $array;
            $account->balancedAmount    = $balancedAmount;

        }
        /**
         * END ORIGINAL BUDGET REPORT
         */

        /**
         * Start getBudgetsForMonth DONE
         */
        $budgets = $this->helper->getBudgetsForMonth($date, $shared);

        /**
         * End getBudgetsForMonth DONE
         */
        /**
         * Start getCategoriesForMonth DONE
         */
        // all categories.
        $result     = $this->query->journalsByCategory($start, $end);
        $categories = Steam::makeArray($result);


        // all transfers
        if ($shared === false) {
            $result    = $this->query->sharedExpensesByCategory($start, $end);
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


        // clean up and sort expenses:
        $expenses = Steam::makeArray($expensesSet);
        $expenses = Steam::sortArray($expenses);
        $expenses = Steam::limitArray($expenses, 10);

        return view(
            'reports.month',
            compact(
                'income', 'expenses', 'budgets', 'accounts', 'categories', 'shared',
                'date', 'subTitle', 'displaySum', 'subTitleIcon'
            )
        );
    }

    /**
     * @param $year
     *
     * @return $this
     */
    public function year($year, $shared = false)
    {
        $date             = new Carbon('01-01-' . $year);
        $end              = clone $date;
        $subTitle         = trans('firefly.reportForYear', ['year' => $year]);
        $subTitleIcon     = 'fa-bar-chart';
        $totalExpense     = 0;
        $totalIncome      = 0;
        $incomeTopLength  = 5;
        $expenseTopLength = 10;

        if ($shared == 'shared') {
            $shared   = true;
            $subTitle = trans('firefly.reportForYearShared', ['year' => $year]);
        }
        $end->endOfYear();

        /**
         * ALL ACCOUNTS
         * Summarized as well.
         */
        $accounts     = $this->query->getAllAccounts($date, $end, $shared);
        $accountsSums = ['start' => 0, 'end' => 0, 'diff' => 0];
        // summarize:
        foreach ($accounts as $account) {
            $accountsSums['start'] += $account->startBalance;
            $accountsSums['end'] += $account->endBalance;
            $accountsSums['diff'] += ($account->endBalance - $account->startBalance);
        }


        /**
         * ALL INCOMES.
         * Grouped, sorted and summarized.
         */
        $set = $this->query->incomeInPeriod($date, $end, $shared);
        // group, sort and sum:
        $incomes = [];
        foreach ($set as $entry) {
            $id = $entry->account_id;
            $totalIncome += floatval($entry->queryAmount);
            if (isset($incomes[$id])) {
                $incomes[$id]['amount'] += floatval($entry->queryAmount);
                $incomes[$id]['count']++;
            } else {
                $incomes[$id] = [
                    'amount' => floatval($entry->queryAmount),
                    'name'   => $entry->name,
                    'count'  => 1,
                    'id'     => $id,
                ];
            }
        }
        // sort with callback:
        uasort(
            $incomes, function ($a, $b) {
            if ($a['amount'] == $b['amount']) {
                return 0;
            }

            return ($a['amount'] < $b['amount']) ? 1 : -1;
        }
        );
        unset($set, $id);

        /**
         * GET ALL EXPENSES
         * Summarized.
         */
        $expenses = $this->query->journalsByExpenseAccount($date, $end, $shared);
        foreach ($expenses as $expense) {
            $totalExpense += floatval($expense->queryAmount);
        }

        return view(
            'reports.year',
            compact(
                'date', // the date for this report.
                'shared', // is a shared report?
                'totalExpense', 'totalIncome', // total income and expense.
                'accounts', // all accounts
                'accountsSums', // sums for all accounts
                'incomes', 'expenses', // expenses and incomes.
                'subTitle', 'subTitleIcon', // subtitle and subtitle icon.
                'incomeTopLength', // length of income top X
                'expenseTopLength' // length of expense top X.
            )
        );
    }


}

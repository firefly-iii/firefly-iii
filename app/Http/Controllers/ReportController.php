<?php namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use Exception;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionJournal;
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
        $this->query  = $query;
        $this->helper = $helper;

        View::share('title', 'Reports');
        View::share('mainTitleIcon', 'fa-line-chart');

    }

    /**
     * @param string $year
     * @param string $month
     *
     * @return \Illuminate\View\View
     */
    public function budget($year = '2014', $month = '1')
    {
        $date         = new Carbon($year . '-' . $month . '-01');
        $subTitle     = 'Budget report for ' . $date->format('F Y');
        $subTitleIcon = 'fa-calendar';
        $start        = clone $date;


        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();

        // should show shared reports?
        /** @var Preference $pref */
        $pref              = Preferences::get('showSharedReports', false);
        $showSharedReports = $pref->data;
        $accountAmounts    = []; // array with sums of spent amounts on each account.
        $accounts          = $this->query->getAllAccounts($start, $end, $showSharedReports); // all accounts and some data.

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
         * Start getBudgetsForMonth DONE
         */
        $budgets = $this->helper->getBudgetsForMonth($date, $showSharedReports);

        /**
         * End getBudgetsForMonth DONE
         */

        return view('reports.budget', compact('subTitle', 'accountAmounts', 'year', 'month', 'subTitleIcon', 'date', 'accounts', 'budgets'));

    }

    /**
     * @param ReportHelperInterface $helper
     *
     * @return View
     */
    public function index()
    {
        $start         = Session::get('first');
        $months        = $this->helper->listOfMonths($start);
        $years         = $this->helper->listOfYears($start);
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
    public function modalBalancedTransfers(Account $account, $year = '2014', $month = '1')
    {

        $start = new Carbon($year . '-' . $month . '-01');
        $end   = clone $start;
        $end->endOfMonth();

        $journals = $this->query->balancedTransactionsList($account, $start, $end);

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
    public function month($year = '2014', $month = '1')
    {
        $date         = new Carbon($year . '-' . $month . '-01');
        $subTitle     = 'Report for ' . $date->format('F Y');
        $subTitleIcon = 'fa-calendar';
        $displaySum   = true; // to show sums in report.
        /** @var Preference $pref */
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
        $income = $this->query->incomeByPeriod($start, $end, $showSharedReports);
        /**
         * End getIncomeForMonth DONE
         */
        /**
         * Start getExpenseGroupedForMonth DONE
         */
        $set = $this->query->journalsByExpenseAccount($start, $end, $showSharedReports);

        $expenses = Steam::makeArray($set);
        $expenses = Steam::sortArray($expenses);
        $expenses = Steam::limitArray($expenses, 10);
        /**
         * End getExpenseGroupedForMonth DONE
         */
        /**
         * Start getBudgetsForMonth DONE
         */
        $budgets = $this->helper->getBudgetsForMonth($date, $showSharedReports);

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
        if ($showSharedReports === false) {
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
        /**
         * Start getAccountsForMonth
         */
        $list     = $this->query->accountList($showSharedReports);
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
    public function year($year)
    {
        /** @var Preference $pref */
        $pref              = Preferences::get('showSharedReports', false);
        $showSharedReports = $pref->data;
        $date              = new Carbon('01-01-' . $year);
        $end               = clone $date;
        $end->endOfYear();
        $title           = 'Reports';
        $subTitle        = $year;
        $subTitleIcon    = 'fa-bar-chart';
        $mainTitleIcon   = 'fa-line-chart';
        $balances        = $this->helper->yearBalanceReport($date, $showSharedReports);
        $groupedIncomes  = $this->query->journalsByRevenueAccount($date, $end, $showSharedReports);
        $groupedExpenses = $this->query->journalsByExpenseAccount($date, $end, $showSharedReports);

        return view(
            'reports.year', compact('date', 'groupedIncomes', 'groupedExpenses', 'year', 'balances', 'title', 'subTitle', 'subTitleIcon', 'mainTitleIcon')
        );
    }


}

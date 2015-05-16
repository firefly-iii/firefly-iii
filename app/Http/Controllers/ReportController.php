<?php namespace FireflyIII\Http\Controllers;

use App;
use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;
use Session;
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
        $start  = Session::get('first');
        $months = $this->helper->listOfMonths($start);

        // does the user have shared accounts?
        $accounts  = $repository->getAccounts(['Default account', 'Asset account']);
        $hasShared = false;

        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->getMeta('accountRole') == 'sharedAsset') {
                $hasShared = true;
            }
        }


        return view('reports.index', compact('months', 'hasShared'));
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
        $start            = new Carbon($year . '-' . $month . '-01');
        $subTitle         = trans('firefly.reportForMonth', ['date' => $start->formatLocalized($this->monthFormat)]);
        $subTitleIcon     = 'fa-calendar';
        $end              = clone $start;
        $totalExpense     = 0;
        $totalIncome      = 0;
        $incomeTopLength  = 8;
        $expenseTopLength = 8;
        if ($shared == 'shared') {
            $shared   = true;
            $subTitle = trans('firefly.reportForMonthShared', ['date' => $start->formatLocalized($this->monthFormat)]);
        }

        $end->endOfMonth();

        // all accounts:
        $accounts = $this->helper->getAccountReport($start, $end, $shared);

        /**
         * ALL INCOMES.
         * Grouped, sorted and summarized.
         */
        $set = $this->query->incomeInPeriod($start, $end, $shared);
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
        $set = $this->query->expenseInPeriod($start, $end, $shared);
        // group, sort and sum:
        $expenses = [];
        foreach ($set as $entry) {
            $id = $entry->account_id;
            $totalExpense += floatval($entry->queryAmount);
            if (isset($expenses[$id])) {
                $expenses[$id]['amount'] += floatval($entry->queryAmount);
                $expenses[$id]['count']++;
            } else {
                $expenses[$id] = [
                    'amount' => floatval($entry->queryAmount),
                    'name'   => $entry->name,
                    'count'  => 1,
                    'id'     => $id,
                ];
            }
        }
        // sort with callback:
        uasort(
            $expenses, function ($a, $b) {
            if ($a['amount'] == $b['amount']) {
                return 0;
            }

            return ($a['amount'] < $b['amount']) ? -1 : 1;
        }
        );
        unset($set, $id);

        /**
         * DO BUDGETS.
         */
        /** @var \FireflyIII\Repositories\Budget\BudgetRepositoryInterface $repository */
        $repository = App::make('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $set        = $repository->getBudgets();
        $budgets    = new Collection;
        $budgetSums = ['budgeted' => 0, 'spent' => 0, 'left' => 0, 'overspent' => 0];
        /** @var Budget $budget */
        foreach ($set as $budget) {
            $repetitions = $repository->getBudgetLimitRepetitions($budget, $start, $end);
            if ($repetitions->count() == 0) {
                $exp = $repository->spentInPeriod($budget, $start, $end, $shared);
                $budgets->push([$budget, null, 0, 0, $exp]);
                $budgetSums['overspent'] += $exp;
                continue;
            }
            /** @var LimitRepetition $repetition */
            foreach ($repetitions as $repetition) {
                $exp       = $repository->spentInPeriod($budget, $repetition->startdate, $repetition->enddate, $shared);
                $left      = $exp < floatval($repetition->amount) ? floatval($repetition->amount) - $exp : 0;
                $spent     = $exp > floatval($repetition->amount) ? 0 : $exp;
                $overspent = $exp > floatval($repetition->amount) ? $exp - floatval($repetition->amount) : 0;

                $budgetSums['budgeted'] += floatval($repetition->amount);
                $budgetSums['spent'] += $spent;
                $budgetSums['left'] += $left;
                $budgetSums['overspent'] += $overspent;

                $budgets->push([$budget, $repetition, $left, $spent, $overspent]);
            }
        }

        $noBudgetExpenses = $repository->getWithoutBudgetSum($start, $end);
        $budgets->push([null, null, 0, 0, $noBudgetExpenses]);
        $budgetSums['overspent'] += $noBudgetExpenses;
        unset($noBudgetExpenses, $repository, $set, $repetition, $repetitions, $exp);

        /**
         * GET CATEGORIES:
         */
        /** @var \FireflyIII\Repositories\Category\CategoryRepositoryInterface $repository */
        $repository  = App::make('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        $set         = $repository->getCategories();
        $categories  = [];
        $categorySum = 0;
        foreach ($set as $category) {
            $spent        = $repository->spentInPeriod($category, $start, $end, $shared);
            $categories[] = [$category, $spent];
            $categorySum += $spent;
        }
        // no category:

        // sort with callback:
        uasort(
            $categories, function ($a, $b) {
            if ($a[1] == $b[1]) {
                return 0;
            }

            return ($a[1] < $b[1]) ? 1 : -1;
        }
        );
        unset($set, $repository, $spent);

        return view(
            'reports.month',
            compact(
                'start', 'shared',
                'subTitle', 'subTitleIcon',
                'accounts', 'accountsSums',
                'incomes', 'totalIncome', 'incomeTopLength',
                'expenses', 'totalExpense', 'expenseTopLength',
                'budgets', 'budgetSums',
                'categories', 'categorySum'
            )
        );


        // get all income and expenses. it's OK.
        //        $income      = $this->query->incomeInPeriod($start, $end, $shared);
        //        $expensesSet = $this->query->journalsByExpenseAccount($start, $end, $shared);
        //
        //        /**
        //         * INCLUDE ORIGINAL BUDGET REPORT HERE:
        //         */
        //        // should show shared reports?
        //        /** @var Preference $pref */
        //        $accountAmounts = []; // array with sums of spent amounts on each account.
        //        $accounts       = $this->query->getAllAccounts($start, $end, $shared); // all accounts and some data.
        //
        //        foreach ($accounts as $account) {
        //
        //            $budgets                      = $this->query->getBudgetSummary($account, $start, $end);// get budget summary for this account:
        //            $balancedAmount               = $this->query->balancedTransactionsSum($account, $start, $end);
        //            $accountAmounts[$account->id] = $balancedAmount;
        //            // balance out the transactions (see transaction groups & tags) ^^
        //
        //            // array with budget information for each account:
        //            $array = [];
        //            // should always hide account
        //            $hide = true;
        //            // loop all budgets
        //            /** @var \FireflyIII\Models\Budget $budget */
        //            foreach ($budgets as $budget) {
        //                $id         = intval($budget->id);
        //                $data       = $budget->toArray();
        //                $array[$id] = $data;
        //
        //                // no longer hide account if any budget has money in it.
        //                if (floatval($data['queryAmount']) != 0) {
        //                    $hide = false;
        //                }
        //                $accountAmounts[$account->id] += $data['queryAmount'];
        //            }
        //            $account->hide              = $hide;
        //            $account->budgetInformation = $array;
        //            $account->balancedAmount    = $balancedAmount;
        //
        //        }
        //        /**
        //         * END ORIGINAL BUDGET REPORT
        //         */
        //
        //        /**
        //         * Start getBudgetsForMonth DONE
        //         */
        //        $budgets = $this->helper->getBudgetsForMonth($date, $shared);
        //
        //        /**
        //         * End getBudgetsForMonth DONE
        //         */
        //        /**
        //         * Start getCategoriesForMonth DONE
        //         */
        //        // all categories.
        //        $result     = $this->query->journalsByCategory($start, $end);
        //        $categories = Steam::makeArray($result);
        //
        //
        //        // all transfers
        //        if ($shared === false) {
        //            $result    = $this->query->sharedExpensesByCategory($start, $end);
        //            $transfers = Steam::makeArray($result);
        //            $merged    = Steam::mergeArrays($categories, $transfers);
        //        } else {
        //            $merged = $categories;
        //        }
        //
        //
        //        // sort.
        //        $sorted = Steam::sortNegativeArray($merged);
        //
        //        // limit to $limit:
        //        $categories = Steam::limitArray($sorted, 10);
        //
        //        /**
        //         * End getCategoriesForMonth DONE
        //         */
        //
        //
        //        // clean up and sort expenses:
        //        $expenses = Steam::makeArray($expensesSet);
        //        $expenses = Steam::sortArray($expenses);
        //        $expenses = Steam::limitArray($expenses, 10);

        //
        //
        //        return view(
        //            'reports.month',
        //            compact(
        //                'income', 'expenses', 'budgets', 'accounts', 'categories', 'shared',
        //                'date', 'subTitle', 'displaySum', 'subTitleIcon'
        //            )
        //        );
    }

    /**
     * @param $year
     *
     * @return $this
     */
    public function year($year, $shared = false)
    {
        $start            = new Carbon('01-01-' . $year);
        $end              = clone $start;
        $subTitle         = trans('firefly.reportForYear', ['year' => $year]);
        $subTitleIcon     = 'fa-bar-chart';
        $incomeTopLength  = 8;
        $expenseTopLength = 8;

        if ($shared == 'shared') {
            $shared   = true;
            $subTitle = trans('firefly.reportForYearShared', ['year' => $year]);
        }
        $end->endOfYear();

        $accounts = $this->helper->getAccountReport($start, $end, $shared);
        $incomes  = $this->helper->getIncomeReport($start, $end, $shared);
        $expenses = $this->helper->getExpenseReport($start, $end, $shared);


        return view(
            'reports.year',
            compact(
                'start', // the date for this report.
                'shared', // is a shared report?
                'accounts', // all accounts
                'incomes', 'expenses', // expenses and incomes.
                'subTitle', 'subTitleIcon', // subtitle and subtitle icon.
                'incomeTopLength', // length of income top X
                'expenseTopLength' // length of expense top X.
            )
        );
    }


}

<?php
use Carbon\Carbon;
use FireflyIII\Database\TransactionJournal\TransactionJournal as TransactionJournalRepository;
use FireflyIII\Report\ReportInterface as Report;

/**
 *
 * Class ReportController
 */
class ReportController extends BaseController
{
    /** @var \FireflyIII\Database\Budget\Budget */
    protected $_budgets;
    /** @var TransactionJournalRepository */
    protected $_journals;
    /** @var Report */
    protected $_repository;

    /**
     * @param TransactionJournalRepository $journals
     * @param Report                       $repository
     */
    public function __construct(TransactionJournalRepository $journals, Report $repository)
    {
        $this->_journals   = $journals;
        $this->_repository = $repository;
        /** @var \FireflyIII\Database\Budget\Budget _budgets */
        $this->_budgets = App::make('FireflyIII\Database\Budget\Budget');


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
        try {
            new Carbon($year . '-' . $month . '-01');
        } catch (Exception $e) {
            View::make('error')->with('message', 'Invalid date');
        }
        $date     = new Carbon($year . '-' . $month . '-01');
        $dayEarly = clone $date;
        $dayEarly = $dayEarly->subDay();
        $accounts = $this->_repository->getAccountListBudgetOverview($date);
        $budgets  = $this->_repository->getBudgetsForMonth($date);

        return View::make('reports.budget', compact('date', 'accounts', 'budgets', 'dayEarly'));

    }

    /**
     *
     */
    public function index()
    {
        $start         = $this->_journals->firstDate();
        $months        = $this->_repository->listOfMonths(clone $start);
        $years         = $this->_repository->listOfYears(clone $start);
        $title         = 'Reports';
        $mainTitleIcon = 'fa-line-chart';

        return View::make('reports.index', compact('years', 'months', 'title', 'mainTitleIcon'));
    }

    /**
     * @param string $year
     * @param string $month
     *
     * @return \Illuminate\View\View
     */
    public function month($year = '2014', $month = '1')
    {
        try {
            new Carbon($year . '-' . $month . '-01');
        } catch (Exception $e) {
            View::make('error')->with('message', 'Invalid date');
        }
        $date         = new Carbon($year . '-' . $month . '-01');
        $subTitle     = 'Report for ' . $date->format('F Y');
        $subTitleIcon = 'fa-calendar';
        $displaySum   = true; // to show sums in report.
        $income       = $this->_repository->getIncomeForMonth($date);
        $expenses     = $this->_repository->getExpenseGroupedForMonth($date, 10);
        $budgets      = $this->_repository->getBudgetsForMonth($date);
        $categories   = $this->_repository->getCategoriesForMonth($date, 10);
        $accounts     = $this->_repository->getAccountsForMonth($date);
        $piggyBanks   = $this->_repository->getPiggyBanksForMonth($date);

        return View::make(
            'reports.month',
            compact('date', 'accounts', 'categories', 'budgets', 'expenses', 'subTitle', 'displaySum', 'subTitleIcon', 'income')
        );
    }

    /**
     * @param $year
     *
     * @return $this
     */
    public function year($year)
    {
        try {
            new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            App::abort(500);
        }
        $date = new Carbon('01-01-' . $year);
        $end  = clone $date;
        $end->endOfYear();
        $title         = 'Reports';
        $subTitle      = $year;
        $subTitleIcon  = 'fa-bar-chart';
        $mainTitleIcon = 'fa-line-chart';

        $balances        = $this->_repository->yearBalanceReport($date);
        $groupedIncomes  = $this->_repository->revenueGroupedByAccount($date, $end, 15);
        $groupedExpenses = $this->_repository->expensesGroupedByAccount($date, $end, 15);

        return View::make(
            'reports.year', compact('date', 'groupedIncomes', 'groupedExpenses', 'year', 'balances', 'title', 'subTitle', 'subTitleIcon', 'mainTitleIcon')
        );
    }

}
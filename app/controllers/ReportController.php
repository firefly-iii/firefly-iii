<?php
use Carbon\Carbon;
use FireflyIII\Database\Account\Account as AccountRepository;
use FireflyIII\Database\TransactionJournal\TransactionJournal as TransactionJournalRepository;
use FireflyIII\Report\ReportInterface as Report;

/**
 *
 * Class ReportController
 */
class ReportController extends BaseController
{
    /** @var AccountRepository */
    protected $_accounts;

    /** @var TransactionJournalRepository */
    protected $_journals;

    /** @var Report */
    protected $_repository;

    /**
     * @param AccountRepository            $accounts
     * @param TransactionJournalRepository $journals
     * @param Report                       $repository
     */
    public function __construct(AccountRepository $accounts, TransactionJournalRepository $journals, Report $repository)
    {
        $this->_accounts   = $accounts;
        $this->_journals   = $journals;
        $this->_repository = $repository;

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
     * @param $year
     * @param $month
     *
     * @return \Illuminate\View\View
     */
    public function unbalanced($year, $month)
    {
        try {
            new Carbon($year . '-' . $month . '-01');
        } catch (Exception $e) {
            App::abort(500);
        }
        $start         = new Carbon($year . '-' . $month . '-01');
        $end           = clone $start;
        $title         = 'Reports';
        $subTitle      = 'Unbalanced transactions in ' . $start->format('F Y');
        $mainTitleIcon = 'fa-line-chart';
        $subTitleIcon  = 'fa-bar-chart';
        $end->endOfMonth();

        /** @var \FireflyIII\Database\TransactionJournal\TransactionJournal $journalRepository */
        $journalRepository = App::make('FireflyIII\Database\TransactionJournal\TransactionJournal');
        $journals          = $journalRepository->getInDateRange($start, $end);

        $withdrawals = $journals->filter(
            function (TransactionJournal $journal) {
                $relations = $journal->transactiongroups()->where('relation', 'balance')->count();
                $budgets   = $journal->budgets()->count();
                $type      = $journal->transactionType->type;
                if ($type == 'Withdrawal' && $budgets == 0 && $relations == 0) {
                    return $journal;
                }

                return null;
            }
        );
        $deposits = $journals->filter(
            function (TransactionJournal $journal) {
                $relations = $journal->transactiongroups()->where('relation', 'balance')->count();
                $budgets   = $journal->budgets()->count();
                $type      = $journal->transactionType->type;
                if ($type == 'Deposit' && $budgets == 0 && $relations == 0) {
                    return $journal;
                }

                return null;
            }
        );

        $journals = $withdrawals->merge($deposits);

        return View::make('reports.unbalanced', compact('start', 'end', 'title', 'subTitle', 'subTitleIcon', 'mainTitleIcon', 'journals'));
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
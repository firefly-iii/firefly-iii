<?php
use Carbon\Carbon;
use FireflyIII\Database\Account as AccountRepository;
use FireflyIII\Database\Report as ReportRepository;
use FireflyIII\Database\TransactionJournal as TransactionJournalRepository;
use FireflyIII\Report\ReportInterface as ReportHelper;

/**
 * Class ReportController
 */
class ReportController extends BaseController
{
    /** @var AccountRepository */
    protected $_accounts;

    /** @var TransactionJournalRepository */
    protected $_journals;

    /** @var ReportHelper */
    protected $_reports;

    /** @var ReportRepository */
    protected $_repository;

    /**
     * @param AccountRepository            $accounts
     * @param TransactionJournalRepository $journals
     * @param ReportHelper                 $reports
     * @param ReportRepository             $repository
     */
    public function __construct(AccountRepository $accounts, TransactionJournalRepository $journals, ReportHelper $reports, ReportRepository $repository)
    {
        $this->_accounts   = $accounts;
        $this->_journals   = $journals;
        $this->_reports    = $reports;
        $this->_repository = $repository;

    }

    /**
     * @param $year
     * @param $month
     *
     * @return \Illuminate\View\View
     */
    public function budgets($year, $month)
    {
        try {
            $start = new Carbon($year . '-' . $month . '-01');
        } catch (Exception $e) {
            App::abort(500);
        }
        $end           = clone $start;
        $title         = 'Reports';
        $subTitle      = 'Budgets in ' . $start->format('F Y');
        $mainTitleIcon = 'fa-line-chart';
        $subTitleIcon  = 'fa-bar-chart';

        $end->endOfMonth();


        // get a list of all budgets and expenses.
        /** @var \FireflyIII\Database\Budget $budgetRepository */
        $budgetRepository = App::make('FireflyIII\Database\Budget');

        /** @var \FireflyIII\Database\Account $accountRepository */
        $accountRepository = App::make('FireflyIII\Database\Account');


        $budgets = $budgetRepository->get();

        // calculate some stuff:
        $budgets->each(
            function (Budget $budget) use ($start, $end, $budgetRepository) {
                $limitRepetitions = $budget->limitrepetitions()->where('limit_repetitions.startdate', '>=', $start->format('Y-m-d'))->where(
                    'enddate', '<=', $end->format(
                        'Y-m-d'
                    )
                )->get();
                $repInfo          = [];
                /** @var LimitRepetition $repetition */
                foreach ($limitRepetitions as $repetition) {
                    $spent = $budgetRepository->spentInPeriod($budget, $start, $end);
                    if ($spent > floatval($repetition->amount)) {
                        // overspent!
                        $overspent = true;
                        $pct       = floatval($repetition->amount) / $spent * 100;

                    } else {
                        $overspent = false;
                        $pct       = $spent / floatval($repetition->amount) * 100;
                    }
                    $pctDisplay = $spent / floatval($repetition->amount) * 100;
                    $repInfo[]  = [
                        'date'        => DateKit::periodShow($repetition->startdate, $repetition->limit->repeat_freq),
                        'spent'       => $spent,
                        'budgeted'    => floatval($repetition->amount),
                        'left'        => floatval($repetition->amount) - $spent,
                        'pct'         => ceil($pct),
                        'pct_display' => ceil($pctDisplay),
                        'overspent'   => $overspent,
                    ];
                }
                $budget->repInfo = $repInfo;

            }
        );

        $accounts = $accountRepository->getAssetAccounts();

        $accounts->each(
            function (Account $account) use ($start, $end, $accountRepository) {
                $journals = $accountRepository->getTransactionJournalsInRange($account, $start, $end);
                $budgets  = [];
                /** @var TransactionJournal $journal */
                foreach ($journals as $journal) {
                    $budgetId   = isset($journal->budgets[0]) ? $journal->budgets[0]->id : 0;
                    $budgetName = isset($journal->budgets[0]) ? $journal->budgets[0]->name : '(no budget)';
                    if (!isset($budgets[$budgetId])) {
                        $arr                = [
                            'budget_id'   => $budgetId,
                            'budget_name' => $budgetName,
                            'spent'       => floatval($journal->getAmount()),
                            'budgeted'    => 0,
                        ];
                        $budgets[$budgetId] = $arr;
                    } else {
                        $budgets[$budgetId]['spent'] += floatval($journal->getAmount());
                    }
                }
                foreach ($budgets as $budgetId => $budget) {
                    $budgets[$budgetId]['left'] = $budget['budgeted'] - $budget['spent'];
                }
                $account->budgetInfo = $budgets;
            }
        );


        return View::make('reports.budgets', compact('start', 'end', 'title', 'subTitle', 'subTitleIcon', 'mainTitleIcon', 'budgets', 'accounts'));

    }

    /**
     *
     */
    public function index()
    {
        $start         = $this->_journals->firstDate();
        $months        = $this->_reports->listOfMonths(clone $start);
        $years         = $this->_reports->listOfYears(clone $start);
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
            $date = new Carbon($year . '-' . $month . '-01');
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

        /** @var \FireflyIII\Database\TransactionJournal $journalRepository */
        $journalRepository = App::make('FireflyIII\Database\TransactionJournal');

        /*
         * Get all journals from this month:
         */
        $journals = $journalRepository->getInDateRange($start, $end);

        /*
         * Filter withdrawals:
         */
        $withdrawals = $journals->filter(
            function (TransactionJournal $journal) {
                if ($journal->transactionType->type == 'Withdrawal' && count($journal->budgets) == 0) {

                    // count groups related to balance.
                    if ($journal->transactiongroups()->where('relation', 'balance')->count() == 0) {
                        return $journal;
                    }
                }

                return null;
            }
        );
        /*
         * Filter deposits.
         */
        $deposits = $journals->filter(
            function (TransactionJournal $journal) {
                if ($journal->transactionType->type == 'Deposit' && count($journal->budgets) == 0) {
                    // count groups related to balance.
                    if ($journal->transactiongroups()->where('relation', 'balance')->count() == 0) {
                        return $journal;
                    }
                }

                return null;
            }
        );


        /*
         * Filter transfers (not yet used)
         */
        //        $transfers = $journals->filter(
        //            function (TransactionJournal $journal) {
        //                if ($journal->transactionType->type == 'Transfer') {
        //                    return $journal;
        //                }
        //            }
        //        );

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

        $balances        = $this->_reports->yearBalanceReport($date);
        $groupedIncomes  = $this->_reports->revenueGroupedByAccount($date, $end, 15);
        $groupedExpenses = $this->_reports->expensesGroupedByAccount($date, $end, 15);

        return View::make(
            'reports.year', compact('date', 'groupedIncomes', 'groupedExpenses', 'year', 'balances', 'title', 'subTitle', 'subTitleIcon', 'mainTitleIcon')
        );
    }

}
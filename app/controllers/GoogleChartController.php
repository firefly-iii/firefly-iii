<?php
use Carbon\Carbon;
use FireflyIII\Chart\ChartInterface;
use Grumpydictator\Gchart\GChart as GChart;

/**
 * Class GoogleChartController
 * @SuppressWarnings("CamelCase") // I'm fine with this.
 */
class GoogleChartController extends BaseController
{

    /** @var GChart */
    protected $_chart;
    /** @var  Carbon */
    protected $_end;
    /** @var ChartInterface */
    protected $_repository;
    /** @var  Carbon */
    protected $_start;

    /**
     * @param GChart         $chart
     * @param ChartInterface $repository
     */
    public function __construct(GChart $chart, ChartInterface $repository)
    {
        $this->_chart      = $chart;
        $this->_repository = $repository;
        $this->_start      = Session::get('start', Carbon::now()->startOfMonth());
        $this->_end        = Session::get('end', Carbon::now()->endOfMonth());

    }

    /**
     * @param Account $account
     * @param string  $view
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accountBalanceChart(Account $account, $view = 'session')
    {
        $this->_chart->addColumn('Day of month', 'date');
        $this->_chart->addColumn('Balance for ' . $account->name, 'number');

        $start = $this->_start;
        $end   = $this->_end;
        $count = $account->transactions()->count();

        if ($view == 'all' && $count > 0) {
            $first = $account->transactions()->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')->orderBy(
                'date', 'ASC'
            )->first(['transaction_journals.date']);
            $last  = $account->transactions()->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')->orderBy(
                'date', 'DESC'
            )->first(['transaction_journals.date']);
            $start = new Carbon($first->date);
            $end   = new Carbon($last->date);
        }

        $current = clone $start;

        while ($end >= $current) {
            $this->_chart->addRow(clone $current, Steam::balance($account, $current));
            $current->addDay();
        }


        $this->_chart->generate();

        return Response::json($this->_chart->getData());
    }

    /**
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     */
    public function allAccountsBalanceChart()
    {
        $this->_chart->addColumn('Day of the month', 'date');

        /** @var \FireflyIII\Shared\Preferences\Preferences $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\Preferences');
        $pref        = $preferences->get('frontPageAccounts', []);

        /** @var \FireflyIII\Database\Account\Account $acct */
        $acct     = App::make('FireflyIII\Database\Account\Account');
        $accounts = count($pref->data) > 0 ? $acct->getByIds($pref->data) : $acct->getAccountsByType(['Default account', 'Asset account']);

        /** @var Account $account */
        foreach ($accounts as $account) {
            $this->_chart->addColumn('Balance for ' . $account->name, 'number');
        }
        $current = clone $this->_start;
        $current->subDay();

        while ($this->_end >= $current) {
            $row = [clone $current];
            foreach ($accounts as $account) {
                $row[] = Steam::balance($account, $current);
            }
            $this->_chart->addRowArray($row);
            $current->addDay();
        }

        $this->_chart->generate();

        return Response::json($this->_chart->getData());

    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function allBudgetsHomeChart()
    {
        $this->_chart->addColumn('Budget', 'string');
        $this->_chart->addColumn('Budgeted', 'number');
        $this->_chart->addColumn('Spent', 'number');

        /** @var \FireflyIII\Database\Budget\Budget $bdt */
        $bdt     = App::make('FireflyIII\Database\Budget\Budget');
        $budgets = $bdt->get();

        /** @var Budget $budget */
        foreach ($budgets as $budget) {

            /** @var \LimitRepetition $repetition */
            $repetition = $bdt->repetitionOnStartingOnDate($budget, $this->_start);
            if (is_null($repetition)) { // use the session start and end for our search query
                $searchStart = $this->_start;
                $searchEnd   = $this->_end;
                $limit       = 0; // the limit is zero:
            } else {
                // use the limit's start and end for our search query
                $searchStart = $repetition->startdate;
                $searchEnd   = $repetition->enddate;
                $limit       = floatval($repetition->amount); // the limit is the repetitions limit:
            }

            $expenses = floatval($budget->transactionjournals()->before($searchEnd)->after($searchStart)->lessThan(0)->sum('amount')) * -1;
            if ($expenses > 0) {
                $this->_chart->addRow($budget->name, $limit, $expenses);
            }
        }

        $noBudgetSet = $bdt->expenseNoBudget($this->_start, $this->_end);
        $sum         = $noBudgetSet->sum('amount') * -1;
        $this->_chart->addRow('No budget', 0, $sum);
        $this->_chart->generate();

        return Response::json($this->_chart->getData());
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function allCategoriesHomeChart()
    {
        $this->_chart->addColumn('Category', 'string');
        $this->_chart->addColumn('Spent', 'number');

        // query!
        $set = $this->_repository->getCategorySummary($this->_start, $this->_end);

        foreach ($set as $entry) {
            $entry->name = strlen($entry->name) == 0 ? '(no category)' : $entry->name;
            $this->_chart->addRow($entry->name, floatval($entry->sum));
        }

        $this->_chart->generate();

        return Response::json($this->_chart->getData());

    }

    /**
     * @param Bill $bill
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function billOverview(Bill $bill)
    {

        $this->_chart->addColumn('Date', 'date');
        $this->_chart->addColumn('Max amount', 'number');
        $this->_chart->addColumn('Min amount', 'number');
        $this->_chart->addColumn('Current entry', 'number');

        // get first transaction or today for start:
        $first = $bill->transactionjournals()->orderBy('date', 'ASC')->first();
        if ($first) {
            $start = $first->date;
        } else {
            $start = new Carbon;
        }
        $end = new Carbon;
        while ($start <= $end) {
            $result = $bill->transactionjournals()->before($end)->after($start)->first();
            if ($result) {
                $amount = $result->getAmount();
            } else {
                $amount = 0;
            }
            unset($result);
            $this->_chart->addRow(clone $start, $bill->amount_max, $bill->amount_min, $amount);
            $start = DateKit::addPeriod($start, $bill->repeat_freq, 0);
        }

        $this->_chart->generate();

        return Response::json($this->_chart->getData());

    }

    /**
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \FireflyIII\Exception\FireflyException
     */
    public function billsOverview()
    {
        $paid   = ['items' => [], 'amount' => 0];
        $unpaid = ['items' => [], 'amount' => 0];
        $this->_chart->addColumn('Name', 'string');
        $this->_chart->addColumn('Amount', 'number');

        $set = $this->_repository->getBillsSummary($this->_start, $this->_end);

        foreach ($set as $entry) {
            if (intval($entry->journalId) == 0) {
                $unpaid['items'][] = $entry->name;
                $unpaid['amount'] += floatval($entry->averageAmount);
            } else {
                $paid['items'][] = $entry->description;
                $paid['amount'] += floatval($entry->actualAmount);
            }
        }
        $this->_chart->addRow('Unpaid: ' . join(', ', $unpaid['items']), $unpaid['amount']);
        $this->_chart->addRow('Paid: ' . join(', ', $paid['items']), $paid['amount']);
        $this->_chart->generate();

        return Response::json($this->_chart->getData());
    }

    /**
     *
     * @param Budget          $budget
     * @param LimitRepetition $repetition
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetLimitSpending(\Budget $budget, \LimitRepetition $repetition)
    {
        $start = clone $repetition->startdate;
        $end   = $repetition->enddate;

        $this->_chart->addColumn('Day', 'date');
        $this->_chart->addColumn('Left', 'number');


        $amount = $repetition->amount;

        while ($start <= $end) {
            /*
             * Sum of expenses on this day:
             */
            $sum = floatval($budget->transactionjournals()->lessThan(0)->transactionTypes(['Withdrawal'])->onDate($start)->sum('amount'));
            $amount += $sum;
            $this->_chart->addRow(clone $start, $amount);
            $start->addDay();
        }
        $this->_chart->generate();

        return Response::json($this->_chart->getData());

    }

    /**
     *
     * @param Budget $budget
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetsAndSpending(Budget $budget, $year = 0)
    {
        /** @var \FireflyIII\Database\Budget\Budget $budgetRepository */
        $budgetRepository = App::make('FireflyIII\Database\Budget\Budget');

        $this->_chart->addColumn('Month', 'date');
        $this->_chart->addColumn('Budgeted', 'number');
        $this->_chart->addColumn('Spent', 'number');
        if ($year == 0) {
            // grab the first budgetlimit ever:
            $firstLimit = $budget->budgetlimits()->orderBy('startdate', 'ASC')->first();
            if ($firstLimit) {
                $start = new Carbon($firstLimit->startdate);
            } else {
                $start = Carbon::now()->startOfYear();
            }

            // grab the last budget limit ever:
            $lastLimit = $budget->budgetlimits()->orderBy('startdate', 'DESC')->first();
            if ($lastLimit) {
                $end = new Carbon($lastLimit->startdate);
            } else {
                $end = Carbon::now()->endOfYear();
            }
        } else {
            $start = Carbon::createFromDate(intval($year), 1, 1);
            $end   = clone $start;
            $end->endOfYear();
        }


        while ($start <= $end) {
            $spent      = $budgetRepository->spentInMonth($budget, $start);
            $repetition = $budgetRepository->repetitionOnStartingOnDate($budget, $start);

            if ($repetition) {
                $budgeted = floatval($repetition->amount);
                \Log::debug('Found a repetition on ' . $start->format('Y-m-d') . ' for budget ' . $budget->name . '!');
            } else {
                \Log::debug('No repetition on ' . $start->format('Y-m-d') . ' for budget ' . $budget->name);
                $budgeted = null;
            }
            $this->_chart->addRow(clone $start, $budgeted, $spent);
            $start->addMonth();
        }

        $this->_chart->generate();

        return Response::json($this->_chart->getData());


    }

    /**
     *
     * @param Category  $component
     * @param           $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categoriesAndSpending(Category $component, $year)
    {
        try {
            new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            return View::make('error')->with('message', 'Invalid year.');
        }

        /** @var \FireflyIII\Database\Category\Category $categoryRepository */
        $categoryRepository = App::make('FireflyIII\Database\Category\Category');

        $this->_chart->addColumn('Month', 'date');
        $this->_chart->addColumn('Budgeted', 'number');
        $this->_chart->addColumn('Spent', 'number');

        $start = new Carbon('01-01-' . $year);
        $end   = clone $start;
        $end->endOfYear();
        while ($start <= $end) {

            $spent    = $categoryRepository->spentInMonth($component, $start);
            $budgeted = null;

            $this->_chart->addRow(clone $start, $budgeted, $spent);

            $start->addMonth();
        }


        $this->_chart->generate();

        return Response::json($this->_chart->getData());


    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function piggyBankHistory(\PiggyBank $piggyBank)
    {
        $this->_chart->addColumn('Date', 'date');
        $this->_chart->addColumn('Balance', 'number');

        $set = \DB::table('piggy_bank_events')->where('piggy_bank_id', $piggyBank->id)->groupBy('date')->get(['date', DB::Raw('SUM(`amount`) AS `sum`')]);

        foreach ($set as $entry) {
            $this->_chart->addRow(new Carbon($entry->date), floatval($entry->sum));
        }

        $this->_chart->generate();

        return Response::json($this->_chart->getData());

    }

    /**
     *
     * @param $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function yearInExp($year)
    {
        try {
            $start = new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            return View::make('error')->with('message', 'Invalid year.');
        }
        $this->_chart->addColumn('Month', 'date');
        $this->_chart->addColumn('Income', 'number');
        $this->_chart->addColumn('Expenses', 'number');

        /** @var \FireflyIII\Database\TransactionJournal\TransactionJournal $repository */
        $repository = App::make('FireflyIII\Database\TransactionJournal\TransactionJournal');

        $end = clone $start;
        $end->endOfYear();
        while ($start < $end) {

            // total income:
            $income  = $repository->getSumOfIncomesByMonth($start);
            $expense = $repository->getSumOfExpensesByMonth($start);

            $this->_chart->addRow(clone $start, $income, $expense);
            $start->addMonth();
        }


        $this->_chart->generate();

        return Response::json($this->_chart->getData());

    }

    /**
     *
     * @param $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function yearInExpSum($year)
    {
        try {
            $start = new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            return View::make('error')->with('message', 'Invalid year.');
        }
        $this->_chart->addColumn('Summary', 'string');
        $this->_chart->addColumn('Income', 'number');
        $this->_chart->addColumn('Expenses', 'number');

        /** @var \FireflyIII\Database\TransactionJournal\TransactionJournal $repository */
        $repository = App::make('FireflyIII\Database\TransactionJournal\TransactionJournal');

        $end = clone $start;
        $end->endOfYear();
        $income  = 0;
        $expense = 0;
        $count   = 0;
        while ($start < $end) {

            // total income:
            $income += $repository->getSumOfIncomesByMonth($start);
            $expense += $repository->getSumOfExpensesByMonth($start);
            $count++;

            $start->addMonth();
        }
        $this->_chart->addRow('Sum', $income, $expense);
        $count = $count > 0 ? $count : 1;
        $this->_chart->addRow('Average', ($income / $count), ($expense / $count));


        $this->_chart->generate();

        return Response::json($this->_chart->getData());

    }
} 

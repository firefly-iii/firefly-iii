<?php
use Carbon\Carbon;
use Grumpydictator\Gchart\GChart as GChart;
use Illuminate\Database\Query\JoinClause;

/**
 * Class GoogleChartController
 * @SuppressWarnings("CamelCase") // I'm fine with this.
 * @SuppressWarnings("TooManyMethods") // I'm also fine with this.
 * @SuppressWarnings("CyclomaticComplexity") // It's all 5. So ok.
 * @SuppressWarnings("MethodLength") // There is one with 45 lines and im gonna move it.
 * @SuppressWarnings("CouplingBetweenObjects") // There's only so much I can remove.
 */
class GoogleChartController extends BaseController
{

    /** @var GChart */
    protected $_chart;

    /**
     * @param GChart $chart
     */
    public function __construct(GChart $chart)
    {
        $this->_chart = $chart;

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

        // TODO this can be combined in some method, it's coming up quite often, is it?
        $start = Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end');
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
        // todo until this part.

        $current = clone $start;

        while ($end >= $current) {
            $this->_chart->addRow(clone $current, Steam::balance($account, $current));
            $current->addDay();
        }


        $this->_chart->generate();

        return Response::json($this->_chart->getData());
    }

    /**
     * This method renders the b
     */
    public function allAccountsBalanceChart()
    {
        $this->_chart->addColumn('Day of the month', 'date');

        /** @var \FireflyIII\Shared\Preferences\Preferences $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\Preferences');
        $pref        = $preferences->get('frontpageAccounts', []);

        /** @var \FireflyIII\Database\Account\Account $acct */
        $acct = App::make('FireflyIII\Database\Account\Account');
        if (count($pref->data) > 0) {
            $accounts = $acct->getByIds($pref->data);
        } else {
            $accounts = $acct->getAssetAccounts();
        }

        /** @var Account $account */
        foreach ($accounts as $account) {
            $this->_chart->addColumn('Balance for ' . $account->name, 'number');
        }
        $start   = Session::get('start', Carbon::now()->startOfMonth());
        $end     = Session::get('end', Carbon::now()->endOfMonth());
        $current = clone $start;

        while ($end >= $current) {
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
            $repetition = $bdt->repetitionOnStartingOnDate($budget, Session::get('start', Carbon::now()->startOfMonth()));
            if (is_null($repetition)) {
                // use the session start and end for our search query
                $searchStart = Session::get('start', Carbon::now()->startOfMonth());
                $searchEnd   = Session::get('end');
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

        $noBudgetSet = $bdt->transactionsWithoutBudgetInDateRange(Session::get('start', Carbon::now()->startOfMonth()), Session::get('end'));
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
        // TODO move to some helper.
        $set = \TransactionJournal::leftJoin(
            'transactions',
            function (JoinClause $join) {
                $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('amount', '>', 0);
            }
        )
                                  ->leftJoin(
                                      'category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id'
                                  )
                                  ->leftJoin('categories', 'categories.id', '=', 'category_transaction_journal.category_id')
                                  ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                  ->before(Session::get('end', Carbon::now()->endOfMonth()))
                                  ->after(Session::get('start', Carbon::now()->startOfMonth()))
                                  ->where('transaction_types.type', 'Withdrawal')
                                  ->groupBy('categories.id')
                                  ->orderBy('sum', 'DESC')
                                  ->get(['categories.id', 'categories.name', DB::Raw('SUM(`transactions`.`amount`) AS `sum`')]);
        foreach ($set as $entry) {
            $entry->name = strlen($entry->name) == 0 ? '(no category)' : $entry->name;
            $this->_chart->addRow($entry->name, floatval($entry->sum));
        }

        $this->_chart->generate();

        return Response::json($this->_chart->getData());

    }

    /**
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
     * @param Budget    $component
     * @param           $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetsAndSpending(Budget $component, $year)
    {
        try {
            $start = new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            App::abort(500);
        }

        /** @var \FireflyIII\Database\Budget\Budget $repos */
        $repos = App::make('FireflyIII\Database\Budget\Budget');

        $this->_chart->addColumn('Month', 'date');
        $this->_chart->addColumn('Budgeted', 'number');
        $this->_chart->addColumn('Spent', 'number');

        $end = clone $start;
        $end->endOfYear();
        while ($start <= $end) {
            $spent      = $repos->spentInMonth($component, $start);
            $repetition = $repos->repetitionOnStartingOnDate($component, $start);
            if ($repetition) {
                $budgeted = floatval($repetition->amount);
            } else {
                $budgeted = null;
            }

            $this->_chart->addRow(clone $start, $budgeted, $spent);

            $start->addMonth();
        }


        $this->_chart->generate();

        return Response::json($this->_chart->getData());


    }

    /**
     * @param Category  $component
     * @param           $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categoriesAndSpending(Category $component, $year)
    {
        try {
            $start = new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            App::abort(500);
        }

        /** @var \FireflyIII\Database\Category\Category $repos */
        $repos = App::make('FireflyIII\Database\Category\Category');

        $this->_chart->addColumn('Month', 'date');
        $this->_chart->addColumn('Budgeted', 'number');
        $this->_chart->addColumn('Spent', 'number');

        $end = clone $start;
        $end->endOfYear();
        while ($start <= $end) {

            $spent    = $repos->spentInMonth($component, $start);
            $budgeted = null;

            $this->_chart->addRow(clone $start, $budgeted, $spent);

            $start->addMonth();
        }


        $this->_chart->generate();

        return Response::json($this->_chart->getData());


    }

    /**
     * @param Piggybank $piggybank
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function piggyBankHistory(\Piggybank $piggybank)
    {
        $this->_chart->addColumn('Date', 'date');
        $this->_chart->addColumn('Balance', 'number');

        $set = \DB::table('piggy_bank_events')->where('piggybank_id', $piggybank->id)->groupBy('date')->get(['date', DB::Raw('SUM(`amount`) AS `sum`')]);

        foreach ($set as $entry) {
            $this->_chart->addRow(new Carbon($entry->date), floatval($entry->sum));
        }

        $this->_chart->generate();

        return Response::json($this->_chart->getData());

    }

    /**
     * @param RecurringTransaction $recurring
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recurringOverview(RecurringTransaction $recurring)
    {

        $this->_chart->addColumn('Date', 'date');
        $this->_chart->addColumn('Max amount', 'number');
        $this->_chart->addColumn('Min amount', 'number');
        $this->_chart->addColumn('Current entry', 'number');

        // get first transaction or today for start:
        $first = $recurring->transactionjournals()->orderBy('date', 'ASC')->first();
        if ($first) {
            $start = $first->date;
        } else {
            $start = new Carbon;
        }
        $end = new Carbon;
        while ($start <= $end) {
            $result = $recurring->transactionjournals()->before($end)->after($start)->first();
            if ($result) {
                $amount = $result->getAmount();
            } else {
                $amount = 0;
            }
            unset($result);
            $this->_chart->addRow(clone $start, $recurring->amount_max, $recurring->amount_min, $amount);
            $start = DateKit::addPeriod($start, $recurring->repeat_freq, 0);
        }

        $this->_chart->generate();

        return Response::json($this->_chart->getData());

    }

    /**
     * TODO move to helper.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \FireflyIII\Exception\FireflyException
     */
    public function recurringTransactionsOverview()
    {
        $paid   = ['items' => [], 'amount' => 0];
        $unpaid = ['items' => [], 'amount' => 0];
        $start  = Session::get('start', Carbon::now()->startOfMonth());
        $end    = Session::get('end', Carbon::now()->endOfMonth());
        $this->_chart->addColumn('Name', 'string');
        $this->_chart->addColumn('Amount', 'number');
        $set = \RecurringTransaction::
        leftJoin(
            'transaction_journals', function (JoinClause $join) use ($start, $end) {
            $join->on('recurring_transactions.id', '=', 'transaction_journals.recurring_transaction_id')
                 ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                 ->where('transaction_journals.date', '<=', $end->format('Y-m-d'));
        }
        )
                                    ->leftJoin(
                                        'transactions', function (JoinClause $join) {
                                        $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('transactions.amount', '>', 0);
                                    }
                                    )
                                    ->where('active', 1)
                                    ->groupBy('recurring_transactions.id')
                                    ->get(
                                        ['recurring_transactions.id', 'recurring_transactions.name', 'transaction_journals.description',
                                         'transaction_journals.id as journalId',
                                         DB::Raw('SUM(`recurring_transactions`.`amount_min` + `recurring_transactions`.`amount_max`) / 2 as `averageAmount`'),
                                         'transactions.amount AS actualAmount']
                                    );

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
     * @param $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function yearInExp($year)
    {
        try {
            $start = new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            App::abort(500);
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
     * @param $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function yearInExpSum($year)
    {
        try {
            $start = new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            App::abort(500);
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
<?php
use Carbon\Carbon;
use Grumpydictator\Gchart\GChart as GChart;

/**
 * Class GoogleChartController
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

        $start = Session::get('start');
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

        $current = clone $start;

        while ($end >= $current) {
            $this->_chart->addRow(clone $current, $current > Carbon::now() ? null : Steam::balance($account, $current));
            $current->addDay();
        }


        $this->_chart->generate();

        return Response::json($this->_chart->getData());
    }

    /**
     * @param Account $account
     * @param string  $view
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accountSankeyInChart(Account $account, $view = 'session')
    {
        // collect all relevant entries.
        $set = [];

        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('From', 'string');
        $chart->addColumn('To', 'string', 'domain');
        $chart->addColumn('Weight', 'number');

        switch ($view) {
            default:
            case 'session':
                $start = Session::get('start');
                $end   = Session::get('end');
                break;
            case 'all':
                $first = $account->transactionjournals()->orderBy('date', 'DESC')->first();
                $last  = $account->transactionjournals()->orderBy('date', 'ASC')->first();
                if (is_null($first)) {
                    $start = Session::get('start');
                } else {
                    $start = clone $first->date;
                }
                if (is_null($last)) {
                    $end = Session::get('end');
                } else {
                    $end = clone $last->date;
                }
                break;
        }


        $transactions = $account->transactions()->with(
            ['transactionjournal', 'transactionjournal.transactions' => function ($q) {
                $q->where('amount', '<', 0);
            }, 'transactionjournal.budgets', 'transactionjournal.transactiontype', 'transactionjournal.categories']
        )->before($end)->after($start)->get();

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $amount = floatval($transaction->amount);
            $type   = $transaction->transactionJournal->transactionType->type;

            if ($amount > 0 && $type != 'Transfer') {

                $otherAccount = $transaction->transactionJournal->transactions[0]->account->name;
                $categoryName = isset($transaction->transactionJournal->categories[0]) ? $transaction->transactionJournal->categories[0]->name : '(no cat)';
                $set[]        = [$otherAccount, $categoryName, $amount];
                $set[]        = [$categoryName, $account->name, $amount];
            }
        }
        // loop the set, group everything together:
        $grouped = [];
        foreach ($set as $entry) {
            $key = $entry[0] . $entry[1];
            if (isset($grouped[$key])) {
                $grouped[$key][2] += $entry[2];
            } else {
                $grouped[$key] = $entry;
            }
        }

        // add rows to the chart:
        foreach ($grouped as $entry) {
            $chart->addRow($entry[0], $entry[1], $entry[2]);
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param Account $account
     * @param string  $view
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accountSankeyOutChart(Account $account, $view = 'session')
    {
        // collect all relevant entries.
        $set = [];

        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('From', 'string');
        $chart->addColumn('To', 'string', 'domain');
        $chart->addColumn('Weight', 'number');

        $transactions = $account->transactions()->with(
            ['transactionjournal', 'transactionjournal.transactions', 'transactionjournal.budgets', 'transactionjournal.transactiontype',
             'transactionjournal.categories']
        )->before(Session::get('end'))->after(
            Session::get('start')
        )->get();

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $amount = floatval($transaction->amount);
            $type   = $transaction->transactionJournal->transactionType->type;

            if ($amount < 0 && $type != 'Transfer') {

                // from account to a budget (if present).
                $budgetName = isset($transaction->transactionJournal->budgets[0]) ? $transaction->transactionJournal->budgets[0]->name : '(no budget)';
                $set[]      = [$account->name, $budgetName, $amount * -1];

                // from budget to category.
                $categoryName = isset($transaction->transactionJournal->categories[0]) ? ' ' . $transaction->transactionJournal->categories[0]->name
                    : '(no cat)';
                $set[]        = [$budgetName, $categoryName, $amount * -1];
            }
        }
        // loop the set, group everything together:
        $grouped = [];
        foreach ($set as $entry) {
            $key = $entry[0] . $entry[1];
            if (isset($grouped[$key])) {
                $grouped[$key][2] += $entry[2];
            } else {
                $grouped[$key] = $entry;
            }
        }

        // add rows to the chart:
        foreach ($grouped as $entry) {
            $chart->addRow($entry[0], $entry[1], $entry[2]);
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * This method renders the b
     */
    public function allAccountsBalanceChart()
    {
        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('Day of the month', 'date');

        /** @var \FireflyIII\Shared\Preferences\Preferences $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\Preferences');
        $pref        = $preferences->get('frontpageAccounts', []);

        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');
        if (count($pref->data) > 0) {
            $accounts = $acct->getByIds($pref->data);
        } else {
            $accounts = $acct->getAssetAccounts();
        }


        /*
         * Add a column for each account.
         */
        /** @var Account $account */
        foreach ($accounts as $account) {
            $chart->addColumn('Balance for ' . $account->name, 'number');
        }
        /*
         * Loop the date, then loop the accounts, then add balance.
         */
        $start   = Session::get('start');
        $end     = Session::get('end');
        $current = clone $start;

        while ($end >= $current) {
            $row = [clone $current];

            foreach ($accounts as $account) {
                $row[] = Steam::balance($account, $current);
            }

            $chart->addRowArray($row);
            $current->addDay();
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function allBudgetsHomeChart()
    {
        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('Budget', 'string');
        $chart->addColumn('Budgeted', 'number');
        $chart->addColumn('Spent', 'number');

        /** @var \FireflyIII\Database\Budget $bdt */
        $bdt     = App::make('FireflyIII\Database\Budget');
        $budgets = $bdt->get();

        /*
         * Loop budgets:
         */
        /** @var Budget $budget */
        foreach ($budgets as $budget) {

            /*
             * Is there a repetition starting on this particular date? We can use that.
             */
            /** @var \LimitRepetition $repetition */
            $repetition = $bdt->repetitionOnStartingOnDate($budget, Session::get('start'));

            /*
             * If there is, use it. Otherwise, forget it.
             */
            if (is_null($repetition)) {
                // use the session start and end for our search query
                $searchStart = Session::get('start');
                $searchEnd   = Session::get('end');
                // the limit is zero:
                $limit = 0;

            } else {
                // use the limit's start and end for our search query
                $searchStart = $repetition->startdate;
                $searchEnd   = $repetition->enddate;
                // the limit is the repetitions limit:
                $limit = floatval($repetition->amount);
            }

            /*
             * No matter the result of the search for the repetition, get all the transactions associated
             * with the budget, and sum up the expenses made.
             */
            $expenses = floatval($budget->transactionjournals()->before($searchEnd)->after($searchStart)->lessThan(0)->sum('amount')) * -1;
            if ($expenses > 0) {
                $chart->addRow($budget->name, $limit, $expenses);
            }
        }

        /*
         * Finally, get all transactions WITHOUT a budget and add those as well.
         * (yes this method is oddly specific).
         */
        $noBudgetSet = $bdt->transactionsWithoutBudgetInDateRange(Session::get('start'), Session::get('end'));
        $sum         = $noBudgetSet->sum('amount') * -1;
        $chart->addRow('No budget', 0, $sum);


        $chart->generate();

        return Response::json($chart->getData());
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function allCategoriesHomeChart()
    {
        $data = [];

        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('Category', 'string');
        $chart->addColumn('Spent', 'number');

        /** @var \FireflyIII\Database\TransactionJournal $tj */
        $tj = App::make('FireflyIII\Database\TransactionJournal');

        /*
         * Get the journals:
         */
        $journals = $tj->getInDateRange(Session::get('start'), Session::get('end'));

        /** @var \TransactionJournal $journal */
        foreach ($journals as $journal) {
            if ($journal->transactionType->type == 'Withdrawal') {
                $amount   = $journal->getAmount();
                $category = $journal->categories()->first();
                if (!is_null($category)) {
                    if (isset($data[$category->name])) {
                        $data[$category->name] += $amount;
                    } else {
                        $data[$category->name] = $amount;
                    }
                }
            }
        }
        arsort($data);
        foreach ($data as $key => $entry) {
            $chart->addRow($key, $entry);
        }


        $chart->generate();

        return Response::json($chart->getData());

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

        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('Day', 'date');
        $chart->addColumn('Left', 'number');


        $amount = $repetition->amount;

        while ($start <= $end) {
            /*
             * Sum of expenses on this day:
             */
            $sum = floatval($budget->transactionjournals()->lessThan(0)->transactionTypes(['Withdrawal'])->onDate($start)->sum('amount'));
            $amount += $sum;
            $chart->addRow(clone $start, $amount);
            $start->addDay();
        }
        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetsReportChart($year)
    {

        try {
            $start = new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            App::abort(500);
        }

        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');

        /** @var \FireflyIII\Database\Budget $bdt */
        $bdt     = App::make('FireflyIII\Database\Budget');
        $budgets = $bdt->get();

        $chart->addColumn('Month', 'date');
        /** @var \Budget $budget */
        foreach ($budgets as $budget) {
            $chart->addColumn($budget->name, 'number');
        }
        $chart->addColumn('No budget', 'number');

        /*
         * Loop budgets this year.
         */
        $end = clone $start;
        $end->endOfYear();
        while ($start <= $end) {
            $row = [clone $start];

            foreach ($budgets as $budget) {
                $row[] = $bdt->spentInMonth($budget, $start);
            }

            /*
             * Without a budget:
             */
            $endOfMonth = clone $start;
            $endOfMonth->endOfMonth();
            $set   = $bdt->transactionsWithoutBudgetInDateRange($start, $endOfMonth);
            $row[] = floatval($set->sum('amount')) * -1;

            $chart->addRowArray($row);
            $start->addMonth();
        }


        $chart->generate();

        return Response::json($chart->getData());
    }

    /**
     * @param Component $component
     * @param           $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function componentsAndSpending(Component $component, $year)
    {
        try {
            $start = new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            App::abort(500);
        }

        if ($component->class == 'Budget') {
            /** @var \FireflyIII\Database\Budget $repos */
            $repos = App::make('FireflyIII\Database\Budget');
        } else {
            /** @var \FireflyIII\Database\Category $repos */
            $repos = App::make('FireflyIII\Database\Category');
        }

        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('Month', 'date');
        $chart->addColumn('Budgeted', 'number');
        $chart->addColumn('Spent', 'number');

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

            $chart->addRow(clone $start, $budgeted, $spent);

            $start->addMonth();
        }


        $chart->generate();

        return Response::json($chart->getData());


    }

    /**
     * @param Piggybank $piggybank
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function piggyBankHistory(\Piggybank $piggybank)
    {
        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('Date', 'date');
        $chart->addColumn('Balance', 'number');

        $set = \DB::table('piggybank_events')->where('piggybank_id', $piggybank->id)->groupBy('date')->get(['date', DB::Raw('SUM(`amount`) AS `sum`')]);

        foreach ($set as $entry) {
            $chart->addRow(new Carbon($entry->date), floatval($entry->sum));
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param RecurringTransaction $recurring
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recurringOverview(RecurringTransaction $recurring)
    {

        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('Date', 'date');
        $chart->addColumn('Max amount', 'number');
        $chart->addColumn('Min amount', 'number');
        $chart->addColumn('Current entry', 'number');

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
            $chart->addRow(clone $start, $recurring->amount_max, $recurring->amount_min, $amount);
            $start = DateKit::addPeriod($start, $recurring->repeat_freq, 0);
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws \FireflyIII\Exception\FireflyException
     */
    public function recurringTransactionsOverview()
    {

        /*
         * Set of paid transaction journals.
         * Set of unpaid recurring transactions.
         */
        $paid   = ['items' => [], 'amount' => 0];
        $unpaid = ['items' => [], 'amount' => 0];

        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('Name', 'string');
        $chart->addColumn('Amount', 'number');

        /** @var \FireflyIII\Database\Recurring $rcr */
        $rcr = App::make('FireflyIII\Database\Recurring');

        $recurring = $rcr->get();

        /** @var \RecurringTransaction $entry */
        foreach ($recurring as $entry) {
            /*
             * Start another loop starting at the $date.
             */
            $start = clone $entry->date;
            $end   = Carbon::now();

            /*
             * The jump we make depends on the $repeat_freq
             */
            $current = clone $start;

            while ($current <= $end) {
                /*
                 * Get end of period for $current:
                 */
                $currentEnd = DateKit::endOfPeriod($current, $entry->repeat_freq);

                /*
                 * In the current session range?
                 */
                if (\Session::get('end') >= $current and $currentEnd >= \Session::get('start')) {
                    /*
                     * Lets see if we've already spent money on this recurring transaction (it hath recurred).
                     */
                    /** @var TransactionJournal $set */
                    $journal = $rcr->getJournalForRecurringInRange($entry, $current, $currentEnd);

                    if (is_null($journal)) {
                        $unpaid['items'][] = $entry->name;
                        $unpaid['amount'] += (($entry->amount_max + $entry->amount_min) / 2);
                    } else {
                        $amount          = $journal->getAmount();
                        $paid['items'][] = $journal->description;
                        $paid['amount'] += $amount;
                    }
                }

                /*
                 * Add some time for the next loop!
                 */
                $current = DateKit::addPeriod($current, $entry->repeat_freq, intval($entry->skip));

            }

        }
        /** @var \RecurringTransaction $entry */
        $chart->addRow('Unpaid: ' . join(', ', $unpaid['items']), $unpaid['amount']);
        $chart->addRow('Paid: ' . join(', ', $paid['items']), $paid['amount']);

        $chart->generate();

        return Response::json($chart->getData());

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
        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('Month', 'date');
        $chart->addColumn('Income', 'number');
        $chart->addColumn('Expenses', 'number');

        /** @var \FireflyIII\Database\TransactionJournal $tj */
        $tj = App::make('FireflyIII\Database\TransactionJournal');

        $end = clone $start;
        $end->endOfYear();
        while ($start < $end) {

            // total income:
            $income  = $tj->getSumOfIncomesByMonth($start);
            $expense = $tj->getSumOfExpensesByMonth($start);

            $chart->addRow(clone $start, $income, $expense);
            $start->addMonth();
        }


        $chart->generate();

        return Response::json($chart->getData());

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
        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('Summary', 'string');
        $chart->addColumn('Income', 'number');
        $chart->addColumn('Expenses', 'number');

        /** @var \FireflyIII\Database\TransactionJournal $tj */
        $tj = App::make('FireflyIII\Database\TransactionJournal');

        $end = clone $start;
        $end->endOfYear();
        $income  = 0;
        $expense = 0;
        $count   = 0;
        while ($start < $end) {

            // total income:
            $income += $tj->getSumOfIncomesByMonth($start);
            $expense += $tj->getSumOfExpensesByMonth($start);
            $count++;

            $start->addMonth();
        }
        $chart->addRow('Sum', $income, $expense);
        $count = $count > 0 ? $count : 1;
        $chart->addRow('Average', ($income / $count), ($expense / $count));


        $chart->generate();

        return Response::json($chart->getData());

    }
} 
<?php

use Carbon\Carbon;
use Firefly\Exception\FireflyException;
use Firefly\Helper\Controllers\ChartInterface;
use Firefly\Storage\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class ChartController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ChartController extends BaseController
{

    protected $_chart;
    protected $_accounts;


    /**
     * @param ChartInterface $chart
     * @param AccountRepositoryInterface $accounts
     */
    public function __construct(ChartInterface $chart, AccountRepositoryInterface $accounts)
    {
        $this->_chart    = $chart;
        $this->_accounts = $accounts;
    }

    /**
     * This method takes a budget, all limits and all their repetitions and displays three numbers per repetition:
     * the amount of money in the repetition (represented as "an envelope"), the amount spent and the spent percentage.
     *
     * @param Budget $budget
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetDefault(\Budget $budget)
    {
        $expense  = [];
        $left     = [];
        $envelope = [];
        // get all limit repetitions for this budget.
        /** @var \Limit $limit */
        foreach ($budget->limits as $limit) {
            /** @var \LimitRepetition $rep */
            foreach ($limit->limitrepetitions as $rep) {
                // get the amount of money spent in this period on this budget.
                $spentInRep = $rep->amount - $rep->leftInRepetition();
                $pct        = round((floatval($spentInRep) / floatval($limit->amount)) * 100, 2);
                $name       = $rep->periodShow();
                $envelope[] = [$name, floatval($limit->amount)];
                $expense[]  = [$name, floatval($spentInRep)];
                $left[]     = [$name, $pct];
            }
        }

        $return = [
            'chart_title' => 'Overview for budget ' . $budget->name,
            'subtitle' => 'All envelopes',
            'series' => [
                [
                    'type' => 'line',
                    'yAxis' => 1,
                    'name' => 'Amount in envelope',
                    'data' => $envelope
                ],
                [
                    'type' => 'column',
                    'name' => 'Expenses in envelope',
                    'data' => $expense
                ],
                [
                    'type' => 'line',
                    'yAxis' => 1,
                    'name' => 'Spent percentage for envelope',
                    'data' => $left
                ]


            ]
        ];

        return Response::json($return);
    }

    /**
     * This method takes a single limit repetition (so a single "envelope") and displays the amount of money spent
     * per day and subsequently how much money is left.
     *
     * @param LimitRepetition $rep
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetLimit(\LimitRepetition $rep)
    {
        $budget             = $rep->limit->budget;
        $current            = clone $rep->startdate;
        $expense            = [];
        $leftInLimit        = [];
        $currentLeftInLimit = floatval($rep->limit->amount);
        while ($current <= $rep->enddate) {
            $spent              = $this->_chart->spentOnDay($budget, $current);
            $spent              = floatval($spent) == 0 ? null : floatval($spent);
            $entry              = [$current->timestamp * 1000, $spent];
            $expense[]          = $entry;
            $currentLeftInLimit = $currentLeftInLimit - $spent;
            $leftInLimit[]      = [$current->timestamp * 1000, $currentLeftInLimit];
            $current->addDay();
        }

        $return = [
            'chart_title' => 'Overview for budget ' . $budget->name,
            'subtitle' =>
                'Between ' . $rep->startdate->format('M jS, Y') . ' and ' . $rep->enddate->format('M jS, Y'),
            'series' => [
                [
                    'type' => 'column',
                    'name' => 'Expenses per day',
                    'yAxis' => 1,
                    'data' => $expense
                ],
                [
                    'type' => 'line',
                    'name' => 'Left in envelope',
                    'data' => $leftInLimit
                ]

            ]
        ];

        return Response::json($return);
    }

    /**
     * This method takes a budget and gets all transactions in it which haven't got an envelope (limit).
     *
     * Usually this means that very old and unorganized or very NEW transactions get displayed; there was never an
     * envelope or it hasn't been created (yet).
     *
     *
     * @param Budget $budget
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetNoLimits(\Budget $budget)
    {
        /*
         * Firefly can go about this two ways. Either it finds all transactions which definitely are IN an envelope
         * and exclude them or it searches for transactions outside of the range of any of the envelopes there are.
         *
         * Since either is kinda shitty Firefly uses the first one because it's easier to build.
         */
        $inRepetitions = $this->_chart->allJournalsInBudgetEnvelope($budget);

        /*
         * With this set of id's, Firefly can search for all journals NOT in that set.
         * BUT they have to be in the budget (duh).
         */
        $set = $this->_chart->journalsNotInSet($budget, $inRepetitions);
        /*
         * Next step: get all transactions for those journals.
         */
        $transactions = $this->_chart->transactionsByJournals($set);


        /*
         *  this set builds the chart:
         */
        $expense = [];

        foreach ($transactions as $t) {
            $date      = new Carbon($t->date);
            $expense[] = [$date->timestamp * 1000, floatval($t->aggregate)];
        }
        $return = [
            'chart_title' => 'Overview for ' . $budget->name,
            'subtitle' => 'Not organized by an envelope',
            'series' => [
                [
                    'type' => 'column',
                    'name' => 'Expenses per day',
                    'data' => $expense
                ]

            ]
        ];
        return Response::json($return);
    }

    /**
     * This method gets all transactions within a budget within the period set by the current session
     * start and end date. It also includes any envelopes which might exist within this period.
     *
     * @param Budget $budget
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetSession(\Budget $budget)
    {
        $series = [];
        $end    = clone Session::get('end');
        $start  = clone Session::get('start');


        /*
         * Expenses per day in the session's period. That's easy.
         */
        $expense = [];
        $current = clone Session::get('start');
        while ($current <= $end) {
            $spent     = $this->_chart->spentOnDay($budget, $current);
            $spent     = floatval($spent) == 0 ? null : floatval($spent);
            $expense[] = [$current->timestamp * 1000, $spent];
            $current->addDay();
        }

        $series[] = [
            'type' => 'column',
            'name' => 'Expenses per day',
            'data' => $expense
        ];
        unset($expense, $spent, $current);

        /*
         * Find all limit repetitions (for this budget) between start and end. This is
         * quite a complex query.
         */
        $reps = $this->_chart->limitsInRange($budget, $start, $end);

        /*
         * For each limitrepetition Firefly creates a serie that contains the amount left in
         * the limitrepetition for its entire date-range. Entries are only actually included when they
         * fall into the charts date range.
         *
         * So example: the user has a session date from Jan 15 to Jan 30. The limitrepetition
         * starts at 1 Jan until 1 Feb.
         *
         * Firefly loops from 1 Jan to 1 Feb but only includes Jan 15 / Jan 30.
         * But it does keep count of the amount outside of these dates because otherwise the line might be wrong.
         */
        /** @var \LimitRepetition $repetition */
        foreach ($reps as $repetition) {
            $limitAmount = $repetition->limit->amount;

            // create a serie for the repetition.
            $currentSerie = [
                'type' => 'spline',
                'id' => 'rep-' . $repetition->id,
                'yAxis' => 1,
                'name' => 'Envelope #' . $repetition->id . ' in ' . $repetition->periodShow(),
                'data' => []
            ];
            $current      = clone $repetition->startdate;
            while ($current <= $repetition->enddate) {
                if ($current >= $start && $current <= $end) {
                    // spent on limit:
                    $spentSoFar  = $this->_chart->spentOnLimitRepetitionBetweenDates(
                        $repetition, $repetition->startdate, $current
                    );
                    $leftInLimit = floatval($limitAmount) - floatval($spentSoFar);

                    $currentSerie['data'][] = [$current->timestamp * 1000, $leftInLimit];
                }
                $current->addDay();
            }

            // do something here.
            $series[] = $currentSerie;
        }

        $return = [
            'chart_title' => 'Overview for budget ' . $budget->name,
            'subtitle' =>
                'Between ' . Session::get('start')->format('M jS, Y') . ' and ' . Session::get('end')->format(
                    'M jS, Y'
                ),
            'series' => $series
        ];

        return Response::json($return);

    }

    /**
     * @param Category $category
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categoryShowChart(Category $category)
    {
        $start = Session::get('start');
        $end   = Session::get('end');
        $range = Session::get('range');

        $serie = $this->_chart->categoryShowChart($category, $range, $start, $end);
        $data  = [
            'chart_title' => $category->name,
            'subtitle' => '<a href="' . route('categories.show', [$category->id]) . '">View more</a>',
            'series' => $serie
        ];

        return Response::json($data);


    }

    /**
     * @param Account $account
     *
     * @return mixed
     */
    public function homeAccount(Account $account = null)
    {
        // get preferences and accounts (if necessary):
        $start = Session::get('start');
        $end   = Session::get('end');

        if (is_null($account)) {
            // get, depending on preferences:
            /** @var  \Firefly\Helper\Preferences\PreferencesHelperInterface $prefs */
            $prefs = \App::make('Firefly\Helper\Preferences\PreferencesHelperInterface');
            $pref  = $prefs->get('frontpageAccounts', []);

            /** @var \Firefly\Storage\Account\AccountRepositoryInterface $acct */
            $acct     = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
            $accounts = $acct->getByIds($pref->data);
        } else {
            $accounts = [$account];
        }
        // loop and get array data.

        $url  = count($accounts) == 1 && is_array($accounts)
            ? '<a href="' . route('accounts.show', [$account->id]) . '">View more</a>'
            :
            '<a href="' . route('accounts.index') . '">View more</a>';
        $data = [
            'chart_title' => count($accounts) == 1 ? $accounts[0]->name : 'All accounts',
            'subtitle' => $url,
            'series' => []
        ];

        foreach ($accounts as $account) {
            $data['series'][] = $this->_chart->account($account, $start, $end);
        }

        return Response::json($data);
    }

    /**
     * @param $name
     * @param $day
     * @param $month
     * @param $year
     *
     * @return $this
     */
    public function homeAccountInfo($name, $day, $month, $year)
    {
        $account = $this->_accounts->findByName($name);

        $date   = Carbon::createFromDate($year, $month, $day);
        $result = $this->_chart->accountDailySummary($account, $date);

        return View::make('charts.info')->with('rows', $result['rows'])->with('sum', $result['sum'])->with(
            'account', $account
        );
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function homeBudgets()
    {
        $start = Session::get('start');
        $end   = Session::get('end');
        $data  = [
            'labels' => [],
            'series' => [
                [
                    'name' => 'Limit',
                    'data' => []
                ],
                [
                    'name' => 'Spent',
                    'data' => []
                ],
            ]
        ];

        // Get all budgets.
        $budgets   = \Auth::user()->budgets()->orderBy('name', 'ASC')->get();
        $budgetIds = [];
        /** @var \Budget $budget */
        foreach ($budgets as $budget) {
            $budgetIds[] = $budget->id;

            // Does the budget have a limit starting on $start?
            $rep = \LimitRepetition::
            leftJoin('limits', 'limit_repetitions.limit_id', '=', 'limits.id')->leftJoin(
                'components', 'limits.component_id', '=', 'components.id'
            )->where('limit_repetitions.startdate', $start->format('Y-m-d'))->where(
                'components.id', $budget->id
            )->first(['limit_repetitions.*']);

            if (is_null($rep)) {
                $limit     = 0.0;
                $id        = null;
                $parameter = 'useSession=true';
            } else {
                $limit     = floatval($rep->amount);
                $id        = $rep->id;
                $parameter = '';
            }

            // Date range to check for expenses made?
            if (is_null($rep)) {
                // use the session start and end for our search query
                $expenseStart = Session::get('start');
                $expenseEnd   = Session::get('end');

            } else {
                // use the limit's start and end for our search query
                $expenseStart = $rep->startdate;
                $expenseEnd   = $rep->enddate;
            }
            // How much have we spent on this budget?
            $expenses = floatval($budget->transactionjournals()->before($expenseEnd)->after($expenseStart)->lessThan(0)->sum('amount')) * -1;

            // Append to chart:
            if ($limit > 0 || $expenses > 0) {
                $data['labels'][]            = $budget->name;
                $data['series'][0]['data'][] = [
                    'y' => $limit,
                    'url' => route('budgets.show', [$budget->id, $id]) . '?' . $parameter
                ];
                $data['series'][1]['data'][] = [
                    'y' => $expenses,
                    'url' => route('budgets.show', [$budget->id, $id]) . '?' . $parameter
                ];
            }
        }
        // Add expenses that have no budget:
        $set = \Auth::user()->transactionjournals()->whereNotIn(
            'transaction_journals.id', function ($query) use ($start, $end) {
                $query->select('transaction_journals.id')->from('transaction_journals')
                      ->leftJoin(
                          'component_transaction_journal', 'component_transaction_journal.transaction_journal_id', '=',
                          'transaction_journals.id'
                      )
                      ->leftJoin('components', 'components.id', '=', 'component_transaction_journal.component_id')
                      ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                      ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                      ->where('components.class', 'Budget');
            }
        )->before($end)->after($start)->lessThan(0)->transactionTypes(['Withdrawal'])->sum('amount');

        // This can be debugged by using get(['transaction_journals.*','transactions.amount']);
        $data['labels'][]            = 'No budget';
        $data['series'][0]['data'][] = [
            'y' => 0,
            'url' => route('budgets.nobudget', 'session')
        ];
        $data['series'][1]['data'][] = [
            'y' => floatval($set) * -1,
            'url' => route('budgets.nobudget', 'session')
        ];

        return Response::json($data);

    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function homeCategories()
    {
        $start = Session::get('start');
        $end   = Session::get('end');

        return Response::json($this->_chart->categories($start, $end));


    }

    /**
     * This method checks all recurring transactions, calculates the current "moment" and returns
     * a list of yet to be paid (and paid) recurring transactions. This list can be used to show a beautiful chart
     * to the end user who will love it and cherish it.
     *
     * @throws FireflyException
     */
    public function homeRecurring()
    {
        /** @var \Firefly\Helper\Toolkit\ToolkitInterface $toolkit */
        $toolkit               = App::make('Firefly\Helper\Toolkit\ToolkitInterface');
        $recurringTransactions = \Auth::user()->recurringtransactions()->get();
        $sessionStart          = \Session::get('start');
        $sessionEnd            = \Session::get('end');
        $paid                  = [];
        $unpaid                = [];

        /** @var \RecurringTransaction $recurring */
        foreach ($recurringTransactions as $recurring) {
            /*
             * Start a loop starting at the $date.
             */
            $start = clone $recurring->date;
            $end   = Carbon::now();

            /*
             * The jump we make depends on the $repeat_freq
             */
            $current = clone $start;

            \Log::debug('Now looping recurring transaction #' . $recurring->id . ': ' . $recurring->name);

            while ($current <= $end) {
                /*
                 * Get end of period for $current:
                 */
                $currentEnd = clone $current;
                $toolkit->endOfPeriod($currentEnd, $recurring->repeat_freq);
                \Log::debug('Now at $current: ' . $current->format('D d F Y') . ' - ' . $currentEnd->format('D d F Y'));

                /*
                 * In the current session range?
                 */
                if ($sessionEnd >= $current and $currentEnd >= $sessionStart) {
                    /*
                     * Lets see if we've already spent money on this recurring transaction (it hath recurred).
                     */
                    /** @var Collection $set */
                    $set = \Auth::user()->transactionjournals()->where('recurring_transaction_id', $recurring->id)
                                ->after($current)->before($currentEnd)->get();
                    if (count($set) > 1) {
                        \Log::error('Recurring #' . $recurring->id . ', dates [' . $current . ',' . $currentEnd . ']. Found multiple hits. Cannot handle this!');
                        throw new FireflyException('Cannot handle multiple transactions. See logs.');
                    } else if (count($set) == 0) {
                        $unpaid[] = $recurring;
                    } else {
                        $paid[] = $set->get(0);
                    }

                }


                /*
                 * Add some time for the next loop!
                 */
                $toolkit->addPeriod($current, $recurring->repeat_freq, intval($recurring->skip));

            }

        }
        /*
         * Loop paid and unpaid to make two haves for a pie chart.
         */
        $unPaidColours = $toolkit->colorRange('AA4643', 'FFFFFF', count($unpaid) == 0 ? 1 : count($unpaid));
        $paidColours   = $toolkit->colorRange('4572A7', 'FFFFFF', count($paid) == 0 ? 1 : count($paid));
        $serie         = [
            'type' => 'pie',
            'name' => 'Amount',
            'data' => []
        ];

        /** @var TransactionJournal $entry */
        foreach ($paid as $index => $entry) {
            $transactions    = $entry->transactions()->get();
            $amount          = max(floatval($transactions[0]->amount), floatval($transactions[1]->amount));
            $serie['data'][] = [
                'name' => $entry->description,
                'y' => $amount,
                'color' => $paidColours[$index]
            ];
        }


        /** @var RecurringTransaction $entry */
        foreach ($unpaid as $index => $entry) {
            $amount          = (floatval($entry->amount_max) + floatval($entry->amount_min)) / 2;
            $serie['data'][] = [
                'name' => $entry->name,
                'y' => $amount,
                'color' => $unPaidColours[$index]
            ];
        }

        return Response::json([$serie]);

    }
}
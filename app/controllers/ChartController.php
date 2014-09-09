<?php

use Carbon\Carbon;
use Firefly\Helper\Controllers\ChartInterface;
use Firefly\Storage\Account\AccountRepositoryInterface;

/**
 * Class ChartController
 */
class ChartController extends BaseController
{

    protected $_chart;
    protected $_accounts;


    /**
     * @param ChartInterface             $chart
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
                $spentInRep = $rep->amount - $rep->left();
                $pct        = round((floatval($spentInRep) / floatval($limit->amount)) * 100, 2);
                $name       = $rep->periodShow();
                $envelope[] = [$name, floatval($limit->amount)];
                $expense[]  = [$name, floatval($spentInRep)];
                $left[]     = [$name, $pct];
            }
        }

        $return = [
            'chart_title' => 'Overview for budget ' . $budget->name,
            'subtitle'    => 'All envelopes',
            'series'      => [
                [
                    'type'  => 'line',
                    'yAxis' => 1,
                    'name'  => 'Amount in envelope',
                    'data'  => $envelope
                ],
                [
                    'type' => 'column',
                    'name' => 'Expenses in envelope',
                    'data' => $expense
                ],
                [
                    'type'  => 'line',
                    'yAxis' => 1,
                    'name'  => 'Spent percentage for envelope',
                    'data'  => $left
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
            'subtitle'    =>
                'Between ' . $rep->startdate->format('M jS, Y') . ' and ' . $rep->enddate->format('M jS, Y'),
            'series'      => [
                [
                    'type'  => 'column',
                    'name'  => 'Expenses per day',
                    'yAxis' => 1,
                    'data'  => $expense
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
         * We can go about this two ways. Either we find all transactions which definitely are IN an envelope
         * and exclude them or we search for transactions outside of the range of any of the envelopes we have.
         *
         * Since either is shitty we go with the first one because it's easier to build.
         */
        $inRepetitions = $this->_chart->allJournalsInBudgetEnvelope($budget);

        /*
         * With this set of id's, we can search for all journals NOT in that set.
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
            'subtitle'    => 'Not organized by an envelope',
            'series'      => [
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
     * @param Budget $budget
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetSession(\Budget $budget)
    {
        $expense          = [];
        $repetitionSeries = [];
        $current          = clone Session::get('start');
        $end              = clone Session::get('end');
        while ($current <= $end) {
            $spent     = $this->_chart->spentOnDay($budget, $current);
            $spent     = floatval($spent) == 0 ? null : floatval($spent);
            $expense[] = [$current->timestamp * 1000, $spent];
            $current->addDay();
        }

        // find all limit repetitions (for this budget) between start and end.
        $start              = clone Session::get('start');
        $repetitionSeries[] = [
            'type' => 'column',
            'name' => 'Expenses per day',
            'data' => $expense
        ];


        /** @var \Limit $limit */
        foreach ($budget->limits as $limit) {
            $reps               = $limit->limitrepetitions()->where(
                function ($q) use ($start, $end) {
                    // startdate is between range
                    $q->where(
                        function ($q) use ($start, $end) {
                            $q->where('startdate', '>=', $start->format('Y-m-d'));
                            $q->where('startdate', '<=', $end->format('Y-m-d'));
                        }
                    );

                    // or enddate is between range.
                    $q->orWhere(
                        function ($q) use ($start, $end) {
                            $q->where('enddate', '>=', $start->format('Y-m-d'));
                            $q->where('enddate', '<=', $end->format('Y-m-d'));
                        }
                    );
                }
            )->get();
            $currentLeftInLimit = floatval($limit->amount);
            /** @var \LimitRepetition $repetition */
            foreach ($reps as $repetition) {
                // create a serie for the repetition.
                $currentSerie = [
                    'type'  => 'spline',
                    'id'    => 'rep-' . $repetition->id,
                    'yAxis' => 1,
                    'name'  => 'Envelope in ' . $repetition->periodShow(),
                    'data'  => []
                ];
                $current      = clone $repetition->startdate;
                while ($current <= $repetition->enddate) {
                    if ($current >= Session::get('start') && $current <= Session::get('end')) {
                        // spent on limit:

                        $spentSoFar         = \Transaction::
                            leftJoin(
                                'transaction_journals', 'transaction_journals.id', '=',
                                'transactions.transaction_journal_id'
                            )
                            ->leftJoin(
                                'component_transaction_journal', 'component_transaction_journal.transaction_journal_id',
                                '=',
                                'transaction_journals.id'
                            )->where('component_transaction_journal.component_id', '=', $budget->id)->where(
                                'transaction_journals.date', '>=', $repetition->startdate->format('Y-m-d')
                            )->where('transaction_journals.date', '<=', $current->format('Y-m-d'))->where(
                                'amount', '>', 0
                            )->sum('amount');
                        $spent              = floatval($spent) == 0 ? null : floatval($spent);
                        $currentLeftInLimit = floatval($limit->amount) - floatval($spentSoFar);

                        $currentSerie['data'][] = [$current->timestamp * 1000, $currentLeftInLimit];
                    }
                    $current->addDay();
                }

                // do something here.
                $repetitionSeries[] = $currentSerie;

            }

        }


        $return = [
            'chart_title' => 'Overview for budget ' . $budget->name,
            'subtitle'    =>
                'Between ' . Session::get('start')->format('M jS, Y') . ' and ' . Session::get('end')->format(
                    'M jS, Y'
                ),
            'series'      => $repetitionSeries
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
            'subtitle'    => '<a href="' . route('categories.show', [$category->id]) . '">View more</a>',
            'series'      => $serie
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
            'subtitle'    => $url,
            'series'      => []
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
        $start = \Session::get('start');

        return Response::json($this->_chart->budgets($start));
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
}
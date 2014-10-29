<?php
use Carbon\Carbon;

/**
 * Class GoogleChartController
 */
class GoogleChartController extends BaseController
{

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
        $pref        = $preferences->get('frontpageAccounts');

        /** @var \FireflyIII\Database\Account $acct */
        $acct     = App::make('FireflyIII\Database\Account');
        $accounts = $acct->getByIds($pref->data);


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
                if ($current > Carbon::now()) {
                    $row[] = null;
                } else {
                    $row[] = $account->balance($current);
                }

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
                $amount   = floatval($journal->transactions[1]->amount);
                $amount   = $amount < 0 ? $amount * -1 : $amount;
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

    public function recurringTransactionsOverview()
    {

        /*
         * Set of paid transaction journals.
         * Set of unpaid recurring transactions.
         */
        $paid   = [
            'items'  => [],
            'amount' => 0
        ];
        $unpaid = [
            'items'  => [],
            'amount' => 0
        ];

        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('Name', 'string');
        $chart->addColumn('Amount', 'number');

        /** @var \FireflyIII\Database\Recurring $rcr */
        $rcr = App::make('FireflyIII\Database\Recurring');

        /** @var \FireflyIII\Shared\Toolkit\Date $dateKit */
        $dateKit = App::make('FireflyIII\Shared\Toolkit\Date');

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
                $currentEnd = clone $current;
                $dateKit->endOfPeriod($currentEnd, $entry->repeat_freq);

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
                        $amount          = floatval($journal->transactions[0]->amount);
                        $amount          = $amount < 0 ? $amount * -1 : $amount;
                        $paid['items'][] = $journal->description;
                        $paid['amount'] += $amount;
                    }
                }

                /*
                 * Add some time for the next loop!
                 */
                $dateKit->addPeriod($current, $entry->repeat_freq, intval($entry->skip));

            }

        }
        /** @var \RecurringTransaction $entry */
        $chart->addRow('Unpaid: ' . join(', ', $unpaid['items']), $unpaid['amount']);
        $chart->addRow('Paid: ' . join(', ', $paid['items']), $paid['amount']);

        $chart->generate();
        return Response::json($chart->getData());

    }
} 
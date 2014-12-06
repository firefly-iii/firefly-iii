<?php
use Carbon\Carbon;

/**
 * Class ReportController
 */
class ReportController extends BaseController
{

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
        /** @var \FireflyIII\Database\TransactionJournal $journals */
        $journals = App::make('FireflyIII\Database\TransactionJournal');
        /** @var TransactionJournal $journal */
        $journal = $journals->first();
        if (is_null($journal)) {
            $date = Carbon::now();
        } else {
            $date = clone $journal->date;
        }
        $years  = [];
        $months = [];
        while ($date <= Carbon::now()) {
            $years[] = $date->format('Y');
            $date->addYear();
        }
        // months
        if (is_null($journal)) {
            $date = Carbon::now();
        } else {
            $date = clone $journal->date;
        }
        while ($date <= Carbon::now()) {
            $months[] = [
                'formatted' => $date->format('F Y'),
                'month'     => intval($date->format('m')),
                'year'      => intval($date->format('Y')),
            ];
            $date->addMonth();
        }


        return View::make('reports.index', compact('years', 'months'))->with('title', 'Reports')->with('mainTitleIcon', 'fa-line-chart');
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
        Config::set('app.debug', false);
        try {
            $date = new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            App::abort(500);
        }
        $date = new Carbon('01-01-' . $year);

        /** @var \FireflyIII\Database\TransactionJournal $tj */
        $tj = App::make('FireflyIII\Database\TransactionJournal');

        /** @var \FireflyIII\Database\Account $accountRepository */
        $accountRepository = App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Database\Report $reportRepository */
        $reportRepository = App::make('FireflyIII\Database\Report');

        $accounts = $accountRepository->getAssetAccounts();

        // get some sums going
        $summary = [];

        /** @var \Account $account */
        $accounts->each(
            function (\Account $account) {
                if ($account->getMeta('accountRole') == 'sharedExpense') {
                    $account->sharedExpense = true;
                } else {
                    $account->sharedExpense = false;
                }
            }
        );


        $end = clone $date;
        $end->endOfYear();
        while ($date < $end) {
            $month = $date->format('F');

            $income        = 0;
            $incomeShared  = 0;
            $expense       = 0;
            $expenseShared = 0;

            foreach ($accounts as $account) {
                if ($account->sharedExpense === true) {
                    $incomeShared += $reportRepository->getIncomeByMonth($account, $date);
                    $expenseShared += $reportRepository->getExpenseByMonth($account, $date);
                } else {
                    $income += $reportRepository->getIncomeByMonth($account, $date);
                    $expense += $reportRepository->getExpenseByMonth($account, $date);
                }
            }

            $summary[] = [
                'month'         => $month,
                'income'        => $income,
                'expense'       => $expense,
                'incomeShared'  => $incomeShared,
                'expenseShared' => $expenseShared,
            ];
            $date->addMonth();
        }


        // draw some charts etc.
        return View::make('reports.year', compact('summary', 'date'))->with('title', 'Reports')->with('mainTitleIcon', 'fa-line-chart')->with('subTitle', $year)
                   ->with(
                       'subTitleIcon', 'fa-bar-chart'
                   )->with('year', $year);
    }

}
<?php namespace FireflyIII\Http\Controllers;

use App;
use Auth;
use Carbon\Carbon;
use Crypt;
use DB;
use Exception;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Http\Requests;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Grumpydictator\Gchart\GChart;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Navigation;
use Preferences;
use Response;
use Session;
use Steam;

/**
 * Class GoogleChartController
 *
 * @package FireflyIII\Http\Controllers
 */
class GoogleChartController extends Controller
{


    /**
     * @param Account $account
     * @param string  $view
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accountBalanceChart(Account $account, GChart $chart)
    {
        $chart->addColumn('Day of month', 'date');
        $chart->addColumn('Balance for ' . $account->name, 'number');
        $chart->addCertainty(1);

        $start   = Session::get('start', Carbon::now()->startOfMonth());
        $end     = Session::get('end', Carbon::now()->endOfMonth());
        $current = clone $start;

        while ($end >= $current) {
            $chart->addRow(clone $current, Steam::balance($account, $current), false);
            $current->addDay();
        }


        $chart->generate();

        return Response::json($chart->getData());
    }

    /**
     * @param GChart $chart
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function allAccountsBalanceChart(GChart $chart)
    {
        $chart->addColumn('Day of the month', 'date');

        $frontPage = Preferences::get('frontPageAccounts', []);
        $start     = Session::get('start', Carbon::now()->startOfMonth());
        $end       = Session::get('end', Carbon::now()->endOfMonth());

        if ($frontPage->data == []) {
            $accounts = Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account'])->get(['accounts.*']);
        } else {
            $accounts = Auth::user()->accounts()->whereIn('id', $frontPage->data)->get(['accounts.*']);
        }
        $index = 1;
        /** @var Account $account */
        foreach ($accounts as $account) {
            $chart->addColumn('Balance for ' . $account->name, 'number');
            $chart->addCertainty($index);
            $index++;
        }
        $current = clone $start;
        $current->subDay();
        $today = Carbon::now();
        while ($end >= $current) {
            $row     = [clone $current];
            $certain = $current < $today;
            foreach ($accounts as $account) {
                $row[] = Steam::balance($account, $current);
                $row[] = $certain;
            }
            $chart->addRowArray($row);
            $current->addDay();
        }
        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param int $year
     *
     * @return $this|\Illuminate\Http\JsonResponse
     */
    public function allBudgetsAndSpending($year, GChart $chart, BudgetRepositoryInterface $repository)
    {
        try {
            new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            return view('error')->with('message', 'Invalid year.');
        }
        $budgets = Auth::user()->budgets()->get();
        $budgets->sortBy('name');
        $chart->addColumn('Month', 'date');
        foreach ($budgets as $budget) {
            $chart->addColumn($budget->name, 'number');
        }
        $start = Carbon::createFromDate(intval($year), 1, 1);
        $end   = clone $start;
        $end->endOfYear();


        while ($start <= $end) {
            $row = [clone $start];
            foreach ($budgets as $budget) {
                $spent = $repository->spentInMonth($budget, $start);
                $row[] = $spent;
            }
            $chart->addRowArray($row);
            $start->addMonth();
        }


        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param GChart $chart
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function allBudgetsHomeChart(GChart $chart)
    {
        $chart->addColumn('Budget', 'string');
        $chart->addColumn('Budgeted', 'number');
        $chart->addColumn('Spent', 'number');

        $budgets = Auth::user()->budgets()->orderBy('name', 'DESC')->get();
        $start   = Session::get('start', Carbon::now()->startOfMonth());
        $end     = Session::get('end', Carbon::now()->endOfMonth());

        /** @var Budget $budget */
        foreach ($budgets as $budget) {

            /** @var Collection $repetitions */
            $repetitions = LimitRepetition::
            leftJoin('budget_limits', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                                          ->where('limit_repetitions.startdate', '<=', $end->format('Y-m-d 00:00:00'))
                                          ->where('limit_repetitions.startdate', '>=', $start->format('Y-m-d 00:00:00'))
                                          ->where('budget_limits.budget_id', $budget->id)
                                          ->get(['limit_repetitions.*']);

            // no results? search entire range for expenses and list those.
            if ($repetitions->count() == 0) {
                $expenses = floatval($budget->transactionjournals()->before($end)->after($start)->lessThan(0)->sum('amount')) * -1;
                if ($expenses > 0) {
                    $chart->addRow($budget->name, 0, $expenses);
                }
            } else {
                // add with foreach:
                /** @var LimitRepetition $repetition */
                foreach ($repetitions as $repetition) {

                    $expenses
                        =
                        floatval($budget->transactionjournals()->before($repetition->enddate)->after($repetition->startdate)->lessThan(0)->sum('amount')) * -1;
                    if ($expenses > 0) {
                        $chart->addRow($budget->name . ' (' . $repetition->startdate->format('j M Y') . ')', floatval($repetition->amount), $expenses);
                    }
                }
            }


        }

        $noBudgetSet = Auth::user()
                           ->transactionjournals()
                           ->whereNotIn(
                               'transaction_journals.id', function (QueryBuilder $query) use ($start, $end) {
                               $query
                                   ->select('transaction_journals.id')
                                   ->from('transaction_journals')
                                   ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                                   ->where('transaction_journals.date', '>=', $start->format('Y-m-d 00:00:00'))
                                   ->where('transaction_journals.date', '<=', $end->format('Y-m-d 00:00:00'));
                           }
                           )
                           ->before($end)
                           ->after($start)
                           ->lessThan(0)
                           ->transactionTypes(['Withdrawal'])
                           ->get();
        $sum         = $noBudgetSet->sum('amount') * -1;
        $chart->addRow('No budget', 0, $sum);
        $chart->generate();

        return Response::json($chart->getData());
    }

    /**
     * @param GChart $chart
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function allCategoriesHomeChart(GChart $chart)
    {
        $chart->addColumn('Category', 'string');
        $chart->addColumn('Spent', 'number');

        // query!
        $start = Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->endOfMonth());
        $set   = TransactionJournal::leftJoin(
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
                                   ->before($end)
                                   ->after($start)
                                   ->where('transaction_types.type', 'Withdrawal')
                                   ->groupBy('categories.id')
                                   ->orderBy('sum', 'DESC')
                                   ->get(['categories.id', 'categories.name', \DB::Raw('SUM(`transactions`.`amount`) AS `sum`')]);

        foreach ($set as $entry) {
            $entry->name = strlen($entry->name) == 0 ? '(no category)' : $entry->name;
            $chart->addRow($entry->name, floatval($entry->sum));
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param Bill $bill
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function billOverview(Bill $bill, GChart $chart)
    {

        $chart->addColumn('Date', 'date');
        $chart->addColumn('Max amount', 'number');
        $chart->addColumn('Min amount', 'number');
        $chart->addColumn('Current entry', 'number');

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
                /** @var Transaction $tr */
                foreach ($result->transactions()->get() as $tr) {
                    if (floatval($tr->amount) > 0) {
                        $amount = floatval($tr->amount);
                    }
                }
            } else {
                $amount = 0;
            }
            unset($result);
            $chart->addRow(clone $start, $bill->amount_max, $bill->amount_min, $amount);
            $start = Navigation::addPeriod($start, $bill->repeat_freq, 0);
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     * @param GChart $chart
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function billsOverview(GChart $chart)
    {
        $paid   = ['items' => [], 'amount' => 0];
        $unpaid = ['items' => [], 'amount' => 0];
        $start  = Session::get('start', Carbon::now()->startOfMonth());
        $end    = Session::get('end', Carbon::now()->endOfMonth());

        $chart->addColumn('Name', 'string');
        $chart->addColumn('Amount', 'number');

        $set = Bill::
        leftJoin(
            'transaction_journals', function (JoinClause $join) use ($start, $end) {
            $join->on('bills.id', '=', 'transaction_journals.bill_id')
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
                   ->groupBy('bills.id')
                   ->get(
                       ['bills.id', 'bills.name', 'transaction_journals.description',
                        'transaction_journals.encrypted',
                        'transaction_journals.id as journalId',
                        \DB::Raw('SUM(`bills`.`amount_min` + `bills`.`amount_max`) / 2 as `averageAmount`'),
                        'transactions.amount AS actualAmount']
                   );

        foreach ($set as $entry) {
            if (intval($entry->journalId) == 0) {
                $unpaid['items'][] = $entry->name;
                $unpaid['amount'] += floatval($entry->averageAmount);
            } else {
                $description     = intval($entry->encrypted) == 1 ? Crypt::decrypt($entry->description) : $entry->description;
                $paid['items'][] = $description;
                $paid['amount'] += floatval($entry->actualAmount);
            }
        }
        $chart->addRow('Unpaid: ' . join(', ', $unpaid['items']), $unpaid['amount']);
        $chart->addRow('Paid: ' . join(', ', $paid['items']), $paid['amount']);
        $chart->generate();

        return Response::json($chart->getData());
    }

    /**
     *
     * @param Budget          $budget
     * @param LimitRepetition $repetition
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetLimitSpending(Budget $budget, LimitRepetition $repetition, GChart $chart)
    {
        $start = clone $repetition->startdate;
        $end   = $repetition->enddate;

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
     *
     * @param Budget $budget
     *
     * @param int    $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgetsAndSpending(Budget $budget, $year = 0)
    {

        $chart      = App::make('Grumpydictator\Gchart\GChart');
        $repository = App::make('FireflyIII\Repositories\Budget\BudgetRepository');
        $chart->addColumn('Month', 'date');
        $chart->addColumn('Budgeted', 'number');
        $chart->addColumn('Spent', 'number');
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
            $spent      = $repository->spentInMonth($budget, $start);
            $repetition = LimitRepetition::leftJoin('budget_limits', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                                         ->where('limit_repetitions.startdate', $start->format('Y-m-d 00:00:00'))
                                         ->where('budget_limits.budget_id', $budget->id)
                                         ->first(['limit_repetitions.*']);

            if ($repetition) {
                $budgeted = floatval($repetition->amount);
                \Log::debug('Found a repetition on ' . $start->format('Y-m-d') . ' for budget ' . $budget->name . '!');
            } else {
                \Log::debug('No repetition on ' . $start->format('Y-m-d') . ' for budget ' . $budget->name);
                $budgeted = null;
            }
            $chart->addRow(clone $start, $budgeted, $spent);
            $start->addMonth();
        }

        $chart->generate();

        return Response::json($chart->getData());


    }

    /**
     *
     * @param Category  $category
     * @param           $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categoriesAndSpending(Category $category, $year, GChart $chart)
    {
        try {
            new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            return view('error')->with('message', 'Invalid year.');
        }

        $chart->addColumn('Month', 'date');
        $chart->addColumn('Budgeted', 'number');
        $chart->addColumn('Spent', 'number');

        $start = new Carbon('01-01-' . $year);
        $end   = clone $start;
        $end->endOfYear();
        while ($start <= $end) {

            $currentEnd = clone $start;
            $currentEnd->endOfMonth();
            $spent    = floatval($category->transactionjournals()->before($end)->after($start)->lessThan(0)->sum('amount')) * -1;
            $budgeted = null;

            $chart->addRow(clone $start, $budgeted, $spent);

            $start->addMonth();
        }


        $chart->generate();

        return Response::json($chart->getData());


    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function piggyBankHistory(PiggyBank $piggyBank, GChart $chart)
    {
        $chart->addColumn('Date', 'date');
        $chart->addColumn('Balance', 'number');

        $set = \DB::table('piggy_bank_events')->where('piggy_bank_id', $piggyBank->id)->groupBy('date')->get(['date', DB::Raw('SUM(`amount`) AS `sum`')]);

        foreach ($set as $entry) {
            $chart->addRow(new Carbon($entry->date), floatval($entry->sum));
        }

        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     *
     * @param $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function yearInExp($year, GChart $chart, ReportQueryInterface $query)
    {
        try {
            $start = new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            return view('error')->with('message', 'Invalid year.');
        }
        $chart->addColumn('Month', 'date');
        $chart->addColumn('Income', 'number');
        $chart->addColumn('Expenses', 'number');

        // get report query interface.

        $end = clone $start;
        $end->endOfYear();
        while ($start < $end) {
            $currentEnd = clone $start;
            $currentEnd->endOfMonth();
            // total income:
            $income    = $query->incomeByPeriod($start, $currentEnd);
            $incomeSum = 0;
            foreach ($income as $entry) {
                $incomeSum += floatval($entry->amount);
            }

            // total expenses:
            $expense    = $query->journalsByExpenseAccount($start, $currentEnd);
            $expenseSum = 0;
            foreach ($expense as $entry) {
                $expenseSum += floatval($entry->amount);
            }

            $chart->addRow(clone $start, $incomeSum, $expenseSum);
            $start->addMonth();
        }


        $chart->generate();

        return Response::json($chart->getData());

    }

    /**
     *
     * @param $year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function yearInExpSum($year, GChart $chart, ReportQueryInterface $query)
    {
        try {
            $start = new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            return view('error')->with('message', 'Invalid year.');
        }
        $chart->addColumn('Summary', 'string');
        $chart->addColumn('Income', 'number');
        $chart->addColumn('Expenses', 'number');

        $income  = 0;
        $expense = 0;
        $count   = 0;

        $end = clone $start;
        $end->endOfYear();
        while ($start < $end) {
            $currentEnd = clone $start;
            $currentEnd->endOfMonth();
            // total income:
            $incomeResult = $query->incomeByPeriod($start, $currentEnd);
            $incomeSum    = 0;
            foreach ($incomeResult as $entry) {
                $incomeSum += floatval($entry->amount);
            }

            // total expenses:
            $expenseResult = $query->journalsByExpenseAccount($start, $currentEnd);
            $expenseSum    = 0;
            foreach ($expenseResult as $entry) {
                $expenseSum += floatval($entry->amount);
            }

            $income += $incomeSum;
            $expense += $expenseSum;
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

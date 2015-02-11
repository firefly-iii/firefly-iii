<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Crypt;
use FireflyIII\Http\Requests;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\TransactionJournal;
use Grumpydictator\Gchart\GChart;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
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

            /** @var \LimitRepetition $repetition */
            $repetition = LimitRepetition::
            leftJoin('budget_limits', 'limit_repetitions.budget_limit_id', '=', 'budget_limits.id')
                                         ->where('limit_repetitions.startdate', $start->format('Y-m-d 00:00:00'))
                                         ->where('budget_limits.budget_id', $budget->id)
                                         ->first(['limit_repetitions.*']);
            if (is_null($repetition)) { // use the session start and end for our search query
                $searchStart = $start;
                $searchEnd   = $end;
                $limit       = 0; // the limit is zero:
            } else {
                // use the limit's start and end for our search query
                $searchStart = $repetition->startdate;
                $searchEnd   = $repetition->enddate;
                $limit       = floatval($repetition->amount); // the limit is the repetitions limit:
            }

            $expenses = floatval($budget->transactionjournals()->before($searchEnd)->after($searchStart)->lessThan(0)->sum('amount')) * -1;
            if ($expenses > 0) {
                $chart->addRow($budget->name, $limit, $expenses);
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


}

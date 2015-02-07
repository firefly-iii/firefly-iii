<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use FireflyIII\Http\Requests;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use Grumpydictator\Gchart\GChart;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
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


}

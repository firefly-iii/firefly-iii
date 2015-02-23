<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Exception;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Helpers\Report\ReportQueryInterface;
use FireflyIII\Http\Requests;
use FireflyIII\Models\Account;
use Illuminate\Database\Query\JoinClause;
use Steam;
use View;

/**
 * Class ReportController
 *
 * @package FireflyIII\Http\Controllers
 */
class ReportController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        View::share('title', 'Reports');
        View::share('mainTitleIcon', 'fa-line-chart');

    }


    /**
     * @param ReportHelperInterface $helper
     *
     * @return View
     */
    public function index(ReportHelperInterface $helper)
    {
        $start         = $helper->firstDate();
        $months        = $helper->listOfMonths($start);
        $years         = $helper->listOfYears($start);
        $title         = 'Reports';
        $mainTitleIcon = 'fa-line-chart';

        return view('reports.index', compact('years', 'months', 'title', 'mainTitleIcon'));
    }

    /**
     * @param string $year
     * @param string $month
     *
     * @return \Illuminate\View\View
     */
    public function month($year = '2014', $month = '1', ReportQueryInterface $query)
    {
        try {
            new Carbon($year . '-' . $month . '-01');
        } catch (Exception $e) {
            return View::make('error')->with('message', 'Invalid date.');
        }
        $date         = new Carbon($year . '-' . $month . '-01');
        $subTitle     = 'Report for ' . $date->format('F Y');
        $subTitleIcon = 'fa-calendar';
        $displaySum   = true; // to show sums in report.


        /**
         *
         * get income for month (date)
         *
         */

        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();

        /**
         * Start getIncomeForMonth DONE
         */
        $income = $query->incomeByPeriod($start, $end);
        /**
         * End getIncomeForMonth DONE
         */
        /**
         * Start getExpenseGroupedForMonth DONE
         */
        $set      = $query->journalsByExpenseAccount($start, $end);
        $expenses = Steam::makeArray($set);
        $expenses = Steam::sortArray($expenses);
        $expenses = Steam::limitArray($expenses, 10);
        /**
         * End getExpenseGroupedForMonth DONE
         */
        /**
         * Start getBudgetsForMonth DONE
         */
        $set                  = Auth::user()->budgets()
                                    ->leftJoin(
                                        'budget_limits', function (JoinClause $join) use ($date) {
                                        $join->on('budget_limits.budget_id', '=', 'budgets.id')->where('budget_limits.startdate', '=', $date->format('Y-m-d'));
                                    }
                                    )
                                    ->get(['budgets.*', 'budget_limits.amount as amount']);
        $budgets              = Steam::makeArray($set);
        $amountSet            = $query->journalsByBudget($start, $end);
        $amounts              = Steam::makeArray($amountSet);
        $budgets              = Steam::mergeArrays($budgets, $amounts);
        $budgets[0]['spent']  = isset($budgets[0]['spent']) ? $budgets[0]['spent'] : 0.0;
        $budgets[0]['amount'] = isset($budgets[0]['amount']) ? $budgets[0]['amount'] : 0.0;
        $budgets[0]['name']   = 'No budget';

        // find transactions to shared expense accounts, which are without a budget by default:
        $transfers = $query->sharedExpenses($start, $end);
        foreach ($transfers as $transfer) {
            $budgets[0]['spent'] += floatval($transfer->amount) * -1;
        }

        /**
         * End getBudgetsForMonth DONE
         */
        /**
         * Start getCategoriesForMonth DONE
         */
        // all categories.
        $result     = $query->journalsByCategory($start, $end);
        $categories = Steam::makeArray($result);

        // all transfers
        $result    = $query->sharedExpensesByCategory($start, $end);
        $transfers = Steam::makeArray($result);
        $merged    = Steam::mergeArrays($categories, $transfers);

        // sort.
        $sorted = Steam::sortNegativeArray($merged);

        // limit to $limit:
        $categories = Steam::limitArray($sorted, 10);
        /**
         * End getCategoriesForMonth DONE
         */
        /**
         * Start getAccountsForMonth
         */
        $list     = $query->accountList();
        $accounts = [];
        /** @var Account $account */
        foreach ($list as $account) {
            $id = intval($account->id);
            /** @noinspection PhpParamsInspection */
            $accounts[$id] = [
                'name'         => $account->name,
                'startBalance' => Steam::balance($account, $start),
                'endBalance'   => Steam::balance($account, $end)
            ];

            $accounts[$id]['difference'] = $accounts[$id]['endBalance'] - $accounts[$id]['startBalance'];
        }

        /**
         * End getAccountsForMonth
         */


        return View::make(
            'reports.month',
            compact(
                'income', 'expenses', 'budgets', 'accounts', 'categories',
                'date', 'subTitle', 'displaySum', 'subTitleIcon'
            )
        );
    }

    /**
     * @param $year
     *
     * @return $this
     */
    public function year($year, ReportHelperInterface $helper, ReportQueryInterface $query)
    {
        try {
            new Carbon('01-01-' . $year);
        } catch (Exception $e) {
            return View::make('error')->with('message', 'Invalid date.');
        }
        $date = new Carbon('01-01-' . $year);
        $end  = clone $date;
        $end->endOfYear();
        $title           = 'Reports';
        $subTitle        = $year;
        $subTitleIcon    = 'fa-bar-chart';
        $mainTitleIcon   = 'fa-line-chart';
        $balances        = $helper->yearBalanceReport($date);
        $groupedIncomes  = $query->journalsByRevenueAccount($date, $end);
        $groupedExpenses = $query->journalsByExpenseAccount($date, $end);

        //$groupedExpenses = $helper-> expensesGroupedByAccount($date, $end, 15);

        return View::make(
            'reports.year', compact('date', 'groupedIncomes', 'groupedExpenses', 'year', 'balances', 'title', 'subTitle', 'subTitleIcon', 'mainTitleIcon')
        );
    }


}

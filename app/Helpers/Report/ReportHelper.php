<?php

namespace FireflyIII\Helpers\Report;

use Auth;
use Carbon\Carbon;
use FireflyIII\Models\Account;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Session;

/**
 * Class ReportHelper
 *
 * @package FireflyIII\Helpers\Report
 */
class ReportHelper implements ReportHelperInterface
{

    /**
     * This methods fails to take in account transfers FROM shared accounts.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int    $limit
     *
     * @return Collection
     */
    public function expensesGroupedByAccount(Carbon $start, Carbon $end, $limit = 15)
    {
        $result  = $this->_queries->journalsByExpenseAccount($start, $end);
        $array   = $this->_helper->makeArray($result);
        $limited = $this->_helper->limitArray($array, $limit);

        return $limited;

    }

    /**
     * This method gets some kind of list for a monthly overview.
     *
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getBudgetsForMonth(Carbon $date)
    {
        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();
        // all budgets
        $set = \Auth::user()->budgets()
                    ->leftJoin(
                        'budget_limits', function (JoinClause $join) use ($date) {
                        $join->on('budget_limits.budget_id', '=', 'budgets.id')->where('budget_limits.startdate', '=', $date->format('Y-m-d'));
                    }
                    )
                    ->get(['budgets.*', 'budget_limits.amount as amount']);


        $budgets               = $this->_helper->makeArray($set);
        $amountSet             = $this->_queries->journalsByBudget($start, $end);
        $amounts               = $this->_helper->makeArray($amountSet);
        $combined              = $this->_helper->mergeArrays($budgets, $amounts);
        $combined[0]['spent']  = isset($combined[0]['spent']) ? $combined[0]['spent'] : 0.0;
        $combined[0]['amount'] = isset($combined[0]['amount']) ? $combined[0]['amount'] : 0.0;
        $combined[0]['name']   = 'No budget';

        // find transactions to shared expense accounts, which are without a budget by default:
        $transfers = $this->_queries->sharedExpenses($start, $end);
        foreach ($transfers as $transfer) {
            $combined[0]['spent'] += floatval($transfer->amount) * -1;
        }

        return $combined;
    }

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function listOfMonths(Carbon $date)
    {
        $start  = clone $date;
        $end    = Carbon::now();
        $months = [];
        while ($start <= $end) {
            $year = $start->format('Y');
            $months[$year][] = [
                'formatted' => $start->format('F Y'),
                'month'     => intval($start->format('m')),
                'year'      => intval($start->format('Y')),
            ];
            $start->addMonth();
        }

        return $months;
    }

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function listOfYears(Carbon $date)
    {
        $start = clone $date;
        $end   = Carbon::now();
        $years = [];
        while ($start <= $end) {
            $years[] = $start->format('Y');
            $start->addYear();
        }

        return $years;
    }

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function yearBalanceReport(Carbon $date)
    {
        $start            = clone $date;
        $end              = clone $date;
        $sharedAccounts   = [];
        $sharedCollection = \Auth::user()->accounts()
                                 ->leftJoin('account_meta', 'account_meta.account_id', '=', 'accounts.id')
                                 ->where('account_meta.name', '=', 'accountRole')
                                 ->where('account_meta.data', '=', json_encode('sharedAsset'))
                                 ->get(['accounts.id']);

        foreach ($sharedCollection as $account) {
            $sharedAccounts[] = $account->id;
        }

        $accounts = \Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account'])->orderBy('accounts.name','ASC')->get(['accounts.*'])->filter(
            function (Account $account) use ($sharedAccounts) {
                if (!in_array($account->id, $sharedAccounts)) {
                    return $account;
                }

                return null;
            }
        );
        $report   = [];
        $start->startOfYear()->subDay();
        $end->endOfYear();

        foreach ($accounts as $account) {
            $report[] = [
                'start'   => \Steam::balance($account, $start),
                'end'     => \Steam::balance($account, $end),
                'account' => $account,
                'shared'  => $account->accountRole == 'sharedAsset'
            ];
        }

        return $report;
    }
}
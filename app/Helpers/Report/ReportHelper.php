<?php

namespace FireflyIII\Helpers\Report;

use App;
use Auth;
use Carbon\Carbon;
use FireflyIII\Models\Account;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Steam;

/**
 * Class ReportHelper
 *
 * @package FireflyIII\Helpers\Report
 */
class ReportHelper implements ReportHelperInterface
{

    /**
     * This method gets some kind of list for a monthly overview.
     *
     * @param Carbon $date
     * @param bool   $showSharedReports
     *
     * @return Collection
     */
    public function getBudgetsForMonth(Carbon $date, $showSharedReports = false)
    {
        /** @var \FireflyIII\Helpers\Report\ReportQueryInterface $query */
        $query = App::make('FireflyIII\Helpers\Report\ReportQueryInterface');

        $start = clone $date;
        $start->startOfMonth();
        $end = clone $date;
        $end->endOfMonth();
        $set = Auth::user()->budgets()->orderBy('budgets.name', 'ASC')
                   ->leftJoin(
                       'budget_limits', function (JoinClause $join) use ($date) {
                       $join->on('budget_limits.budget_id', '=', 'budgets.id')->where('budget_limits.startdate', '=', $date->format('Y-m-d'));
                   }
                   )
                   ->get(['budgets.*', 'budget_limits.amount as queryAmount']);

        $budgets                   = Steam::makeArray($set);
        $amountSet                 = $query->journalsByBudget($start, $end, $showSharedReports);
        $amounts                   = Steam::makeArray($amountSet);
        $budgets                   = Steam::mergeArrays($budgets, $amounts);
        $budgets[0]['spent']       = isset($budgets[0]['spent']) ? $budgets[0]['spent'] : 0.0;
        $budgets[0]['queryAmount'] = isset($budgets[0]['queryAmount']) ? $budgets[0]['queryAmount'] : 0.0;
        $budgets[0]['name']        = 'No budget';

        // find transactions to shared asset accounts, which are without a budget by default:
        // which is only relevant when shared asset accounts are hidden.
        if ($showSharedReports === false) {
            $transfers = $query->sharedExpenses($start, $end)->sum('queryAmount');
            $budgets[0]['spent'] += floatval($transfers) * -1;
        }

        return $budgets;
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
            $year            = $start->year;
            $months[$year][] = [
                'formatted' => $start->format('F Y'),
                'month'     => $start->month,
                'year'      => $year,
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
            $years[] = $start->year;
            $start->addYear();
        }
        $years[] = Carbon::now()->year;
        // force the current year.
        $years = array_unique($years);

        return $years;
    }

    /**
     * @param Carbon $date
     * @param bool   $showSharedReports
     *
     * @return array
     */
    public function yearBalanceReport(Carbon $date, $showSharedReports = false)
    {
        $start          = clone $date;
        $end            = clone $date;
        $sharedAccounts = [];
        if ($showSharedReports === false) {
            $sharedCollection = Auth::user()->accounts()
                                     ->leftJoin('account_meta', 'account_meta.account_id', '=', 'accounts.id')
                                     ->where('account_meta.name', '=', 'accountRole')
                                     ->where('account_meta.data', '=', json_encode('sharedAsset'))
                                     ->get(['accounts.id']);

            foreach ($sharedCollection as $account) {
                $sharedAccounts[] = $account->id;
            }
        }

        $accounts = Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account'])->orderBy('accounts.name', 'ASC')->get(['accounts.*'])
                        ->filter(
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
            $startBalance = Steam::balance($account, $start);
            $endBalance   = Steam::balance($account, $end);
            $report[]     = [
                'start'   => $startBalance,
                'end'     => $endBalance,
                'hide'    => ($startBalance == 0 && $endBalance == 0),
                'account' => $account,
                'shared'  => $account->accountRole == 'sharedAsset'
            ];
        }

        return $report;
    }
}

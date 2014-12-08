<?php

namespace FireflyIII\Report;

use Carbon\Carbon;
use FireflyIII\Database\Account as AccountRepository;

/**
 * Class Report
 *
 * @package FireflyIII\Report
 */
class Report implements ReportInterface
{
    /** @var AccountRepository */
    protected $_accounts;

    /**
     * @param AccountRepository $accounts
     */
    public function __construct(AccountRepository $accounts)
    {
        $this->_accounts = $accounts;

    }

    /**
     * @param Carbon $date
     * @param string $direction
     *
     * @return mixed
     */
    public function groupByRevenue(Carbon $date, $direction = 'income')
    {
        $operator = $direction == 'income' ? '<' : '>';
        $type     = $direction == 'income' ? 'Deposit' : 'Withdrawal';
        $order    = $direction == 'income' ? 'ASC' : 'DESC';
        $start    = clone $date;
        $end      = clone $date;
        $start->startOfYear();
        $end->endOfYear();

        // TODO remove shared accounts
        return \TransactionJournal::
        leftJoin('transaction_types', 'transaction_journals.transaction_type_id', '=', 'transaction_types.id')
                                  ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                  ->leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
                                  ->where('transaction_types.type', '=', $type)
                                  ->where('transactions.amount', $operator, 0)
                                  ->before($end)
                                  ->after($start)
                                  ->groupBy('accounts.id')
                                  ->where('transaction_journals.user_id', \Auth::user()->id)
                                  ->orderBy('sum', $order)
                                  ->take(10)
                                  ->get(['accounts.name', 'transactions.account_id', \DB::Raw('SUM(`transactions`.`amount`) as `sum`')]);

    }

    /**
     * @param Carbon $start
     *
     * @return array
     */
    public function listOfMonths(Carbon $start)
    {
        $end    = Carbon::now();
        $months = [];
        while ($start <= $end) {
            $months[] = [
                'formatted' => $start->format('F Y'),
                'month'     => intval($start->format('m')),
                'year'      => intval($start->format('Y')),
            ];
            $start->addMonth();
        }

        return $months;
    }

    /**
     * @param Carbon $start
     *
     * @return array
     */
    public function listOfYears(Carbon $start)
    {
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
        $start = clone $date;
        $end   = clone $date;
        // TODO filter accounts, no shared accounts.
        $accounts = $this->_accounts->getAssetAccounts();
        $report   = [];
        $start->startOfYear();
        $end->endOfYear();

        foreach ($accounts as $account) {
            $report[] = [
                'start'   => \Steam::balance($account, $start),
                'end'     => \Steam::balance($account, $end),
                'account' => $account,
                'shared'  => $account->accountRole == 'sharedExpense'
            ];
        }

        return $report;
    }

} 
<?php

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Account as AccountCollection;
use FireflyIII\Helpers\Collection\Expense;
use FireflyIII\Helpers\Collection\Income;
use FireflyIII\Models\Account;

/**
 * Class ReportHelper
 *
 * @package FireflyIII\Helpers\Report
 */
class ReportHelper implements ReportHelperInterface
{

    /** @var ReportQueryInterface */
    protected $query;

    /**
     * @param ReportHelperInterface $helper
     */
    public function __construct(ReportQueryInterface $query)
    {
        $this->query = $query;

    }


    /**
     * This method generates a full report for the given period on all
     * the users asset and cash accounts.
     *
     * @param Carbon $date
     * @param Carbon $end
     * @param        $shared
     *
     * @return Account
     */
    public function getAccountReport(Carbon $date, Carbon $end, $shared)
    {


        $accounts = $this->query->getAllAccounts($date, $end, $shared);
        $start    = 0;
        $end      = 0;
        $diff     = 0;

        // summarize:
        foreach ($accounts as $account) {
            $start += $account->startBalance;
            $end += $account->endBalance;
            $diff += ($account->endBalance - $account->startBalance);
        }

        $object = new AccountCollection;
        $object->setStart($start);
        $object->setEnd($end);
        $object->setDifference($diff);
        $object->setAccounts($accounts);

        return $object;
    }

    /**
     * Get a full report on the users expenses during the period.
     *
     * @param Carbon  $start
     * @param Carbon  $end
     * @param boolean $shared
     *
     * @return Expense
     */
    public function getExpenseReport($start, $end, $shared)
    {
        $object = new Expense;
        $set    = $this->query->expenseInPeriod($start, $end, $shared);
        foreach ($set as $entry) {
            $object->addToTotal($entry->queryAmount);
            $object->addOrCreateExpense($entry);
        }

        return $object;
    }

    /**
     * Get a full report on the users incomes during the period.
     *
     * @param Carbon  $start
     * @param Carbon  $end
     * @param boolean $shared
     *
     * @return Income
     */
    public function getIncomeReport($start, $end, $shared)
    {
        $object = new Income;
        $set    = $this->query->incomeInPeriod($start, $end, $shared);
        foreach ($set as $entry) {
            $object->addToTotal($entry->queryAmount);
            $object->addOrCreateIncome($entry);
        }

        return $object;
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
                'formatted' => $start->formatLocalized('%B %Y'),
                'month'     => $start->month,
                'year'      => $year,
            ];
            $start->addMonth();
        }

        return $months;
    }
}

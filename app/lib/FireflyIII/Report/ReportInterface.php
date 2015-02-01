<?php

namespace FireflyIII\Report;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Interface ReportInterface
 *
 * @package FireflyIII\Report
 */
interface ReportInterface
{
    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param int    $limit
     *
     * @return Collection
     */
    public function expensesGroupedByAccount(Carbon $start, Carbon $end, $limit = 15);

    /**
     * Gets all the users shared and non-shared accounts combined with various meta-data
     * to display the amount of money spent that month compared to what's been spend within
     * budgets.
     *
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getAccountListBudgetOverview(Carbon $date);

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function getAccountsForMonth(Carbon $date);

    /**
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getBudgetsForMonth(Carbon $date);

    /**
     * @param Carbon $date
     * @param int    $limit
     *
     * @return array
     */
    public function getCategoriesForMonth(Carbon $date, $limit = 15);

    /**
     * @param Carbon $date
     * @param int    $limit
     *
     * @return Collection
     */
    public function getExpenseGroupedForMonth(Carbon $date, $limit = 15);

    /**
     * @param Carbon $date
     * @param bool   $shared
     *
     * @return Collection
     */
    public function getIncomeForMonth(Carbon $date, $shared = false);

    /**
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getPiggyBanksForMonth(Carbon $date);

    /**
     * @param Carbon $start
     *
     * @return array
     */
    public function listOfMonths(Carbon $start);

    /**
     * @param Carbon $start
     *
     * @return array
     */
    public function listOfYears(Carbon $start);

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function revenueGroupedByAccount(Carbon $start, Carbon $end);

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function yearBalanceReport(Carbon $date);
} 

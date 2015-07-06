<?php

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use Illuminate\Support\Collection;

/**
 * Interface ReportQueryInterface
 *
 * @package FireflyIII\Helpers\Report
 */
interface ReportQueryInterface
{

    /**
     * See ReportQueryInterface::incomeInPeriodCorrected
     *
     * This method returns all "expense" journals in a certain period, which are both transfers to a shared account
     * and "ordinary" withdrawals. The query used is almost equal to ReportQueryInterface::journalsByRevenueAccount but it does
     * not group and returns different fields.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     *
     */
    public function expenseInPeriodCorrected(Carbon $start, Carbon $end, $includeShared = false);

    /**
     * Get a users accounts combined with various meta-data related to the start and end date.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     */
    public function getAllAccounts(Carbon $start, Carbon $end, $includeShared = false);

    /**
     * This method works the same way as ReportQueryInterface::incomeInPeriod does, but instead of returning results
     * will simply list the transaction journals only. This should allow any follow up counting to be accurate with
     * regards to tags.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     */
    public function incomeInPeriodCorrected(Carbon $start, Carbon $end, $includeShared = false);

    /**
     * Covers tags as well.
     *
     * @param Account $account
     * @param Budget  $budget
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return float
     */
    public function spentInBudgetCorrected(Account $account, Budget $budget, Carbon $start, Carbon $end);

    /**
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return string
     */
    public function spentNoBudget(Account $account, Carbon $start, Carbon $end);


}

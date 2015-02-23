<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 22/02/15
 * Time: 18:30
 */

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Interface ReportQueryInterface
 *
 * @package FireflyIII\Helpers\Report
 */
interface ReportQueryInterface
{

    /**
     * This query retrieves a list of accounts that are active and not shared.
     *
     * @return Collection
     */
    public function accountList();

    /**
     * This method returns all "income" journals in a certain period, which are both transfers from a shared account
     * and "ordinary" deposits. The query used is almost equal to ReportQueryInterface::journalsByRevenueAccount but it does
     * not group and returns different fields.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function incomeByPeriod(Carbon $start, Carbon $end);

    /**
     * Gets a list of expenses grouped by the budget they were filed under.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function journalsByBudget(Carbon $start, Carbon $end);

    /**
     * Gets a list of categories and the expenses therein, grouped by the relevant category.
     * This result excludes transfers to shared accounts which are expenses, technically.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function journalsByCategory(Carbon $start, Carbon $end);

    /**
     * Gets a list of expense accounts and the expenses therein, grouped by that expense account.
     * This result excludes transfers to shared accounts which are expenses, technically.
     *
     * So now it will include them!
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function journalsByExpenseAccount(Carbon $start, Carbon $end);

    /**
     * This method returns all deposits into asset accounts, grouped by the revenue account,
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function journalsByRevenueAccount(Carbon $start, Carbon $end);

    /**
     * With an equally misleading name, this query returns are transfers to shared accounts. These are considered
     * expenses.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function sharedExpenses(Carbon $start, Carbon $end);

    /**
     * With a slightly misleading name, this query returns all transfers to shared accounts
     * which are technically expenses, since it won't be just your money that gets spend.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function sharedExpensesByCategory(Carbon $start, Carbon $end);
}
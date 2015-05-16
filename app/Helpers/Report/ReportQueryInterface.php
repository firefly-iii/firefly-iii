<?php

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use Illuminate\Support\Collection;

/**
 * Interface ReportQueryInterface
 *
 * @package FireflyIII\Helpers\Report
 */
interface ReportQueryInterface
{

    /**
     * This method will get a list of all expenses in a certain time period that have no budget
     * and are balanced by a transfer to make up for it.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return Collection
     */
    public function balancedTransactionsList(Account $account, Carbon $start, Carbon $end);

    /**
     * This method will get the sum of all expenses in a certain time period that have no budget
     * and are balanced by a transfer to make up for it.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return float
     */
    public function balancedTransactionsSum(Account $account, Carbon $start, Carbon $end);

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
     * Grabs a summary of all expenses grouped by budget, related to the account.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return mixed
     */
    public function getBudgetSummary(Account $account, Carbon $start, Carbon $end);

    /**
     * Get a list of transaction journals that have no budget, filtered for the specified account
     * and the specified date range.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return Collection
     */
    public function getTransactionsWithoutBudget(Account $account, Carbon $start, Carbon $end);

    /**
     * This method returns all "income" journals in a certain period, which are both transfers from a shared account
     * and "ordinary" deposits. The query used is almost equal to ReportQueryInterface::journalsByRevenueAccount but it does
     * not group and returns different fields.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     *
     */
    public function incomeInPeriod(Carbon $start, Carbon $end, $includeShared = false);

    /**
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
    public function expenseInPeriod(Carbon $start, Carbon $end, $includeShared = false);

    /**
     * Gets a list of expenses grouped by the budget they were filed under.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     */
    public function journalsByBudget(Carbon $start, Carbon $end, $includeShared = false);

    /**
     * Gets a list of categories and the expenses therein, grouped by the relevant category.
     * This result excludes transfers to shared accounts which are expenses, technically.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     */
    public function journalsByCategory(Carbon $start, Carbon $end, $includeShared = false);

    /**
     * Gets a list of expense accounts and the expenses therein, grouped by that expense account.
     * This result excludes transfers to shared accounts which are expenses, technically.
     *
     * So now it will include them!
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     *
     */
    public function journalsByExpenseAccount(Carbon $start, Carbon $end, $includeShared = false);

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

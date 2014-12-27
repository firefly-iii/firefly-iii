<?php

namespace FireflyIII\Report;

use Carbon\Carbon;
use Illuminate\Support\Collection;


/**
 * Interface ReportQueryInterface
 *
 * @package FireflyIII\Report
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
     * Gets a list of expenses grouped by the budget they were filed under.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function journalsByBudget(Carbon $start, Carbon $end);

    /**
     * Gets a list of all budgets and if present, the amount of the current BudgetLimit
     * as well
     *
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getAllBudgets(Carbon $date);

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
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function journalsByExpenseAccount(Carbon $start, Carbon $end);


    /**
     * With a slightly misleading name, this query returns all transfers to shared accounts
     * grouped by category (which are technically expenses, since it won't be just your money that gets spend).
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function sharedExpensesByCategory(Carbon $start, Carbon $end);

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

}
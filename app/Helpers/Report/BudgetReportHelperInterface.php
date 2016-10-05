<?php
/**
 * BudgetReportHelperInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Helpers\Report;


use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Budget as BudgetCollection;
use Illuminate\Support\Collection;

/**
 * Interface BudgetReportHelperInterface
 *
 * @package FireflyIII\Helpers\Report
 */
interface BudgetReportHelperInterface
{
    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function budgetYearOverview(Carbon $start, Carbon $end, Collection $accounts): Collection;

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return BudgetCollection
     */
    public function getBudgetReport(Carbon $start, Carbon $end, Collection $accounts): BudgetCollection;

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function getBudgetsWithExpenses(Carbon $start, Carbon $end, Collection $accounts): Collection;

}

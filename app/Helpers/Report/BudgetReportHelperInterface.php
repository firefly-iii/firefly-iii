<?php
/**
 * BudgetReportHelperInterface.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

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
     * @return BudgetCollection
     */
    public function getBudgetReport(Carbon $start, Carbon $end, Collection $accounts);
}
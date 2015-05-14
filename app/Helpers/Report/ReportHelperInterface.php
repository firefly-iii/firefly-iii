<?php

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Interface ReportHelperInterface
 *
 * @package FireflyIII\Helpers\Report
 */
interface ReportHelperInterface
{

    /**
     * This method gets some kind of list for a monthly overview.
     *
     * @param Carbon $date
     *
     * @return Collection
     */
    public function getBudgetsForMonth(Carbon $date);

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function listOfMonths(Carbon $date);

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function listOfYears(Carbon $date);

    /**
     * @param Carbon $date
     * @param bool   $showSharedReports
     *
     * @return array
     */
    public function yearBalanceReport(Carbon $date, $showSharedReports = false);
}

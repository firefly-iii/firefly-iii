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
     * @param bool   $includeShared
     *
     * @return Collection
     */
    public function getBudgetsForMonth(Carbon $date, $includeShared = false);

    /**
     * @param Carbon $date
     *
     * @return array
     */
    public function listOfMonths(Carbon $date);

    /**
     * @param Carbon $date
     * @param bool   $includeShared
     *
     * @return array
     */
    public function yearBalanceReport(Carbon $date, $includeShared = false);
}

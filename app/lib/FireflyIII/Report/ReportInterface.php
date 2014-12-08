<?php

namespace FireflyIII\Report;

use Carbon\Carbon;

/**
 * Interface ReportInterface
 *
 * @package FireflyIII\Report
 */
interface ReportInterface
{
    /**
     * @param Carbon $date
     * @param string $direction
     *
     * @return mixed
     */
    public function groupByRevenue(Carbon $date, $direction = 'income');

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
     * @param Carbon $date
     *
     * @return array
     */
    public function yearBalanceReport(Carbon $date);
} 
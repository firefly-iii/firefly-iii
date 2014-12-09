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
     * @param int $limit
     *
     * @return Collection
     */
    public function revenueGroupedByAccount(Carbon $start, Carbon $end, $limit = 15);

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param int $limit
     *
     * @return Collection
     */
    public function expensesGroupedByAccount(Carbon $start, Carbon $end, $limit = 15);

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
<?php

namespace FireflyIII\Generator\Chart\Report;

use Illuminate\Support\Collection;

/**
 * Interface ReportChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Report
 */
interface ReportChartGenerator
{

    /**
 * @param Collection $entries
 *
 * @return array
 */
    public function yearInOut(Collection $entries);

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function multiYearInOut(Collection $entries);

    /**
     * @param string $income
     * @param string $expense
     * @param int    $count
     *
     * @return array
     */
    public function yearInOutSummarized($income, $expense, $count);

    /**
     * @param string $income
     * @param string $expense
     * @param int    $count
     *
     * @return array
     */
    public function multiYearInOutSummarized($income, $expense, $count);

}

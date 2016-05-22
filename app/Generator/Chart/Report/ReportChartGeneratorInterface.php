<?php
/**
 * ReportChartGeneratorInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Generator\Chart\Report;

use Illuminate\Support\Collection;

/**
 * Interface ReportChartGeneratorInterface
 *
 * @package FireflyIII\Generator\Chart\Report
 */
interface ReportChartGeneratorInterface
{

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function multiYearInOut(Collection $entries): array;

    /**
     * @param string $income
     * @param string $expense
     * @param int    $count
     *
     * @return array
     */
    public function multiYearInOutSummarized(string $income, string $expense, int $count): array;

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function netWorth(Collection $entries) : array;

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function yearInOut(Collection $entries): array;

    /**
     * @param string $income
     * @param string $expense
     * @param int    $count
     *
     * @return array
     */
    public function yearInOutSummarized(string $income, string $expense, int $count): array;

}

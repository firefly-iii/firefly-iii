<?php
/**
 * BudgetChartGeneratorInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Generator\Chart\Budget;

use Illuminate\Support\Collection;

/**
 * Interface BudgetChartGeneratorInterface
 *
 * @package FireflyIII\Generator\Chart\Budget
 */
interface BudgetChartGeneratorInterface
{

    /**
     * @param Collection $entries
     * @param string     $dateFormat
     *
     * @return array
     */
    public function budgetLimit(Collection $entries, string $dateFormat): array;

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function frontpage(Collection $entries): array;

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function multiYear(Collection $entries): array;

    /**
     * @param Collection $entries
     * @param string     $viewRange
     *
     * @return array
     */
    public function period(Collection $entries, string $viewRange) : array;

    /**
     * @param Collection $budgets
     * @param Collection $entries
     *
     * @return array
     */
    public function year(Collection $budgets, Collection $entries): array;

}

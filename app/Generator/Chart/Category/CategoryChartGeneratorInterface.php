<?php
/**
 * CategoryChartGeneratorInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Generator\Chart\Category;

use Illuminate\Support\Collection;

/**
 * Interface CategoryChartGeneratorInterface
 *
 * @package FireflyIII\Generator\Chart\Category
 */
interface CategoryChartGeneratorInterface
{

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function all(Collection $entries): array;

    /**
     * @param Collection $categories
     * @param Collection $entries
     *
     * @return array
     */
    public function earnedInPeriod(Collection $categories, Collection $entries): array;

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
     *
     * @return array
     */
    public function period(Collection $entries): array;

    /**
     * @param Collection $categories
     * @param Collection $entries
     *
     * @return array
     */
    public function spentInPeriod(Collection $categories, Collection $entries): array;
}

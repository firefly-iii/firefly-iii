<?php
declare(strict_types = 1);
/**
 * CategoryChartGeneratorInterface.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

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
    public function all(Collection $entries);

    /**
     * @param Collection $categories
     * @param Collection $entries
     *
     * @return array
     */
    public function earnedInPeriod(Collection $categories, Collection $entries);

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function frontpage(Collection $entries);

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function multiYear(Collection $entries);

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function period(Collection $entries);

    /**
     * @param Collection $categories
     * @param Collection $entries
     *
     * @return array
     */
    public function spentInPeriod(Collection $categories, Collection $entries);
}

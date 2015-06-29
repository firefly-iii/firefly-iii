<?php

namespace FireflyIII\Generator\Chart\Category;

use Illuminate\Support\Collection;

/**
 * Interface CategoryChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Category
 */
interface CategoryChartGenerator
{

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function all(Collection $entries);

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
    public function month(Collection $entries);


    /**
     * @param Collection $categories
     * @param Collection $entries
     *
     * @return array
     */
    public function year(Collection $categories, Collection $entries);
}

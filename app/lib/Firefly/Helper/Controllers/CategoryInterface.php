<?php

namespace Firefly\Helper\Controllers;


use Carbon\Carbon;

/**
 * Interface CategoryInterface
 *
 * @package Firefly\Helper\Controllers
 */
interface CategoryInterface
{


    /**
     * @param \Category $category
     * @param Carbon    $start
     * @param Carbon    $end
     *
     * @return mixed
     */
    public function journalsInRange(\Category $category, Carbon $start, Carbon $end);
} 
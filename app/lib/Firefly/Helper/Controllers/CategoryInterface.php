<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 30-7-14
 * Time: 10:57
 */

namespace Firefly\Helper\Controllers;


use Carbon\Carbon;

interface CategoryInterface {


    public function journalsInRange(\Category $category, Carbon $start, Carbon $end);
} 
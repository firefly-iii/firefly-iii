<?php

namespace Firefly\Helper\Controllers;


use Carbon\Carbon;

interface CategoryInterface
{


    public function journalsInRange(\Category $category, Carbon $start, Carbon $end);
} 
<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 30-7-14
 * Time: 10:57
 */

namespace Firefly\Helper\Controllers;


use Carbon\Carbon;

class Category implements CategoryInterface
{
    public function journalsInRange(\Category $category, Carbon $start, Carbon $end)
    {
        return $category->transactionjournals()->
            with(['transactions','transactions.account','transactiontype','components'])->

            orderBy('date','DESC')->orderBy('id','DESC')->before($end)->after($start)->get();

    }
} 
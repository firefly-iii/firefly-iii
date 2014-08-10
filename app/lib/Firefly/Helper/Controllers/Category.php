<?php

namespace Firefly\Helper\Controllers;


use Carbon\Carbon;

/**
 * Class Category
 *
 * @package Firefly\Helper\Controllers
 */
class Category implements CategoryInterface
{
    /**
     * @param \Category $category
     * @param Carbon    $start
     * @param Carbon    $end
     *
     * @return mixed
     */
    public function journalsInRange(\Category $category, Carbon $start, Carbon $end)
    {
        return $category->transactionjournals()->with(
            ['transactions', 'transactions.account', 'transactiontype', 'components']
        )->orderBy('date', 'DESC')->orderBy('id', 'DESC')->before($end)->after($start)->get();

    }
} 
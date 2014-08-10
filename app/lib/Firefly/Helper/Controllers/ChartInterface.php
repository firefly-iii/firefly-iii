<?php

namespace Firefly\Helper\Controllers;


use Carbon\Carbon;

/**
 * Interface ChartInterface
 *
 * @package Firefly\Helper\Controllers
 */
interface ChartInterface
{

    /**
     * @param \Account $account
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return mixed
     */
    public function account(\Account $account, Carbon $start, Carbon $end);

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return mixed
     */
    public function categories(Carbon $start, Carbon $end);

    /**
     * @param Carbon $start
     *
     * @return mixed
     */
    public function budgets(Carbon $start);

    /**
     * @param \Account $account
     * @param Carbon   $date
     *
     * @return mixed
     */
    public function accountDailySummary(\Account $account, Carbon $date);

    /**
     * @param \Category $category
     * @param           $range
     * @param Carbon    $start
     * @param Carbon    $end
     *
     * @return mixed
     */
    public function categoryShowChart(\Category $category, $range, Carbon $start, Carbon $end);
}
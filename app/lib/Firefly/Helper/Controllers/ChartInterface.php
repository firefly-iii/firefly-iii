<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 29-7-14
 * Time: 10:42
 */

namespace Firefly\Helper\Controllers;


use Carbon\Carbon;

interface ChartInterface
{

    public function account(\Account $account, Carbon $start, Carbon $end);

    public function categories(Carbon $start, Carbon $end);

    public function budgets(Carbon $start);

    public function accountDailySummary(\Account $account, Carbon $date);
}
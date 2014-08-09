<?php

namespace Firefly\Helper\Controllers;


use Carbon\Carbon;

interface ChartInterface
{

    public function account(\Account $account, Carbon $start, Carbon $end);

    public function categories(Carbon $start, Carbon $end);

    public function budgets(Carbon $start);

    public function accountDailySummary(\Account $account, Carbon $date);

    public function categoryShowChart(\Category $category, $range, Carbon $start, Carbon $end);
}
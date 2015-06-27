<?php

namespace FireflyIII\Generator\Chart\Account;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Interface AccountChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Account
 */
interface AccountChartGenerator
{

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function all(Collection $accounts, Carbon $start, Carbon $end);
}
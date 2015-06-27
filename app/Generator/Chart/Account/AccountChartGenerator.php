<?php

namespace FireflyIII\Generator\Chart\Account;
use FireflyIII\Models\Account;
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

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function frontpage(Collection $accounts, Carbon $start, Carbon $end);

    /**
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return array
     */
    public function single(Account $account, Carbon $start, Carbon $end);
}
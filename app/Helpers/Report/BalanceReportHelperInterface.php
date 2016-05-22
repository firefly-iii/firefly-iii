<?php
/**
 * BalanceReportHelperInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Balance;
use Illuminate\Support\Collection;


/**
 * Interface BalanceReportHelperInterface
 *
 * @package FireflyIII\Helpers\Report
 */
interface BalanceReportHelperInterface
{
    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Balance
     */
    public function getBalanceReport(Carbon $start, Carbon $end, Collection $accounts): Balance;
}

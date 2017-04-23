<?php
/**
 * BalanceReportHelperInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

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
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Balance
     */
    public function getBalanceReport(Collection $accounts, Carbon $start, Carbon $end): Balance;
}

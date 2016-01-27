<?php
/**
 * AccountReportHelperInterface.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Account as AccountCollection;
use Illuminate\Support\Collection;


/**
 * Interface AccountReportHelperInterface
 *
 * @package FireflyIII\Helpers\Report
 */
interface AccountReportHelperInterface
{
    /**
     * This method generates a full report for the given period on all
     * given accounts
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return AccountCollection
     */
    public function getAccountReport(Carbon $start, Carbon $end, Collection $accounts);

}
<?php
/**
 * FiscalHelperInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers;

use Carbon\Carbon;

/**
 * Interface FiscalHelperInterface
 *
 * @package FireflyIII\Helpers
 */
interface FiscalHelperInterface
{

    /**
     * This method produces a clone of the Carbon date object passed, checks preferences
     * and calculates the last day of the fiscal year.
     *
     * @param Carbon $date
     *
     * @return Carbon date object
     */
    public function endOfFiscalYear(Carbon $date): Carbon;

    /**
     * This method produces a clone of the Carbon date object passed, checks preferences
     * and calculates the first day of the fiscal year.
     *
     * @param Carbon $date
     *
     * @return Carbon date object
     */
    public function startOfFiscalYear(Carbon $date): Carbon;

}

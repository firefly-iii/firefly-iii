<?php
declare(strict_types = 1);

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

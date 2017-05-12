<?php
/**
 * FiscalHelper.php
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
use Preferences;

/**
 * Class FiscalHelper
 *
 * @package FireflyIII\Helpers
 */
class FiscalHelper implements FiscalHelperInterface
{

    /** @var bool */
    protected $useCustomFiscalYear;

    /**
     * FiscalHelper constructor.
     *
     *
     */
    public function __construct()
    {
        $this->useCustomFiscalYear = Preferences::get('customFiscalYear', false)->data;
    }

    /**
     * @param Carbon $date
     *
     * @return Carbon date object
     */
    public function endOfFiscalYear(Carbon $date): Carbon
    {
        // get start of fiscal year for passed date
        $endDate = $this->startOfFiscalYear($date);
        if ($this->useCustomFiscalYear === true) {
            // add 1 year and sub 1 day
            $endDate->addYear();
            $endDate->subDay();

            return $endDate;
        }
        $endDate->endOfYear();


        return $endDate;
    }

    /**
     * @param Carbon $date
     *
     * @return Carbon date object
     */
    public function startOfFiscalYear(Carbon $date): Carbon
    {
        // get start mm-dd. Then create a start date in the year passed.
        $startDate = clone $date;
        if ($this->useCustomFiscalYear === true) {
            $prefStartStr = Preferences::get('fiscalYearStart', '01-01')->data;
            list($mth, $day) = explode('-', $prefStartStr);
            $startDate->month(intval($mth))->day(intval($day));

            // if start date is after passed date, sub 1 year.
            if ($startDate > $date) {
                $startDate->subYear();
            }

            return $startDate;
        }
        $startDate->startOfYear();

        return $startDate;
    }
}

<?php

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
     * @codeCoverageIgnore
     *
     */
    public function __construct()
    {
        if (Preferences::get('customFiscalYear', 0)->data) {
            $this->useCustomFiscalYear = true;
        } else {
            $this->useCustomFiscalYear = false;
        }
    }

    /**
     * @param Carbon $date
     *
     * @return Carbon date object
     */
    public function startOfFiscalYear(Carbon $date)
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
        } else {
            $startDate->startOfYear();
        }
        return $startDate;
    }

    /**
     * @param Carbon $date
     *
     * @return Carbon date object
     */
    public function endOfFiscalYear(Carbon $date)
    {
        // get start of fiscal year for passed date
        $endDate = $this->startOfFiscalYear($date);
        if ($this->useCustomFiscalYear === true) {
            // add 1 year and sub 1 day
            $endDate->addYear();
            $endDate->subDay();
        } else {
            $endDate->endOfYear();
        }


        return $endDate;
    }
}

<?php

namespace FireflyIII\Support;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;

/**
 * Class Navigation
 *
 * @package FireflyIII\Support
 */
class Navigation
{


    /**
     * @param Carbon         $date
     * @param                $repeatFrequency
     *
     * @return string
     * @throws FireflyException
     */
    public function periodShow(Carbon $date, $repeatFrequency)
    {
        $formatMap = [
            'daily'   => 'j F Y',
            'week'    => '\W\e\e\k W, Y',
            'weekly'  => '\W\e\e\k W, Y',
            'quarter' => 'F Y',
            'month'   => 'F Y',
            'monthly' => 'F Y',
            'year'    => 'Y',
            'yearly'  => 'Y',

        ];
        if (isset($formatMap[$repeatFrequency])) {
            return $date->format($formatMap[$repeatFrequency]);
        }
        throw new FireflyException('No date formats for frequency "' . $repeatFrequency . '"!');
    }


    /**
     * @param        $range
     * @param Carbon $date
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function jumpToNext($range, Carbon $date)
    {
        switch ($range) {
            case '1D':
                $date->endOfDay()->addDay();
                break;
            case '1W':
                $date->endOfWeek()->addDay()->startOfWeek();
                break;
            case '1M':
                $date->endOfMonth()->addDay()->startOfMonth();
                break;
            case '3M':
                $date->lastOfQuarter()->addDay();
                break;
            case '6M':
                if (intval($date->format('m')) >= 7) {
                    $date->startOfYear()->addYear();
                } else {
                    $date->startOfYear()->addMonths(6);
                }
                break;
            case '1Y':
                $date->startOfYear()->addYear();
                break;
            default:
                throw new FireflyException('Cannot do _next() on ' . $range);
                break;
        }

        return $date;
    }

    /**
     * @param        $range
     * @param Carbon $date
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function jumpToPrevious($range, Carbon $date)
    {
        $functionMap = [
            '1D' => 'Day',
            '1W' => 'Week',
            '1M' => 'Month',
            '1Y' => 'Year'
        ];

        if (isset($functionMap[$range])) {
            $startFunction = 'startOf' . $functionMap[$range];
            $subFunction   = 'sub' . $functionMap[$range];
            $date->$startFunction()->$subFunction();

            return $date;
        }
        if ($range == '3M') {
            $date->firstOfQuarter()->subMonths(3)->firstOfQuarter();

            return $date;
        }
        if ($range == '6M') {
            $month = intval($date->format('m'));
            $date->startOfYear();
            if ($month <= 6) {
                $date->subMonths(6);
            }

            return $date;
        }
        throw new FireflyException('Cannot do _previous() on ' . $range);
    }

    /**
     * @param        $range
     * @param Carbon $date
     *
     * @return string
     * @throws FireflyException
     */
    public function periodName($range, Carbon $date)
    {
        $formatMap = [
            '1D' => 'jS F Y',
            '1W' => '\w\e\ek W, Y',
            '1M' => 'F Y',
            '1Y' => 'Y',
        ];
        if (isset($formatMap[$range])) {
            return $date->format($formatMap[$range]);
        }
        if ($range == '3M') {
            $month = intval($date->format('m'));

            return 'Q' . ceil(($month / 12) * 4) . ' ' . $date->format('Y');
        }
        if ($range == '6M') {
            $month    = intval($date->format('m'));
            $half     = ceil(($month / 12) * 2);
            $halfName = $half == 1 ? 'first' : 'second';

            return $halfName . ' half of ' . $date->format('Y');
        }
        throw new FireflyException('No _periodName() for range "' . $range . '"');
    }

    /**
     * @param        $range
     * @param Carbon $start
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function updateEndDate($range, Carbon $start)
    {
        $functionMap = [
            '1D' => 'endOfDay',
            '1W' => 'endOfWeek',
            '1M' => 'endOfMonth',
            '3M' => 'lastOfQuarter',
            '1Y' => 'endOfYear',
        ];
        $end         = clone $start;

        if (isset($functionMap[$range])) {
            $function = $functionMap[$range];
            $end->$function();

            return $end;
        }
        if ($range == '6M') {
            if (intval($start->format('m')) >= 7) {
                $end->endOfYear();
            } else {
                $end->startOfYear()->addMonths(6);
            }

            return $end;
        }
        throw new FireflyException('updateEndDate cannot handle $range ' . $range);
    }

    /**
     * @param        $range
     * @param Carbon $start
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function updateStartDate($range, Carbon $start)
    {
        $functionMap = [
            '1D' => 'startOfDay',
            '1W' => 'startOfWeek',
            '1M' => 'startOfMonth',
            '3M' => 'firstOfQuarter',
            '1Y' => 'startOfYear',
        ];
        if (isset($functionMap[$range])) {
            $function = $functionMap[$range];
            $start->$function();

            return $start;
        }
        if ($range == '6M') {
            if (intval($start->format('m')) >= 7) {
                $start->startOfYear()->addMonths(6);
            } else {
                $start->startOfYear();
            }

            return $start;
        }
        throw new FireflyException('updateStartDate cannot handle $range ' . $range);
    }

    /**
     * @param Carbon         $theDate
     * @param                $repeatFreq
     * @param                $skip
     *
     * @return \Carbon\Carbon
     * @throws FireflyException
     */
    public function addPeriod(Carbon $theDate, $repeatFreq, $skip)
    {
        $date = clone $theDate;
        $add  = ($skip + 1);

        $functionMap = [
            'daily'     => 'addDays',
            'weekly'    => 'addWeeks',
            'week'      => 'addWeeks',
            'month'     => 'addMonths',
            'monthly'   => 'addMonths',
            'quarter'   => 'addMonths',
            'quarterly' => 'addMonths',
            'half-year' => 'addMonths',
            'year'      => 'addYears',
            'yearly'    => 'addYears',
        ];
        $modifierMap = [
            'quarter'   => 3,
            'quarterly' => 3,
            'half-year' => 6,
        ];
        if (!isset($functionMap[$repeatFreq])) {
            throw new FireflyException('Cannot do addPeriod for $repeat_freq "' . $repeatFreq . '"');
        }
        if (isset($modifierMap[$repeatFreq])) {
            $add = $add * $modifierMap[$repeatFreq];
        }
        $function = $functionMap[$repeatFreq];
        $date->$function($add);

        return $date;
    }


}
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
     * @param \Carbon\Carbon $theDate
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
            '1D'      => 'addDays', 'daily' => 'addDays',
            '1W'      => 'addWeeks', 'weekly' => 'addWeeks', 'week' => 'addWeeks',
            '1M'      => 'addMonths', 'month' => 'addMonths', 'monthly' => 'addMonths', '3M' => 'addMonths',
            'quarter' => 'addMonths', 'quarterly' => 'addMonths', '6M' => 'addMonths', 'half-year' => 'addMonths',
            'year'    => 'addYears', 'yearly' => 'addYears',
        ];
        $modifierMap = [
            'quarter'   => 3,
            '3M'        => 3,
            'quarterly' => 3,
            '6M'        => 6,
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

    /**
     * @param \Carbon\Carbon $theCurrentEnd
     * @param                $repeatFreq
     *
     * @return \Carbon\Carbon
     * @throws FireflyException
     */
    public function endOfPeriod(Carbon $theCurrentEnd, $repeatFreq)
    {
        $currentEnd = clone $theCurrentEnd;

        $functionMap = [
            '1D'   => 'addDay', 'daily' => 'addDay',
            '1W'   => 'addWeek', 'week' => 'addWeek', 'weekly' => 'addWeek',
            '1M'   => 'addMonth', 'month' => 'addMonth', 'monthly' => 'addMonth',
            '3M'   => 'addMonths', 'quarter' => 'addMonths', 'quarterly' => 'addMonths', '6M' => 'addMonths', 'half-year' => 'addMonths',
            'year' => 'addYear', 'yearly' => 'addYear',
        ];
        $modifierMap = [
            'quarter'   => 3,
            '3M'        => 3,
            'quarterly' => 3,
            'half-year' => 6,
            '6M'        => 6,
        ];

        $subDay = ['week', 'weekly', '1W', 'month', 'monthly', '1M', '3M', 'quarter', 'quarterly', '6M', 'half-year', 'year', 'yearly'];

        if (!isset($functionMap[$repeatFreq])) {
            throw new FireflyException('Cannot do endOfPeriod for $repeat_freq ' . $repeatFreq);
        }
        $function = $functionMap[$repeatFreq];
        if (isset($modifierMap[$repeatFreq])) {
            $currentEnd->$function($modifierMap[$repeatFreq]);
        } else {
            $currentEnd->$function();
        }
        if (in_array($repeatFreq, $subDay)) {
            $currentEnd->subDay();
        }

        return $currentEnd;
    }

    /**
     *
     * @param Carbon         $theCurrentEnd
     * @param                $repeatFreq
     * @param Carbon         $maxDate
     *
     * @return Carbon
     */
    public function endOfX(Carbon $theCurrentEnd, $repeatFreq, Carbon $maxDate)
    {
        $functionMap = [
            'daily'     => 'endOfDay',
            'week'      => 'endOfWeek',
            'weekly'    => 'endOfWeek',
            'month'     => 'endOfMonth',
            'monthly'   => 'endOfMonth',
            'quarter'   => 'lastOfQuarter',
            'quarterly' => 'lastOfQuarter',
            'year'      => 'endOfYear',
            'yearly'    => 'endOfYear',
        ];
        $specials    = ['mont', 'monthly'];

        $currentEnd = clone $theCurrentEnd;

        if (isset($functionMap[$repeatFreq])) {
            $function = $functionMap[$repeatFreq];
            $currentEnd->$function();

        }
        if (isset($specials[$repeatFreq])) {
            $currentEnd->endOfYear();
            if ($theCurrentEnd->month <= 6) {
                $currentEnd->subMonths(6);
            }
        }
        if ($currentEnd > $maxDate) {
            return clone $maxDate;
        }

        return $currentEnd;
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
                if ($date->month >= 7) {
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
            $date->startOfYear();
            if ($date->month <= 6) {
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


            return 'Q' . ceil(($date->month / 12) * 4) . ' ' . $date->year;
        }
        if ($range == '6M') {
            $half     = ceil(($date->month / 12) * 2);
            $halfName = $half == 1 ? 'first' : 'second';

            return $halfName . ' half of ' . $date->year;
        }
        throw new FireflyException('No _periodName() for range "' . $range . '"');
    }

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
     * @param \Carbon\Carbon $theDate
     * @param                $repeatFreq
     *
     * @return \Carbon\Carbon
     * @throws FireflyException
     */
    public function startOfPeriod(Carbon $theDate, $repeatFreq)
    {
        $date = clone $theDate;

        $functionMap = [
            '1D'        => 'startOfDay',
            'daily'     => 'startOfDay',
            '1W'        => 'startOfWeek',
            'week'      => 'startOfWeek',
            'weekly'    => 'startOfWeek',
            'month'     => 'startOfMonth',
            '1M'        => 'startOfMonth',
            'monthly'   => 'startOfMonth',
            '3M'        => 'firstOfQuarter',
            'quarter'   => 'firstOfQuarter',
            'quarterly' => 'firstOfQuarter',
            'year'      => 'startOfYear',
            'yearly'    => 'startOfYear',
        ];
        if (isset($functionMap[$repeatFreq])) {
            $function = $functionMap[$repeatFreq];
            $date->$function();

            return $date;
        }
        if ($repeatFreq == 'half-year' || $repeatFreq == '6M') {
            $month = $date->month;
            $date->startOfYear();
            if ($month >= 7) {
                $date->addMonths(6);
            }

            return $date;
        }
        throw new FireflyException('Cannot do startOfPeriod for $repeat_freq ' . $repeatFreq);
    }

    /**
     * @param Carbon         $theDate
     * @param                $repeatFreq
     * @param int            $subtract
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function subtractPeriod(Carbon $theDate, $repeatFreq, $subtract = 1)
    {
        $date = clone $theDate;

        $functionMap = [
            'daily'   => 'subDays',
            'week'    => 'subWeeks',
            'weekly'  => 'subWeeks',
            'month'   => 'subMonths',
            'monthly' => 'subMonths',
            'year'    => 'subYears',
            'yearly'  => 'subYears',
        ];
        $modifierMap = [
            'quarter'   => 3,
            'quarterly' => 3,
            'half-year' => 6,
        ];
        if (isset($functionMap[$repeatFreq])) {
            $function = $functionMap[$repeatFreq];
            $date->$function($subtract);

            return $date;
        }
        if (isset($modifierMap[$repeatFreq])) {
            $subtract = $subtract * $modifierMap[$repeatFreq];
            $date->subMonths($subtract);

            return $date;
        }

        throw new FireflyException('Cannot do subtractPeriod for $repeat_freq ' . $repeatFreq);
    }

    /**
     * @param                $range
     * @param \Carbon\Carbon $start
     *
     * @return \Carbon\Carbon
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
            if ($start->month >= 7) {
                $end->endOfYear();
            } else {
                $end->startOfYear()->addMonths(6);
            }

            return $end;
        }
        throw new FireflyException('updateEndDate cannot handle $range ' . $range);
    }

    /**
     * @param                $range
     * @param \Carbon\Carbon $start
     *
     * @return \Carbon\Carbon
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
            if ($start->month >= 7) {
                $start->startOfYear()->addMonths(6);
            } else {
                $start->startOfYear();
            }

            return $start;
        }
        throw new FireflyException('updateStartDate cannot handle $range ' . $range);
    }


}

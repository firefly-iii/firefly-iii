<?php
/**
 * Navigation.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

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
    public function addPeriod(Carbon $theDate, string $repeatFreq, int $skip): Carbon
    {
        $date = clone $theDate;
        $add  = ($skip + 1);

        $functionMap = [
            '1D'      => 'addDays', 'daily' => 'addDays',
            '1W'      => 'addWeeks', 'weekly' => 'addWeeks', 'week' => 'addWeeks',
            '1M'      => 'addMonths', 'month' => 'addMonths', 'monthly' => 'addMonths', '3M' => 'addMonths',
            'quarter' => 'addMonths', 'quarterly' => 'addMonths', '6M' => 'addMonths', 'half-year' => 'addMonths',
            'year'    => 'addYears', 'yearly' => 'addYears', '1Y' => 'addYears',
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
     * @param \Carbon\Carbon $end
     * @param                $repeatFreq
     *
     * @return \Carbon\Carbon
     * @throws FireflyException
     */
    public function endOfPeriod(Carbon $end, string $repeatFreq): Carbon
    {
        $currentEnd = clone $end;

        $functionMap = [
            '1D'   => 'endOfDay', 'daily' => 'endOfDay',
            '1W'   => 'addWeek', 'week' => 'addWeek', 'weekly' => 'addWeek',
            '1M'   => 'addMonth', 'month' => 'addMonth', 'monthly' => 'addMonth',
            '3M'   => 'addMonths', 'quarter' => 'addMonths', 'quarterly' => 'addMonths', '6M' => 'addMonths', 'half-year' => 'addMonths',
            'year' => 'addYear', 'yearly' => 'addYear', '1Y' => 'addYear',
        ];
        $modifierMap = [
            'quarter'   => 3,
            '3M'        => 3,
            'quarterly' => 3,
            'half-year' => 6,
            '6M'        => 6,
        ];

        $subDay = ['week', 'weekly', '1W', 'month', 'monthly', '1M', '3M', 'quarter', 'quarterly', '6M', 'half-year', 'year', 'yearly'];

        // if the range is custom, the end of the period
        // is another X days (x is the difference between start)
        // and end added to $theCurrentEnd
        if ($repeatFreq == 'custom') {
            /** @var Carbon $tStart */
            $tStart = session('start', Carbon::now()->startOfMonth());
            /** @var Carbon $tEnd */
            $tEnd       = session('end', Carbon::now()->endOfMonth());
            $diffInDays = $tStart->diffInDays($tEnd);
            $currentEnd->addDays($diffInDays);

            return $currentEnd;
        }

        if (!isset($functionMap[$repeatFreq])) {
            throw new FireflyException('Cannot do endOfPeriod for $repeat_freq "' . $repeatFreq . '"');
        }
        $function = $functionMap[$repeatFreq];
        if (isset($modifierMap[$repeatFreq])) {
            $currentEnd->$function($modifierMap[$repeatFreq]);

            if (in_array($repeatFreq, $subDay)) {
                $currentEnd->subDay();
            }

            return $currentEnd;
        }
        $currentEnd->$function();
        if (in_array($repeatFreq, $subDay)) {
            $currentEnd->subDay();
        }

        return $currentEnd;
    }

    /**
     *
     * @param \Carbon\Carbon $theCurrentEnd
     * @param                $repeatFreq
     * @param \Carbon\Carbon $maxDate
     *
     * @return \Carbon\Carbon
     */
    public function endOfX(Carbon $theCurrentEnd, string $repeatFreq, Carbon $maxDate = null): Carbon
    {
        $functionMap = [
            '1D'        => 'endOfDay',
            'daily'     => 'endOfDay',
            '1W'        => 'endOfWeek',
            'week'      => 'endOfWeek',
            'weekly'    => 'endOfWeek',
            'month'     => 'endOfMonth',
            '1M'        => 'endOfMonth',
            'monthly'   => 'endOfMonth',
            '3M'        => 'lastOfQuarter',
            'quarter'   => 'lastOfQuarter',
            'quarterly' => 'lastOfQuarter',
            '1Y'        => 'endOfYear',
            'year'      => 'endOfYear',
            'yearly'    => 'endOfYear',
        ];

        $currentEnd = clone $theCurrentEnd;

        if (isset($functionMap[$repeatFreq])) {
            $function = $functionMap[$repeatFreq];
            $currentEnd->$function();

        }

        if (!is_null($maxDate) && $currentEnd > $maxDate) {
            return clone $maxDate;
        }

        return $currentEnd;
    }

    /**
     * @param \Carbon\Carbon $date
     * @param                $repeatFrequency
     *
     * @return string
     * @throws FireflyException
     */
    public function periodShow(Carbon $date, string $repeatFrequency): string
    {
        $formatMap = [
            '1D'      => trans('config.specific_day'),
            'daily'   => trans('config.specific_day'),
            'custom'  => trans('config.specific_day'),
            '1W'      => trans('config.week_in_year'),
            'week'    => trans('config.week_in_year'),
            'weekly'  => trans('config.week_in_year'),
            '3M'      => trans('config.quarter_of_year'),
            'quarter' => trans('config.quarter_of_year'),
            '1M'      => trans('config.month'),
            'month'   => trans('config.month'),
            'monthly' => trans('config.month'),
            '1Y'      => trans('config.year'),
            'year'    => trans('config.year'),
            'yearly'  => trans('config.year'),
            '6M'      => trans('config.half_year'),

        ];


        if (isset($formatMap[$repeatFrequency])) {
            return $date->formatLocalized(strval($formatMap[$repeatFrequency]));
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
    public function startOfPeriod(Carbon $theDate, string $repeatFreq): Carbon
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
            '1Y'        => 'startOfYear',
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
        if ($repeatFreq === 'custom') {
            return $date; // the date is already at the start.
        }


        throw new FireflyException('Cannot do startOfPeriod for $repeat_freq "' . $repeatFreq . '"');
    }

    /**
     * @param \Carbon\Carbon $theDate
     * @param                $repeatFreq
     * @param int            $subtract
     *
     * @return \Carbon\Carbon
     * @throws FireflyException
     */
    public function subtractPeriod(Carbon $theDate, string $repeatFreq, int $subtract = 1): Carbon
    {
        $date = clone $theDate;
        // 1D 1W 1M 3M 6M 1Y
        $functionMap = [
            '1D'      => 'subDays',
            'daily'   => 'subDays',
            'week'    => 'subWeeks',
            '1W'      => 'subWeeks',
            'weekly'  => 'subWeeks',
            'month'   => 'subMonths',
            '1M'      => 'subMonths',
            'monthly' => 'subMonths',
            'year'    => 'subYears',
            '1Y'      => 'subYears',
            'yearly'  => 'subYears',
        ];
        $modifierMap = [
            'quarter'   => 3,
            '3M'        => 3,
            'quarterly' => 3,
            'half-year' => 6,
            '6M'        => 6,
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
        // a custom range requires the session start
        // and session end to calculate the difference in days.
        // this is then subtracted from $theDate (* $subtract).
        if ($repeatFreq === 'custom') {
            /** @var Carbon $tStart */
            $tStart = session('start', Carbon::now()->startOfMonth());
            /** @var Carbon $tEnd */
            $tEnd       = session('end', Carbon::now()->endOfMonth());
            $diffInDays = $tStart->diffInDays($tEnd);
            $date->subDays($diffInDays * $subtract);

            return $date;
        }

        throw new FireflyException('Cannot do subtractPeriod for $repeat_freq "' . $repeatFreq . '"');
    }

    /**
     * @param                $range
     * @param \Carbon\Carbon $start
     *
     * @return \Carbon\Carbon
     * @throws FireflyException
     */
    public function updateEndDate(string $range, Carbon $start): Carbon
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

                return $end;
            }
            $end->startOfYear()->addMonths(6);

            return $end;
        }
        throw new FireflyException('updateEndDate cannot handle $range "' . $range . '"');
    }

    /**
     * @param                $range
     * @param \Carbon\Carbon $start
     *
     * @return \Carbon\Carbon
     * @throws FireflyException
     */
    public function updateStartDate(string $range, Carbon $start): Carbon
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

                return $start;
            }
            $start->startOfYear();

            return $start;


        }
        throw new FireflyException('updateStartDate cannot handle $range "' . $range . '"');
    }


}

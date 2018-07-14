<?php
/**
 * DateCalculation.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Http\Controllers;

use Carbon\Carbon;
use Log;

/**
 * Trait DateCalculation
 *
 * @package FireflyIII\Support\Http\Controllers
 */
trait DateCalculation
{
    /**
     * Returns the number of days between the two given dates.
     * - If today is within the two dates, give the number of days between today and the end date.
     * - If they are the same, return 1.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return int
     */
    protected function getDayDifference(Carbon $start, Carbon $end): int
    {
        $dayDifference = 0;

        // if today is between start and end, use the diff in days between end and today (days left)
        // otherwise, use diff between start and end.
        $today = new Carbon;
        Log::debug(sprintf('Start is %s, end is %s, today is %s', $start->format('Y-m-d'), $end->format('Y-m-d'), $today->format('Y-m-d')));
        if ($today->gte($start) && $today->lte($end)) {
            $dayDifference = $end->diffInDays($today);
        }
        if ($today->lte($start) || $today->gte($end)) {
            $dayDifference = $start->diffInDays($end);
        }
        $dayDifference = 0 === $dayDifference ? 1 : $dayDifference;

        return $dayDifference;
    }

    /**
     * Returns the number of days that have passed in this period. If it is zero (start of period)
     * then return 1.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return int
     */
    protected function getDaysPassedInPeriod(Carbon $start, Carbon $end): int
    {
        // if today is between start and end, use the diff in days between end and today (days left)
        // otherwise, use diff between start and end.
        $today      = new Carbon;
        $daysPassed = 0;
        Log::debug(sprintf('Start is %s, end is %s, today is %s', $start->format('Y-m-d'), $end->format('Y-m-d'), $today->format('Y-m-d')));
        if ($today->gte($start) && $today->lte($end)) {
            $daysPassed = $start->diffInDays($today);
        }
        if ($today->lte($start) || $today->gte($end)) {
            $daysPassed = $start->diffInDays($end);
        }
        $daysPassed = 0 === $daysPassed ? 1 : $daysPassed;

        return $daysPassed;

    }

    /**
     * Get a list of the periods that will occur after this date. For example,
     * March 2018, April 2018, etc.
     *
     * @param Carbon $date
     * @param string $range
     *
     * @return array
     */
    protected function getNextPeriods(Carbon $date, string $range): array
    {
        // select thing for next 12 periods:
        $loop = [];
        /** @var Carbon $current */
        $current = clone $date;
        $current->addDay();
        $count = 0;

        while ($count < 12) {
            $format        = $current->format('Y-m-d');
            $loop[$format] = app('navigation')->periodShow($current, $range);
            $current      = app('navigation')->endOfPeriod($current, $range);
            ++$count;
            $current->addDay();
        }

        return $loop;
    }

    /**
     * Get a list of the periods that occurred before the start date. For example,
     * March 2018, February 2018, etc.
     *
     * @param Carbon $date
     * @param string $range
     *
     * @return array
     */
    protected function getPreviousPeriods(Carbon $date, string $range): array
    {
        // select thing for last 12 periods:
        $loop = [];
        /** @var Carbon $current */
        $current = clone $date;
        $count   = 0;
        while ($count < 12) {
            $current->subDay();
            $current       = app('navigation')->startOfPeriod($current, $range);
            $format        = $current->format('Y-m-d');
            $loop[$format] = app('navigation')->periodShow($current, $range);
            ++$count;
        }

        return $loop;
    }

}
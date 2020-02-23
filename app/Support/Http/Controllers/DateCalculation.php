<?php
/**
 * DateCalculation.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Http\Controllers;

use Carbon\Carbon;

/**
 * Trait DateCalculation
 *
 */
trait DateCalculation
{
    /**
     * Calculate the number of days passed left until end date, as seen from start date.
     * If today is between start and end, today will be used instead of end.
     *
     * If both are in the past OR both are in the future, simply return the number of days in the period with a minimum of 1
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return int
     */
    public function activeDaysLeft(Carbon $start, Carbon $end): int
    {
        $difference = $start->diffInDays($end) + 1;
        $today      = Carbon::now()->startOfDay();

        if ($start->lte($today) && $end->gte($today)) {
            $difference = $today->diffInDays($end);
        }
        $difference = 0 === $difference ? 1 : $difference;

        return $difference;
    }

    /**
     * Calculate the number of days passed between two dates. Will take the current moment into consideration.
     *
     * If both are in the past OR both are in the future, simply return the period between them with a minimum of 1
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return int
     */
    protected function activeDaysPassed(Carbon $start, Carbon $end): int
    {
        $difference = $start->diffInDays($end) + 1;
        $today      = Carbon::now()->startOfDay();

        if ($start->lte($today) && $end->gte($today)) {
            $difference = $start->diffInDays($today) + 1;
        }

        return $difference;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    protected function calculateStep(Carbon $start, Carbon $end): string
    {

        $step   = '1D';
        $months = $start->diffInMonths($end);
        if ($months > 3) {
            $step = '1W'; // @codeCoverageIgnore
        }
        if ($months > 24) {
            $step = '1M'; // @codeCoverageIgnore
        }
        if ($months > 100) {
            $step = '1Y'; // @codeCoverageIgnore
        }

        return $step;
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
        $current = app('navigation')->startOfPeriod($date, $range);
        $current = app('navigation')->endOfPeriod($current, $range);
        $current->addDay();
        $count = 0;

        while ($count < 12) {
            $current      = app('navigation')->endOfPeriod($current, $range);
            $currentStart = app('navigation')->startOfPeriod($current, $range);

            $loop[] = [
                'label' => $current->format('Y-m-d'),
                'title' => app('navigation')->periodShow($current, $range),
                'start' => clone $currentStart,
                'end'   => clone $current,
            ];


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
        $current = app('navigation')->startOfPeriod($date, $range);
        $count   = 0;
        while ($count < 12) {
            $current->subDay();
            $current    = app('navigation')->startOfPeriod($current, $range);
            $currentEnd = app('navigation')->endOfPeriod($current, $range);
            $loop[]     = [
                'label' => $current->format('Y-m-d'),
                'title' => app('navigation')->periodShow($current, $range),
                'start' => clone $current,
                'end'   => clone $currentEnd,
            ];
            ++$count;
        }

        return $loop;
    }

}

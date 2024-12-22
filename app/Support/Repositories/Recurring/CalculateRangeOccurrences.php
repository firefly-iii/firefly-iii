<?php

/**
 * CalculateRangeOccurrences.php
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

namespace FireflyIII\Support\Repositories\Recurring;

use Carbon\Carbon;

/**
 * Trait CalculateRangeOccurrences
 */
trait CalculateRangeOccurrences
{
    /**
     * Get the number of daily occurrences for a recurring transaction until date $end is reached. Will skip every
     * $skipMod-1 occurrences.
     */
    protected function getDailyInRange(Carbon $start, Carbon $end, int $skipMod): array
    {
        $return   = [];
        $attempts = 0;
        while ($start <= $end) {
            if (0 === $attempts % $skipMod) {
                $return[] = clone $start;
            }
            $start->addDay();
            ++$attempts;
        }

        return $return;
    }

    /**
     * Get the number of daily occurrences for a recurring transaction until date $end is reached. Will skip every
     * $skipMod-1 occurrences.
     */
    protected function getMonthlyInRange(Carbon $start, Carbon $end, int $skipMod, string $moment): array
    {
        $return     = [];
        $attempts   = 0;
        $dayOfMonth = (int) $moment;
        if ($start->day > $dayOfMonth) {
            // day has passed already, add a month.
            $start->addMonth();
        }
        while ($start < $end) {
            $domCorrected = min($dayOfMonth, $start->daysInMonth);
            $start->day   = $domCorrected;
            if (0 === $attempts % $skipMod && $start->lte($start) && $end->gte($start)) {
                $return[] = clone $start;
            }
            ++$attempts;
            $start->endOfMonth()->startOfDay()->addDay();
        }

        return $return;
    }

    /**
     * Get the number of daily occurrences for a recurring transaction until date $end is reached. Will skip every
     * $skipMod-1 occurrences.
     */
    protected function getNdomInRange(Carbon $start, Carbon $end, int $skipMod, string $moment): array
    {
        $return   = [];
        $attempts = 0;
        $start->startOfMonth();
        // this feels a bit like a cop out but why reinvent the wheel?
        $counters   = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth'];
        $daysOfWeek = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
        $parts      = explode(',', $moment);
        while ($start <= $end) {
            $string    = sprintf('%s %s of %s %s', $counters[$parts[0]], $daysOfWeek[$parts[1]], $start->format('F'), $start->format('Y'));
            $newCarbon = new Carbon($string);
            if (0 === $attempts % $skipMod) {
                $return[] = clone $newCarbon;
            }
            ++$attempts;
            $start->endOfMonth()->addDay();
        }

        return $return;
    }

    /**
     * Get the number of daily occurrences for a recurring transaction until date $end is reached. Will skip every
     * $skipMod-1 occurrences.
     */
    protected function getWeeklyInRange(Carbon $start, Carbon $end, int $skipMod, string $moment): array
    {
        $return   = [];
        $attempts = 0;
        app('log')->debug('Rep is weekly.');
        // monday = 1
        // sunday = 7
        $dayOfWeek = (int) $moment;
        app('log')->debug(sprintf('DoW in repetition is %d, in mutator is %d', $dayOfWeek, $start->dayOfWeekIso));
        if ($start->dayOfWeekIso > $dayOfWeek) {
            // day has already passed this week, add one week:
            $start->addWeek();
            app('log')->debug(sprintf('Jump to next week, so mutator is now: %s', $start->format('Y-m-d')));
        }
        // today is wednesday (3), expected is friday (5): add two days.
        // today is friday (5), expected is monday (1), subtract four days.
        app('log')->debug(sprintf('Mutator is now: %s', $start->format('Y-m-d')));
        $dayDifference = $dayOfWeek - $start->dayOfWeekIso;
        $start->addDays($dayDifference);
        app('log')->debug(sprintf('Mutator is now: %s', $start->format('Y-m-d')));
        while ($start <= $end) {
            if (0 === $attempts % $skipMod && $start->lte($start) && $end->gte($start)) {
                app('log')->debug('Date is in range of start+end, add to set.');
                $return[] = clone $start;
            }
            ++$attempts;
            $start->addWeek();
            app('log')->debug(sprintf('Mutator is now (end of loop): %s', $start->format('Y-m-d')));
        }

        return $return;
    }

    /**
     * Get the number of daily occurrences for a recurring transaction until date $end is reached. Will skip every
     * $skipMod-1 occurrences.
     */
    protected function getYearlyInRange(Carbon $start, Carbon $end, int $skipMod, string $moment): array
    {
        $attempts   = 0;
        $date       = new Carbon($moment);
        $date->year = $start->year;
        $return     = [];
        if ($start > $date) {
            $date->addYear();
        }

        // is $date between $start and $end?
        $obj   = clone $date;
        $count = 0;
        while ($obj <= $end && $obj >= $start && $count < 10) {
            if (0 === $attempts % $skipMod) {
                $return[] = clone $obj;
            }
            $obj->addYears();
            ++$count;
            ++$attempts;
        }

        return $return;
    }
}

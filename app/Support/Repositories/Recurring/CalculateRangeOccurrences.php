<?php

/**
 * CalculateRangeOccurrences.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Repositories\Recurring;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Trait CalculateRangeOccurrences
 */
trait CalculateRangeOccurrences
{

    /**
     * Get the number of daily occurrences for a recurring transaction until date $end is reached. Will skip every $skipMod-1 occurrences.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int    $skipMod
     *
     * @return array
     */
    protected function getDailyInRange(Carbon $start, Carbon $end, int $skipMod): array
    {
        $return   = [];
        $attempts = 0;
        Log::debug('Rep is daily. Start of loop.');
        while ($start <= $end) {
            Log::debug(sprintf('Mutator is now: %s', $start->format('Y-m-d')));
            if (0 === $attempts % $skipMod) {
                Log::debug(sprintf('Attempts modulo skipmod is zero, include %s', $start->format('Y-m-d')));
                $return[] = clone $start;
            }
            $start->addDay();
            $attempts++;
        }

        return $return;
    }


    /**
     * Get the number of daily occurrences for a recurring transaction until date $end is reached. Will skip every $skipMod-1 occurrences.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int    $skipMod
     * @param string $moment
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getMonthlyInRange(Carbon $start, Carbon $end, int $skipMod, string $moment): array
    {
        $return     = [];
        $attempts   = 0;
        $dayOfMonth = (int)$moment;
        Log::debug(sprintf('Day of month in repetition is %d', $dayOfMonth));
        Log::debug(sprintf('Start is %s.', $start->format('Y-m-d')));
        Log::debug(sprintf('End is %s.', $end->format('Y-m-d')));
        if ($start->day > $dayOfMonth) {
            Log::debug('Add a month.');
            // day has passed already, add a month.
            $start->addMonth();
        }
        Log::debug(sprintf('Start is now %s.', $start->format('Y-m-d')));
        Log::debug('Start loop.');
        while ($start < $end) {
            Log::debug(sprintf('Mutator is now %s.', $start->format('Y-m-d')));
            $domCorrected = min($dayOfMonth, $start->daysInMonth);
            Log::debug(sprintf('DoM corrected is %d', $domCorrected));
            $start->day = $domCorrected;
            Log::debug(sprintf('Mutator is now %s.', $start->format('Y-m-d')));
            Log::debug(sprintf('$attempts %% $skipMod === 0 is %s', var_export(0 === $attempts % $skipMod, true)));
            Log::debug(sprintf('$start->lte($mutator) is %s', var_export($start->lte($start), true)));
            Log::debug(sprintf('$end->gte($mutator) is %s', var_export($end->gte($start), true)));
            if (0 === $attempts % $skipMod && $start->lte($start) && $end->gte($start)) {
                Log::debug(sprintf('ADD %s to return!', $start->format('Y-m-d')));
                $return[] = clone $start;
            }
            $attempts++;
            $start->endOfMonth()->startOfDay()->addDay();
        }

        return $return;
    }



    /**
     * Get the number of daily occurrences for a recurring transaction until date $end is reached. Will skip every $skipMod-1 occurrences.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int    $skipMod
     * @param string $moment
     *
     * @return array
     */
    protected function getNdomInRange(Carbon $start, Carbon $end, int $skipMod, string $moment): array
    {
        $return   = [];
        $attempts = 0;
        $start->startOfMonth();
        // this feels a bit like a cop out but why reinvent the wheel?
        $counters   = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth',];
        $daysOfWeek = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',];
        $parts      = explode(',', $moment);
        while ($start <= $end) {
            $string    = sprintf('%s %s of %s %s', $counters[$parts[0]], $daysOfWeek[$parts[1]], $start->format('F'), $start->format('Y'));
            $newCarbon = new Carbon($string);
            if (0 === $attempts % $skipMod) {
                $return[] = clone $newCarbon;
            }
            $attempts++;
            $start->endOfMonth()->addDay();
        }

        return $return;
    }

    /**
     * Get the number of daily occurrences for a recurring transaction until date $end is reached. Will skip every $skipMod-1 occurrences.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int    $skipMod
     * @param string $moment
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getWeeklyInRange(Carbon $start, Carbon $end, int $skipMod, string $moment): array
    {
        $return   = [];
        $attempts = 0;
        Log::debug('Rep is weekly.');
        // monday = 1
        // sunday = 7
        $dayOfWeek = (int)$moment;
        Log::debug(sprintf('DoW in repetition is %d, in mutator is %d', $dayOfWeek, $start->dayOfWeekIso));
        if ($start->dayOfWeekIso > $dayOfWeek) {
            // day has already passed this week, add one week:
            $start->addWeek();
            Log::debug(sprintf('Jump to next week, so mutator is now: %s', $start->format('Y-m-d')));
        }
        // today is wednesday (3), expected is friday (5): add two days.
        // today is friday (5), expected is monday (1), subtract four days.
        Log::debug(sprintf('Mutator is now: %s', $start->format('Y-m-d')));
        $dayDifference = $dayOfWeek - $start->dayOfWeekIso;
        $start->addDays($dayDifference);
        Log::debug(sprintf('Mutator is now: %s', $start->format('Y-m-d')));
        while ($start <= $end) {
            if (0 === $attempts % $skipMod && $start->lte($start) && $end->gte($start)) {
                Log::debug('Date is in range of start+end, add to set.');
                $return[] = clone $start;
            }
            $attempts++;
            $start->addWeek();
            Log::debug(sprintf('Mutator is now (end of loop): %s', $start->format('Y-m-d')));
        }

        return $return;
    }

    /**
     * Get the number of daily occurrences for a recurring transaction until date $end is reached. Will skip every $skipMod-1 occurrences.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param int    $skipMod
     * @param string $moment
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
            $obj->addYears(1);
            $count++;
            $attempts++;
        }

        return $return;

    }
}

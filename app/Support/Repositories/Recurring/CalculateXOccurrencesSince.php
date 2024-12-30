<?php

/**
 * CalculateXOccurrencesSince.php
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
 * Class CalculateXOccurrencesSince
 */
trait CalculateXOccurrencesSince
{
    /**
     * Calculates the number of daily occurrences for a recurring transaction, starting at the date, until $count is
     * reached. It will skip over $skipMod -1 recurrences.
     */
    protected function getXDailyOccurrencesSince(Carbon $date, Carbon $afterDate, int $count, int $skipMod): array
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $return   = [];
        $mutator  = clone $date;
        $total    = 0;
        $attempts = 0;
        while ($total < $count) {
            if (0 === $attempts % $skipMod && $mutator->gt($afterDate)) {
                $return[] = clone $mutator;
                ++$total;
            }
            $mutator->addDay();
            ++$attempts;
        }

        return $return;
    }

    /**
     * Calculates the number of monthly occurrences for a recurring transaction, starting at the date, until $count is
     * reached. It will skip over $skipMod -1 recurrences.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected function getXMonthlyOccurrencesSince(Carbon $date, Carbon $afterDate, int $count, int $skipMod, string $moment): array
    {
        app('log')->debug(sprintf('Now in %s(%s, %s, %d)', __METHOD__, $date->format('Y-m-d'), $afterDate->format('Y-m-d'), $count));
        $return     = [];
        $mutator    = clone $date;
        $total      = 0;
        $attempts   = 0;
        $dayOfMonth = (int) $moment;
        $dayOfMonth = 0 === $dayOfMonth ? 1 : $dayOfMonth;
        if ($mutator->day > $dayOfMonth) {
            app('log')->debug(sprintf('%d is after %d, add a month. Mutator is now', $mutator->day, $dayOfMonth));
            // day has passed already, add a month.
            $mutator->addMonth();
            app('log')->debug(sprintf('%s', $mutator->format('Y-m-d')));
        }

        while ($total < $count) {
            $domCorrected = min($dayOfMonth, $mutator->daysInMonth);
            $mutator->day = $domCorrected;
            app('log')->debug(sprintf('Mutator is now %s', $mutator->format('Y-m-d')));
            if (0 === $attempts % $skipMod && $mutator->gte($afterDate)) {
                app('log')->debug('Is added to the list.');
                $return[] = clone $mutator;
                ++$total;
            }
            ++$attempts;
            $mutator      = $mutator->endOfMonth()->addDay();
        }

        return $return;
    }

    /**
     * Calculates the number of NDOM occurrences for a recurring transaction, starting at the date, until $count is
     * reached. It will skip over $skipMod -1 recurrences.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected function getXNDomOccurrencesSince(Carbon $date, Carbon $afterDate, int $count, int $skipMod, string $moment): array
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $return     = [];
        $total      = 0;
        $attempts   = 0;
        $mutator    = clone $date;
        $mutator->addDay(); // always assume today has passed.
        $mutator->startOfMonth();
        // this feels a bit like a cop out but why reinvent the wheel?
        $counters   = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth'];
        $daysOfWeek = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
        $parts      = explode(',', $moment);

        while ($total < $count) {
            $string    = sprintf('%s %s of %s %s', $counters[$parts[0]], $daysOfWeek[$parts[1]], $mutator->format('F'), $mutator->format('Y'));
            $newCarbon = new Carbon($string);
            if (0 === $attempts % $skipMod && $mutator->gte($afterDate)) {
                $return[] = clone $newCarbon;
                ++$total;
            }
            ++$attempts;
            $mutator->endOfMonth()->addDay();
        }

        return $return;
    }

    /**
     * Calculates the number of weekly occurrences for a recurring transaction, starting at the date, until $count is
     * reached. It will skip over $skipMod -1 recurrences.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected function getXWeeklyOccurrencesSince(Carbon $date, Carbon $afterDate, int $count, int $skipMod, string $moment): array
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $return        = [];
        $total         = 0;
        $attempts      = 0;
        $mutator       = clone $date;
        // monday = 1
        // sunday = 7
        // Removed assumption today has passed, see issue https://github.com/firefly-iii/firefly-iii/issues/4798
        // $mutator->addDay(); // always assume today has passed.
        $dayOfWeek     = (int) $moment;
        if ($mutator->dayOfWeekIso > $dayOfWeek) {
            // day has already passed this week, add one week:
            $mutator->addWeek();
        }
        // today is wednesday (3), expected is friday (5): add two days.
        // today is friday (5), expected is monday (1), subtract four days.
        $dayDifference = $dayOfWeek - $mutator->dayOfWeekIso;
        $mutator->addDays($dayDifference);

        while ($total < $count) {
            if (0 === $attempts % $skipMod && $mutator->gte($afterDate)) {
                $return[] = clone $mutator;
                ++$total;
            }
            ++$attempts;
            $mutator->addWeek();
        }

        return $return;
    }

    /**
     * Calculates the number of yearly occurrences for a recurring transaction, starting at the date, until $count is
     * reached. It will skip over $skipMod -1 recurrences.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected function getXYearlyOccurrencesSince(Carbon $date, Carbon $afterDate, int $count, int $skipMod, string $moment): array
    {
        app('log')->debug(sprintf('Now in %s(%s, %d, %d, %s)', __METHOD__, $date->format('Y-m-d'), $date->format('Y-m-d'), $count, $skipMod));
        $return     = [];
        $mutator    = clone $date;
        $total      = 0;
        $attempts   = 0;
        $date       = new Carbon($moment);
        $date->year = $mutator->year;
        if ($mutator > $date) {
            app('log')->debug(
                sprintf('mutator (%s) > date (%s), so add a year to date (%s)', $mutator->format('Y-m-d'), $date->format('Y-m-d'), $date->format('Y-m-d'))
            );
            $date->addYear();
            app('log')->debug(sprintf('Date is now %s', $date->format('Y-m-d')));
        }
        $obj        = clone $date;
        while ($total < $count) {
            app('log')->debug(sprintf('total (%d) < count (%d) so go.', $total, $count));
            app('log')->debug(sprintf('attempts (%d) %% skipmod (%d) === %d', $attempts, $skipMod, $attempts % $skipMod));
            app('log')->debug(sprintf('Obj (%s) gte afterdate (%s)? %s', $obj->format('Y-m-d'), $afterDate->format('Y-m-d'), var_export($obj->gte($afterDate), true)));
            if (0 === $attempts % $skipMod && $obj->gte($afterDate)) {
                app('log')->debug('All conditions true, add obj.');
                $return[] = clone $obj;
                ++$total;
            }
            $obj->addYears();
            ++$attempts;
        }

        return $return;
    }
}

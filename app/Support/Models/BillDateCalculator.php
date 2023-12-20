<?php
/*
 * BillDateCalculator.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Support\Models;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BillDateCalculator
{
    /**
     * Returns the dates a bill needs to be paid.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function getPayDates(Carbon $earliest, Carbon $latest, Carbon $billStart, string $period, int $skip, ?Carbon $lastPaid): array
    {
        $earliest->startOfDay();
        $latest->endOfDay();
        $billStart->startOfDay();
        Log::debug('Now in BillDateCalculator::getPayDates()');
        Log::debug(sprintf('Dates must be between %s and %s.', $earliest->format('Y-m-d'), $latest->format('Y-m-d')));
        Log::debug(sprintf('Bill started on %s, period is "%s", skip is %d, last paid = "%s".', $billStart->format('Y-m-d'), $period, $skip, $lastPaid?->format('Y-m-d')));

        $set          = new Collection();
        $currentStart = clone $earliest;

        // 2023-06-23 subDay to fix 7655
        $currentStart->subDay();
        $loop = 0;
        Log::debug('Start of loop');
        while ($currentStart <= $latest) {
            Log::debug(sprintf('Current start is %s', $currentStart->format('Y-m-d')));
            $nextExpectedMatch = $this->nextDateMatch(clone $currentStart, clone $billStart, $period, $skip);
            Log::debug(sprintf('Next expected match is %s', $nextExpectedMatch->format('Y-m-d')));

            // If nextExpectedMatch is after end, we stop looking:
            if ($nextExpectedMatch->gt($latest)) {
                Log::debug('Next expected match is after $latest.');
                if ($set->count() > 0) {
                    Log::debug(sprintf('Already have %d date(s), so we can safely break.', $set->count()));

                    break;
                }
                Log::debug('Add date to set anyway, since we had no dates yet.');
                $set->push(clone $nextExpectedMatch);

                continue;
            }

            // add to set, if the date is ON or after the start parameter
            // AND date is after last paid date
            if (
                $nextExpectedMatch->gte($earliest) // date is after "earliest possible date"
                && (null === $lastPaid || $nextExpectedMatch->gt($lastPaid)) // date is after last paid date, if that date is not NULL
            ) {
                Log::debug('Add date to set, because it is after earliest possible date and after last paid date.');
                $set->push(clone $nextExpectedMatch);
            }

            // 2023-10
            // for the next loop, go to end of period, THEN add day.
            $nextExpectedMatch->addDay();
            $currentStart = clone $nextExpectedMatch;

            ++$loop;
            if ($loop > 12) {
                Log::debug('Loop is more than 12, so we break.');

                break;
            }
        }
        Log::debug('end of loop');
        $simple = $set->map(
            static function (Carbon $date) {
                return $date->format('Y-m-d');
            }
        );
        Log::debug(sprintf('Found %d pay dates', $set->count()), $simple->toArray());

        return $simple->toArray();
    }

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction given the earliest date this could happen.
     *
     * That date must be AFTER $billStartDate, as a sanity check.
     */
    protected function nextDateMatch(Carbon $earliest, Carbon $billStartDate, string $period, int $skip): Carbon
    {
        Log::debug(sprintf('Bill start date is %s', $billStartDate->format('Y-m-d')));
        if ($earliest->lt($billStartDate)) {
            Log::debug('Earliest possible date is after bill start, so just return bill start date.');

            return $billStartDate;
        }

        $steps  = app('navigation')->diffInPeriods($period, $skip, $earliest, $billStartDate);
        $result = clone $billStartDate;
        if ($steps > 0) {
            --$steps;
            Log::debug(sprintf('Steps is %d, because addPeriod already adds 1.', $steps));
            $result = app('navigation')->addPeriod($billStartDate, $period, $steps);
        }
        Log::debug(sprintf('Number of steps is %d, added to %s, result is %s', $steps, $billStartDate->format('Y-m-d'), $result->format('Y-m-d')));

        return $result;
    }
}

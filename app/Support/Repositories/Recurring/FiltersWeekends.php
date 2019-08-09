<?php

/**
 * FiltersWeekends.php
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
use FireflyIII\Models\RecurrenceRepetition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Trait FiltersWeekends
 */
trait FiltersWeekends
{

    /**
     * Filters out all weekend entries, if necessary.
     *
     * @param RecurrenceRepetition $repetition
     * @param array                $dates
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function filterWeekends(RecurrenceRepetition $repetition, array $dates): array
    {
        if ((int)$repetition->weekend === RecurrenceRepetition::WEEKEND_DO_NOTHING) {
            Log::debug('Repetition will not be filtered on weekend days.');

            return $dates;
        }
        $return = [];
        /** @var Carbon $date */
        foreach ($dates as $date) {
            $isWeekend = $date->isWeekend();
            if (!$isWeekend) {
                $return[] = clone $date;
                Log::debug(sprintf('Date is %s, not a weekend date.', $date->format('D d M Y')));
                continue;
            }

            // is weekend and must set back to Friday?
            if ($repetition->weekend === RecurrenceRepetition::WEEKEND_TO_FRIDAY) {
                $clone = clone $date;
                $clone->addDays(5 - $date->dayOfWeekIso);
                Log::debug(
                    sprintf('Date is %s, and this is in the weekend, so corrected to %s (Friday).', $date->format('D d M Y'), $clone->format('D d M Y'))
                );
                $return[] = clone $clone;
                continue;
            }

            // postpone to Monday?
            if ($repetition->weekend === RecurrenceRepetition::WEEKEND_TO_MONDAY) {
                $clone = clone $date;
                $clone->addDays(8 - $date->dayOfWeekIso);
                Log::debug(
                    sprintf('Date is %s, and this is in the weekend, so corrected to %s (Monday).', $date->format('D d M Y'), $clone->format('D d M Y'))
                );
                $return[] = $clone;
                continue;
            }
            Log::debug(sprintf('Date is %s, removed from final result', $date->format('D d M Y')));
        }

        // filter unique dates
        Log::debug(sprintf('Count before filtering: %d', count($dates)));
        $collection = new Collection($return);
        $filtered   = $collection->unique();
        $return     = $filtered->toArray();

        Log::debug(sprintf('Count after filtering: %d', count($return)));

        return $return;
    }
}

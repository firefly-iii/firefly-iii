<?php

/**
 * FiltersWeekends.php
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
use FireflyIII\Enums\RecurrenceRepetitionWeekend;
use FireflyIII\Models\RecurrenceRepetition;
use Illuminate\Support\Collection;

/**
 * Trait FiltersWeekends
 */
trait FiltersWeekends
{
    /**
     * Filters out all weekend entries, if necessary.
     */
    protected function filterWeekends(RecurrenceRepetition $repetition, array $dates): array
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        if (RecurrenceRepetitionWeekend::WEEKEND_DO_NOTHING->value === $repetition->weekend) {
            app('log')->debug('Repetition will not be filtered on weekend days.');

            return $dates;
        }
        $return     = [];

        /** @var Carbon $date */
        foreach ($dates as $date) {
            $isWeekend = $date->isWeekend();
            if (!$isWeekend) {
                $return[] = clone $date;

                // app('log')->debug(sprintf('Date is %s, not a weekend date.', $date->format('D d M Y')));
                continue;
            }

            // is weekend and must set back to Friday?
            if (RecurrenceRepetitionWeekend::WEEKEND_TO_FRIDAY->value === $repetition->weekend) {
                $clone    = clone $date;
                $clone->addDays(5 - $date->dayOfWeekIso);
                app('log')->debug(
                    sprintf('Date is %s, and this is in the weekend, so corrected to %s (Friday).', $date->format('D d M Y'), $clone->format('D d M Y'))
                );
                $return[] = clone $clone;

                continue;
            }

            // postpone to Monday?
            if (RecurrenceRepetitionWeekend::WEEKEND_TO_MONDAY->value === $repetition->weekend) {
                $clone    = clone $date;
                $clone->addDays(8 - $date->dayOfWeekIso);
                app('log')->debug(
                    sprintf('Date is %s, and this is in the weekend, so corrected to %s (Monday).', $date->format('D d M Y'), $clone->format('D d M Y'))
                );
                $return[] = $clone;

                continue;
            }
            // app('log')->debug(sprintf('Date is %s, removed from final result', $date->format('D d M Y')));
        }

        // filter unique dates
        app('log')->debug(sprintf('Count before filtering: %d', count($dates)));
        $collection = new Collection($return);
        $filtered   = $collection->unique();
        $return     = $filtered->toArray();

        app('log')->debug(sprintf('Count after filtering: %d', count($return)));

        return $return;
    }
}

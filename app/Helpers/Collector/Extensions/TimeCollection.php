<?php

/**
 * TimeCollection.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Helpers\Collector\Extensions;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;

/**
 * Trait TimeCollection
 */
trait TimeCollection
{
    public function dayAfter(string $day): GroupCollectorInterface
    {
        $this->query->whereDay('transaction_journals.date', '>=', $day);

        return $this;
    }

    public function dayBefore(string $day): GroupCollectorInterface
    {
        $this->query->whereDay('transaction_journals.date', '<=', $day);

        return $this;
    }

    public function dayIs(string $day): GroupCollectorInterface
    {
        $this->query->whereDay('transaction_journals.date', '=', $day);

        return $this;
    }

    public function dayIsNot(string $day): GroupCollectorInterface
    {
        $this->query->whereDay('transaction_journals.date', '!=', $day);

        return $this;
    }

    public function excludeMetaDateRange(Carbon $start, Carbon $end, string $field): GroupCollectorInterface
    {
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        $end                 = clone $end; // this is so weird, but it works if $end and $start secretly point to the same object.
        $end->endOfDay();
        $start->startOfDay();
        $this->withMetaDate($field);

        $filter              = static function (array $object) use ($field, $start, $end): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon) {
                    return $transaction[$field]->lt($start) || $transaction[$field]->gt($end);
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function withMetaDate(string $field): GroupCollectorInterface
    {
        $this->joinMetaDataTables();
        $this->query->where('journal_meta.name', '=', $field);
        $this->query->whereNotNull('journal_meta.data');

        return $this;
    }

    public function excludeObjectRange(Carbon $start, Carbon $end, string $field): GroupCollectorInterface
    {
        $after  = $start->format('Y-m-d 00:00:00');
        $before = $end->format('Y-m-d 23:59:59');

        $this->query->where(sprintf('transaction_journals.%s', $field), '<', $after);
        $this->query->orWhere(sprintf('transaction_journals.%s', $field), '>', $before);

        return $this;
    }

    public function excludeRange(Carbon $start, Carbon $end): GroupCollectorInterface
    {
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        $startStr = $start->format('Y-m-d 00:00:00');
        $endStr   = $end->format('Y-m-d 23:59:59');

        $this->query->where('transaction_journals.date', '<', $startStr);
        $this->query->orWhere('transaction_journals.date', '>', $endStr);

        return $this;
    }

    public function metaDayAfter(string $day, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $day): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return $transaction[$field]->day >= (int) $day;
                }
            }

            return true;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function metaDayBefore(string $day, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $day): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return $transaction[$field]->day <= (int) $day;
                }
            }

            return true;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function metaDayIs(string $day, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $day): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return (int) $day === $transaction[$field]->day;
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function metaDayIsNot(string $day, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $day): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return (int) $day !== $transaction[$field]->day;
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function metaMonthAfter(string $month, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $month): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return $transaction[$field]->month >= (int) $month;
                }
            }

            return true;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function metaMonthBefore(string $month, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $month): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return $transaction[$field]->month <= (int) $month;
                }
            }

            return true;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function metaMonthIs(string $month, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $month): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return (int) $month === $transaction[$field]->month;
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function metaMonthIsNot(string $month, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $month): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return (int) $month !== $transaction[$field]->month;
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function metaYearAfter(string $year, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $year): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return $transaction[$field]->year >= (int) $year;
                }
            }

            return true;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function metaYearBefore(string $year, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $year): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return $transaction[$field]->year <= (int) $year;
                }
            }

            return true;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function metaYearIs(string $year, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $year): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return $year === (string) $transaction[$field]->year;
                }
            }

            return true;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function metaYearIsNot(string $year, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $year): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return $year !== (string) $transaction[$field]->year;
                }
            }

            return true;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function monthAfter(string $month): GroupCollectorInterface
    {
        $this->query->whereMonth('transaction_journals.date', '>=', $month);

        return $this;
    }

    public function monthBefore(string $month): GroupCollectorInterface
    {
        $this->query->whereMonth('transaction_journals.date', '<=', $month);

        return $this;
    }

    public function monthIs(string $month): GroupCollectorInterface
    {
        $this->query->whereMonth('transaction_journals.date', '=', $month);

        return $this;
    }

    public function monthIsNot(string $month): GroupCollectorInterface
    {
        $this->query->whereMonth('transaction_journals.date', '!=', $month);

        return $this;
    }

    public function objectDayAfter(string $day, string $field): GroupCollectorInterface
    {
        $this->query->whereDay(sprintf('transaction_journals.%s', $field), '>=', $day);

        return $this;
    }

    public function objectDayBefore(string $day, string $field): GroupCollectorInterface
    {
        $this->query->whereDay(sprintf('transaction_journals.%s', $field), '<=', $day);

        return $this;
    }

    public function objectDayIs(string $day, string $field): GroupCollectorInterface
    {
        $this->query->whereDay(sprintf('transaction_journals.%s', $field), '=', $day);

        return $this;
    }

    public function objectDayIsNot(string $day, string $field): GroupCollectorInterface
    {
        $this->query->whereDay(sprintf('transaction_journals.%s', $field), '!=', $day);

        return $this;
    }

    public function objectMonthAfter(string $month, string $field): GroupCollectorInterface
    {
        $this->query->whereMonth(sprintf('transaction_journals.%s', $field), '>=', $month);

        return $this;
    }

    public function objectMonthBefore(string $month, string $field): GroupCollectorInterface
    {
        $this->query->whereMonth(sprintf('transaction_journals.%s', $field), '<=', $month);

        return $this;
    }

    public function objectMonthIs(string $month, string $field): GroupCollectorInterface
    {
        $this->query->whereMonth(sprintf('transaction_journals.%s', $field), '=', $month);

        return $this;
    }

    public function objectMonthIsNot(string $month, string $field): GroupCollectorInterface
    {
        $this->query->whereMonth(sprintf('transaction_journals.%s', $field), '!=', $month);

        return $this;
    }

    public function objectYearAfter(string $year, string $field): GroupCollectorInterface
    {
        $this->query->whereYear(sprintf('transaction_journals.%s', $field), '>=', $year);

        return $this;
    }

    public function objectYearBefore(string $year, string $field): GroupCollectorInterface
    {
        $this->query->whereYear(sprintf('transaction_journals.%s', $field), '<=', $year);

        return $this;
    }

    public function objectYearIs(string $year, string $field): GroupCollectorInterface
    {
        $this->query->whereYear(sprintf('transaction_journals.%s', $field), '=', $year);

        return $this;
    }

    public function objectYearIsNot(string $year, string $field): GroupCollectorInterface
    {
        $this->query->whereYear(sprintf('transaction_journals.%s', $field), '!=', $year);

        return $this;
    }

    /**
     * Collect transactions after a specific date.
     */
    public function setAfter(Carbon $date): GroupCollectorInterface
    {
        $afterStr = $date->format('Y-m-d 00:00:00');
        $this->query->where('transaction_journals.date', '>=', $afterStr);

        return $this;
    }

    /**
     * Collect transactions before a specific date.
     */
    public function setBefore(Carbon $date): GroupCollectorInterface
    {
        $beforeStr = $date->format('Y-m-d 23:59:59');
        $this->query->where('transaction_journals.date', '<=', $beforeStr);

        return $this;
    }

    /**
     * Collect transactions created on a specific date.
     */
    public function setCreatedAt(Carbon $date): GroupCollectorInterface
    {
        $after  = $date->format('Y-m-d 00:00:00');
        $before = $date->format('Y-m-d 23:59:59');
        $this->query->where('transaction_journals.created_at', '>=', $after);
        $this->query->where('transaction_journals.created_at', '<=', $before);

        return $this;
    }

    /**
     * Set the end time of the results to return.
     */
    public function setEnd(Carbon $end): GroupCollectorInterface
    {
        // always got to end of day / start of day for ranges.
        $endStr = $end->format('Y-m-d 23:59:59');

        $this->query->where('transaction_journals.date', '<=', $endStr);

        return $this;
    }

    public function setMetaAfter(Carbon $date, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $date->startOfDay();
        $filter              = static function (array $object) use ($field, $date): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return $transaction[$field]->gte($date);
                }
            }

            return true;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function setMetaBefore(Carbon $date, string $field): GroupCollectorInterface
    {
        $this->withMetaDate($field);
        $filter              = static function (array $object) use ($field, $date): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return $transaction[$field]->lte($date);
                }
            }

            return true;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function setMetaDateRange(Carbon $start, Carbon $end, string $field): GroupCollectorInterface
    {
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        $end                 = clone $end; // this is so weird, but it works if $end and $start secretly point to the same object.
        $end->endOfDay();
        $start->startOfDay();
        $this->withMetaDate($field);

        $filter              = static function (array $object) use ($field, $start, $end): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists($field, $transaction) && $transaction[$field] instanceof Carbon
                ) {
                    return $transaction[$field]->gte($start) && $transaction[$field]->lte($end);
                }
            }

            return false;
        };
        $this->postFilters[] = $filter;

        return $this;
    }

    public function setObjectAfter(Carbon $date, string $field): GroupCollectorInterface
    {
        $afterStr = $date->format('Y-m-d 00:00:00');
        $this->query->where(sprintf('transaction_journals.%s', $field), '>=', $afterStr);

        return $this;
    }

    public function setObjectBefore(Carbon $date, string $field): GroupCollectorInterface
    {
        $afterStr = $date->format('Y-m-d 00:00:00');
        $this->query->where(sprintf('transaction_journals.%s', $field), '<=', $afterStr);

        return $this;
    }

    public function setObjectRange(Carbon $start, Carbon $end, string $field): GroupCollectorInterface
    {
        $after  = $start->format('Y-m-d 00:00:00');
        $before = $end->format('Y-m-d 23:59:59');
        $this->query->where(sprintf('transaction_journals.%s', $field), '>=', $after);
        $this->query->where(sprintf('transaction_journals.%s', $field), '<=', $before);

        return $this;
    }

    /**
     * Set the start and end time of the results to return.
     *
     * Can either or both be NULL
     */
    public function setRange(?Carbon $start, ?Carbon $end): GroupCollectorInterface
    {
        if (null !== $start && null !== $end && $end < $start) {
            [$start, $end] = [$end, $start];
        }
        // always got to end of day / start of day for ranges.
        $startStr = $start?->format('Y-m-d 00:00:00');
        $endStr   = $end?->format('Y-m-d 23:59:59');

        if (null !== $start) {
            $this->query->where('transaction_journals.date', '>=', $startStr);
        }
        if (null !== $end) {
            $this->query->where('transaction_journals.date', '<=', $endStr);
        }

        return $this;
    }

    /**
     * Set the start time of the results to return.
     */
    public function setStart(Carbon $start): GroupCollectorInterface
    {
        $startStr = $start->format('Y-m-d 00:00:00');

        $this->query->where('transaction_journals.date', '>=', $startStr);

        return $this;
    }

    /**
     * Collect transactions updated on a specific date.
     */
    public function setUpdatedAt(Carbon $date): GroupCollectorInterface
    {
        $after  = $date->format('Y-m-d 00:00:00');
        $before = $date->format('Y-m-d 23:59:59');
        $this->query->where('transaction_journals.updated_at', '>=', $after);
        $this->query->where('transaction_journals.updated_at', '<=', $before);

        return $this;
    }

    public function yearAfter(string $year): GroupCollectorInterface
    {
        $this->query->whereYear('transaction_journals.date', '>=', $year);

        return $this;
    }

    public function yearBefore(string $year): GroupCollectorInterface
    {
        $this->query->whereYear('transaction_journals.date', '<=', $year);

        return $this;
    }

    public function yearIs(string $year): GroupCollectorInterface
    {
        $this->query->whereYear('transaction_journals.date', '=', $year);

        return $this;
    }

    public function yearIsNot(string $year): GroupCollectorInterface
    {
        $this->query->whereYear('transaction_journals.date', '!=', $year);

        return $this;
    }
}

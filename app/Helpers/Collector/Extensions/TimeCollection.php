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

    /**
     * Collect transactions after a specific date.
     *
     * @param Carbon $date
     *
     * @return GroupCollectorInterface
     */
    public function setAfter(Carbon $date): GroupCollectorInterface
    {
        $afterStr = $date->format('Y-m-d 00:00:00');
        $this->query->where('transaction_journals.date', '>=', $afterStr);

        return $this;
    }

    /**
     * Collect transactions before a specific date.
     *
     * @param Carbon $date
     *
     * @return GroupCollectorInterface
     */
    public function setBefore(Carbon $date): GroupCollectorInterface
    {
        $beforeStr = $date->format('Y-m-d 00:00:00');
        $this->query->where('transaction_journals.date', '<=', $beforeStr);

        return $this;
    }

    /**
     * Collect transactions created on a specific date.
     *
     * @param Carbon $date
     *
     * @return GroupCollectorInterface
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
     * Set the start and end time of the results to return.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return GroupCollectorInterface
     */
    public function setRange(Carbon $start, Carbon $end): GroupCollectorInterface
    {
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        // always got to end of day / start of day for ranges.
        $startStr = $start->format('Y-m-d 00:00:00');
        $endStr   = $end->format('Y-m-d 23:59:59');

        $this->query->where('transaction_journals.date', '>=', $startStr);
        $this->query->where('transaction_journals.date', '<=', $endStr);

        return $this;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param string $field
     * @return GroupCollectorInterface
     */
    public function setMetaDateRange(Carbon $start, Carbon $end, string $field): GroupCollectorInterface
    {
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        $end = clone $end; // this is so weird, but it works if $end and $start secretly point to the same object.
        $end->endOfDay();
        $start->startOfDay();
        $this->withMetaDate($field);

        $filter              = function (int $index, array $object) use ($field, $start, $end): bool {
            foreach ($object['transactions'] as $transaction) {
                if (array_key_exists('interest_date', $transaction) && $transaction['interest_date'] instanceof Carbon
                ) {
                    return $transaction['interest_date']->gte($start) && $transaction['interest_date']->lte($end);
                }
            }

            return true;
        };
        $this->postFilters[] = $filter;
        return $this;

    }

    /**
     * @inheritDoc
     */
    public function withMetaDate(string $field): GroupCollectorInterface
    {
        $this->joinMetaDataTables();
        $this->query->where('journal_meta.name', '=', $field);
        $this->query->whereNotNull('journal_meta.data');

        return $this;
    }

    /**
     * Collect transactions updated on a specific date.
     *
     * @param Carbon $date
     *
     * @return GroupCollectorInterface
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
}

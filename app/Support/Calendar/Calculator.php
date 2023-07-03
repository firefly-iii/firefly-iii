<?php

/**
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

namespace FireflyIII\Support\Calendar;

use Carbon\Carbon;
use FireflyIII\Support\Calendar\Exceptions\IntervalException;

class Calculator
{
    const DEFAULT_INTERVAL = 1;
    private static array $intervals = [];
    private static ?\SplObjectStorage $intervalMap = null;

    private static function loadIntervalMap(): \SplObjectStorage
    {
        if (self::$intervalMap != null) {
            return self::$intervalMap;
        }
        self::$intervalMap = new \SplObjectStorage();
        foreach (Periodicity::cases() as $interval) {
            $periodicityClass  = __NAMESPACE__ . "\\Periodicity\\{$interval->name}";
            self::$intervals[] = $interval->name;
            self::$intervalMap->attach($interval, new $periodicityClass());
        }
        return self::$intervalMap;
    }

    private static function containsInterval(Periodicity $periodicity): bool
    {
        return self::loadIntervalMap()->contains($periodicity);
    }

    public function isAvailablePeriodicity(Periodicity $periodicity): bool
    {
        return self::containsInterval($periodicity);
    }

    private function skipInterval(int $skip): int
    {
        return self::DEFAULT_INTERVAL + $skip;
    }

    /**
     * @param Carbon      $epoch
     * @param Periodicity $periodicity
     * @param int         $skipInterval
     * @return Carbon
     * @throws IntervalException
     */
    public function nextDateByInterval(Carbon $epoch, Periodicity $periodicity, int $skipInterval = 0): Carbon
    {
        if (!self::isAvailablePeriodicity($periodicity)) {
            throw IntervalException::unavailable($periodicity, self::$intervals);
        }

        /** @var Periodicity\Interval $periodicity */
        $periodicity = self::$intervalMap->offsetGet($periodicity);
        $interval    = $this->skipInterval($skipInterval);
        return $periodicity->nextDate($epoch->clone(), $interval);
    }

}

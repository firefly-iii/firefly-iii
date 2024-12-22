<?php

/*
 * Calculator.php
 * Copyright (c) 2023 Antonio Spinelli https://github.com/tonicospinelli
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

namespace FireflyIII\Support\Calendar;

use Carbon\Carbon;
use FireflyIII\Exceptions\IntervalException;
use SplObjectStorage;

/**
 * Class Calculator
 */
class Calculator
{
    public const int DEFAULT_INTERVAL = 1;
    private static ?SplObjectStorage $intervalMap = null;
    private static array             $intervals   = [];

    /**
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

    public function isAvailablePeriodicity(Periodicity $periodicity): bool
    {
        return self::containsInterval($periodicity);
    }

    private static function containsInterval(Periodicity $periodicity): bool
    {
        return self::loadIntervalMap()->contains($periodicity);
    }

    /**
     * @SuppressWarnings(PHPMD.MissingImport)
     */
    private static function loadIntervalMap(): SplObjectStorage
    {
        if (null !== self::$intervalMap) {
            return self::$intervalMap;
        }
        self::$intervalMap = new SplObjectStorage();
        foreach (Periodicity::cases() as $interval) {
            $periodicityClass  = __NAMESPACE__ . "\\Periodicity\\{$interval->name}";
            self::$intervals[] = $interval->name;
            self::$intervalMap->attach($interval, new $periodicityClass());
        }

        return self::$intervalMap;
    }

    private function skipInterval(int $skip): int
    {
        return self::DEFAULT_INTERVAL + $skip;
    }
}

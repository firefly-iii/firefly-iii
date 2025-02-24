<?php

/*
 * IntervalTestCase.php
 * Copyright (c) 2023 Antonio Spinelli <https://github.com/tonicospinelli>
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

namespace Tests\unit\Support\Calendar\Periodicity;

use FireflyIII\Support\Calendar\Periodicity\Interval;
use Tests\integration\TestCase;

abstract class IntervalTestCase extends TestCase
{
    abstract public static function provideIntervals(): array;

    /**
     * @dataProvider provider
     */
    public function testGivenAnEpochWhenCallTheNextDateThenReturnsTheExpectedDateSuccessful(IntervalProvider $provider): void
    {
        $period = static::factory()->nextDate($provider->epoch);
        self::assertSame($provider->expected->toDateString(), $period->toDateString());
    }

    public static function provider(): iterable
    {
        $intervals = static::provideIntervals();

        /** @var IntervalProvider $interval */
        foreach ($intervals as $interval) {
            yield "{$interval->label}" => [$interval];
        }
    }

    abstract public static function factory(): Interval;
}

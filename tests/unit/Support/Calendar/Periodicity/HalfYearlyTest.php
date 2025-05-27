<?php

/*
 * HalfYearlyTest.php
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

use FireflyIII\Support\Calendar\Periodicity\HalfYearly;
use Carbon\Carbon;
use FireflyIII\Support\Calendar\Periodicity;
use FireflyIII\Support\Calendar\Periodicity\Interval;

/**
 * @group unit-test
 * @group support
 * @group calendar
 * @group periodicity
 *
 * @internal
 *
 * @coversNothing
 */
final class HalfYearlyTest extends IntervalTestCase
{
    public static function factory(): Interval
    {
        return new HalfYearly();
    }

    public static function provideIntervals(): array
    {
        return [
            new IntervalProvider(Carbon::now(), Carbon::now()->addMonthsNoOverflow(6)),
            new IntervalProvider(Carbon::parse('2019-01-29'), Carbon::parse('2019-07-29')),
            new IntervalProvider(Carbon::parse('2019-01-30'), Carbon::parse('2019-07-30')),
            new IntervalProvider(Carbon::parse('2019-01-31'), Carbon::parse('2019-07-31')),
            new IntervalProvider(Carbon::parse('2018-11-01'), Carbon::parse('2019-05-01')),
            new IntervalProvider(Carbon::parse('2019-08-29'), Carbon::parse('2020-02-29')),
            new IntervalProvider(Carbon::parse('2019-08-30'), Carbon::parse('2020-02-29')),
            new IntervalProvider(Carbon::parse('2019-08-31'), Carbon::parse('2020-02-29')),
            new IntervalProvider(Carbon::parse('2020-08-29'), Carbon::parse('2021-02-28')),
            new IntervalProvider(Carbon::parse('2020-08-30'), Carbon::parse('2021-02-28')),
        ];
    }
}

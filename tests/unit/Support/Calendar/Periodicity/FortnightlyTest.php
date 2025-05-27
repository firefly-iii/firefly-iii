<?php

/*
 * FortnightlyTest.php
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

use FireflyIII\Support\Calendar\Periodicity\Fortnightly;
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
final class FortnightlyTest extends IntervalTestCase
{
    public static function factory(): Interval
    {
        return new Fortnightly();
    }

    public static function provideIntervals(): array
    {
        return [
            new IntervalProvider(Carbon::now(), Carbon::now()->addWeeks(2)),
            new IntervalProvider(Carbon::parse('2023-01-31'), Carbon::parse('2023-02-14')),
        ];
    }
}

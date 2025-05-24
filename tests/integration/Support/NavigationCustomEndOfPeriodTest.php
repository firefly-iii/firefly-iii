<?php

/*
 * NavigationCustomEndOfPeriodTest.php
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

namespace Tests\integration\Support;

use Carbon\Carbon;
use FireflyIII\Support\Navigation;
use Tests\integration\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class NavigationCustomEndOfPeriodTest extends TestCase
{
    /**
     * @preserveGlobalState disabled
     */
    public function testGivenADateAndCustomFrequencyWhenCalculateTheDateThenReturnsTheEndOfMonthSuccessful(): void
    {
        $from       = Carbon::parse('2023-08-05');
        $expected   = Carbon::parse('2023-09-04');
        $navigation = new Navigation();

        $period     = $navigation->endOfPeriod($from, 'custom');
        self::assertSame($expected->toDateString(), $period->toDateString());
    }
}

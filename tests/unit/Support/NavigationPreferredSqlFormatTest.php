<?php

/*
 * NavigationPreferredSqlFormatTest.php
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

namespace Tests\unit\Support;

use Override;
use Iterator;
use Carbon\Carbon;
use FireflyIII\Support\Navigation;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\integration\TestCase;

/**
 * @group unit-test
 * @group support
 * @group navigation
 *
 * @internal
 *
 * @coversNothing
 */
final class NavigationPreferredSqlFormatTest extends TestCase
{
    private Navigation $navigation;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->navigation = new Navigation();
    }

    #[DataProvider('provideDates')]
    public function testGivenStartAndEndDatesWhenCallPreferredSqlFormatThenReturnsTheExpectedFormatSuccessful(Carbon $start, Carbon $end, string $expected): void
    {
        $formatPeriod = $this->navigation->preferredSqlFormat($start, $end);
        $this->assertSame($expected, $formatPeriod);
    }

    public static function provideDates(): Iterator
    {
        yield '1 week' => [Carbon::now(), Carbon::now()->addWeek(), '%Y-%m-%d'];

        yield '1 month' => [Carbon::now(), Carbon::now()->addMonth(), '%Y-%m-%d'];

        yield '2 months' => [Carbon::now(), Carbon::now()->addMonths(2), '%Y-%m'];

        yield '3 months' => [Carbon::now(), Carbon::now()->addMonths(3), '%Y-%m'];

        yield '6 months' => [Carbon::now(), Carbon::now()->addMonths(6), '%Y-%m'];

        yield '7 months' => [Carbon::now(), Carbon::now()->addMonths(7), '%Y-%m'];

        yield '11 months' => [Carbon::now(), Carbon::now()->addMonths(11), '%Y-%m'];

        yield '12 months' => [Carbon::now(), Carbon::now()->addMonths(12), '%Y-%m'];

        yield '13 months' => [Carbon::now(), Carbon::now()->addMonths(13), '%Y'];

        yield '16 months' => [Carbon::now(), Carbon::now()->addMonths(16), '%Y'];

        yield '1 year' => [Carbon::now(), Carbon::now()->addYear(), '%Y-%m'];

        yield '2 years' => [Carbon::now(), Carbon::now()->addYears(2), '%Y'];
    }
}

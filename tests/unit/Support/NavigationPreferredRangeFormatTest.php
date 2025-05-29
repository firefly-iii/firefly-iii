<?php

/*
 * NavigationPreferredRangeFormatTest.php
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
final class NavigationPreferredRangeFormatTest extends TestCase
{
    private Navigation $navigation;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->navigation = new Navigation();
    }

    #[DataProvider('providePeriods')]
    public function testGivenStartAndEndDatesWhenCallPreferredRangeFormatThenReturnsTheExpectedFormatSuccessful(Carbon $start, Carbon $end, string $expected): void
    {
        $formatPeriod = $this->navigation->preferredRangeFormat($start, $end);
        self::assertSame($expected, $formatPeriod);
    }

    public static function providePeriods(): Iterator
    {
        yield '1 week' => [Carbon::now(), Carbon::now()->addWeek(), '1D'];

        yield '1 month' => [Carbon::now(), Carbon::now()->addMonth(), '1D'];

        yield '2 months' => [Carbon::now(), Carbon::now()->addMonths(2), '1M'];

        yield '3 months' => [Carbon::now(), Carbon::now()->addMonths(3), '1M'];

        yield '6 months' => [Carbon::now(), Carbon::now()->addMonths(6), '1M'];

        yield '7 months' => [Carbon::now(), Carbon::now()->addMonths(7), '1M'];

        yield '11 months' => [Carbon::now(), Carbon::now()->addMonths(11), '1M'];

        yield '12 months' => [Carbon::now(), Carbon::now()->addMonths(12), '1M'];

        yield '13 months' => [Carbon::now(), Carbon::now()->addMonths(13), '1Y'];

        yield '16 months' => [Carbon::now(), Carbon::now()->addMonths(16), '1Y'];

        yield '1 year' => [Carbon::now(), Carbon::now()->addYear(), '1M'];

        yield '2 years' => [Carbon::now(), Carbon::now()->addYears(2), '1Y'];
    }
}

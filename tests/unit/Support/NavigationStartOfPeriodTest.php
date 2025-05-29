<?php

/*
 * NavigationStartOfPeriodTest.php
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
use Illuminate\Support\Facades\Log;
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
final class NavigationStartOfPeriodTest extends TestCase
{
    private Navigation $navigation;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->navigation = new Navigation();
    }

    #[DataProvider('provideDates')]
    public function testGivenADateAndFrequencyWhenCalculateTheDateThenReturnsTheExpectedDateSuccessful(string $frequency, Carbon $from, Carbon $expected): void
    {
        $period = $this->navigation->startOfPeriod($from, $frequency);
        $this->assertSame($expected->toDateString(), $period->toDateString());
    }

    public static function provideDates(): Iterator
    {
        yield 'custom' => ['custom', Carbon::now(), Carbon::now()];

        yield '1D' => ['1D', Carbon::now(), Carbon::now()->startOfDay()];

        yield 'daily' => ['daily', Carbon::now(), Carbon::now()->startOfDay()];

        yield '1W' => ['1W', Carbon::now(), Carbon::now()->startOfWeek()];

        yield 'week' => ['week', Carbon::now(), Carbon::now()->startOfWeek()];

        yield 'weekly' => ['weekly', Carbon::now(), Carbon::now()->startOfWeek()];

        yield 'month' => ['month', Carbon::now(), Carbon::now()->startOfMonth()];

        yield '1M' => ['1M', Carbon::now(), Carbon::now()->startOfMonth()];

        yield 'monthly' => ['monthly', Carbon::now(), Carbon::now()->startOfMonth()];

        yield '3M' => ['3M', Carbon::now(), Carbon::now()->firstOfQuarter()];

        yield 'quarter' => ['quarter', Carbon::now(), Carbon::now()->firstOfQuarter()];

        yield 'quarterly' => ['quarterly', Carbon::now(), Carbon::now()->firstOfQuarter()];

        yield 'year' => ['year', Carbon::now(), Carbon::now()->startOfYear()];

        yield 'yearly' => ['yearly', Carbon::now(), Carbon::now()->startOfYear()];

        yield '1Y' => ['1Y', Carbon::now(), Carbon::now()->startOfYear()];

        yield 'half-year' => ['half-year', Carbon::parse('2023-05-20'), Carbon::parse('2023-01-01')->startOfYear()];

        yield '6M' => ['6M', Carbon::parse('2023-08-20'), Carbon::parse('2023-07-01')];

        yield 'last7' => ['last7', Carbon::now(), Carbon::now()->subDays(7)->startOfDay()];

        yield 'last30' => ['last30', Carbon::now(), Carbon::now()->subDays(30)->startOfDay()];

        yield 'last90' => ['last90', Carbon::now(), Carbon::now()->subDays(90)->startOfDay()];

        yield 'last365' => ['last365', Carbon::now(), Carbon::now()->subDays(365)->startOfDay()];

        yield 'MTD' => ['MTD', Carbon::now(), Carbon::now()->startOfMonth()->startOfDay()];

        yield 'QTD' => ['QTD', Carbon::now(), Carbon::now()->firstOfQuarter()->startOfDay()];

        yield 'YTD' => ['YTD', Carbon::now(), Carbon::now()->startOfYear()->startOfDay()];
    }

    #[DataProvider('provideUnknownFrequencies')]
    public function testGivenADateAndUnknownFrequencyWhenCalculateTheDateThenReturnsTheSameDateSuccessful(string $frequency, Carbon $from, Carbon $expected): void
    {
        Log::spy();

        Log::shouldReceive('error')
            ->with(sprintf('Cannot do startOfPeriod for $repeat_freq "%s"', $frequency))
            ->andReturnNull()
        ;

        $period = $this->navigation->startOfPeriod($from, $frequency);
        $this->assertSame($expected->toDateString(), $period->toDateString());
    }

    public static function provideUnknownFrequencies(): Iterator
    {
        yield '1day' => ['1day', Carbon::now(), Carbon::now()];

        yield 'unknown' => ['unknown', Carbon::now(), Carbon::now()->startOfDay()];

        yield 'empty' => ['', Carbon::now(), Carbon::now()->startOfDay()];
    }
}

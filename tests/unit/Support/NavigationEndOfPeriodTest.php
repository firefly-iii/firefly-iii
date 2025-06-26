<?php

/**
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
final class NavigationEndOfPeriodTest extends TestCase
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
        $period = clone $this->navigation->endOfPeriod($from, $frequency);
        $this->assertSame($expected->toDateString(), $period->toDateString());
    }

    public static function provideDates(): Iterator
    {
        yield '1D' => ['1D', Carbon::now(), Carbon::now()->endOfDay()];

        yield 'daily' => ['daily', Carbon::now(), Carbon::now()->endOfDay()];

        yield '1W' => ['1W', Carbon::now(), Carbon::now()->addWeek()->subDay()->endOfDay()];

        yield 'week' => ['week', Carbon::now(), Carbon::now()->addWeek()->subDay()->endOfDay()];

        yield 'weekly' => ['weekly', Carbon::now(), Carbon::now()->addWeek()->subDay()->endOfDay()];

        yield 'month' => ['month', Carbon::now(), Carbon::now()->addMonth()->subDay()->endOfDay()];

        yield '1M' => ['1M', Carbon::now(), Carbon::now()->addMonth()->subDay()->endOfDay()];

        yield 'monthly' => ['monthly', Carbon::now(), Carbon::now()->addMonth()->subDay()->endOfDay()];

        yield '3M' => ['3M', Carbon::now(), Carbon::now()->addQuarter()->subDay()->endOfDay()];

        yield 'quarter' => ['quarter', Carbon::now(), Carbon::now()->addQuarter()->subDay()->endOfDay()];

        yield 'quarterly' => ['quarterly', Carbon::now(), Carbon::now()->addQuarter()->subDay()->endOfDay()];

        yield 'year' => ['year', Carbon::now(), Carbon::now()->addYearNoOverflow()->subDay()->endOfDay()];

        yield 'yearly' => ['yearly', Carbon::now(), Carbon::now()->addYearNoOverflow()->subDay()->endOfDay()];

        yield '1Y' => ['1Y', Carbon::now(), Carbon::now()->addYearNoOverflow()->subDay()->endOfDay()];

        yield 'half-year' => ['half-year', Carbon::parse('2023-05-20'), Carbon::parse('2023-11-19')->endOfDay()];

        yield '6M' => ['6M', Carbon::parse('2023-08-20'), Carbon::parse('2024-02-19')];

        yield 'last7' => ['last7', Carbon::now(), Carbon::now()->addDays(7)->endOfDay()];

        yield 'last30' => ['last30', Carbon::now(), Carbon::now()->addDays(30)->endOfDay()];

        yield 'last90' => ['last90', Carbon::now(), Carbon::now()->addDays(90)->endOfDay()];

        yield 'last365' => ['last365', Carbon::now(), Carbon::now()->addDays(365)->endOfDay()];

        yield 'MTD' => ['MTD', Carbon::now(),
            Carbon::now()->isSameMonth(Carbon::now()) ? Carbon::now()->endOfDay() : Carbon::now()->endOfMonth()];

        yield 'QTD' => ['QTD', Carbon::now(), Carbon::now()->firstOfQuarter()->startOfDay()];

        yield 'YTD' => ['YTD', Carbon::now(), Carbon::now()->firstOfYear()->startOfDay()];

        yield 'week 2023-08-05 to 2023-08-11' => ['1W', Carbon::parse('2023-08-05'), Carbon::parse('2023-08-11')->endOfDay()];
    }

    #[DataProvider('provideUnknownFrequencies')]
    public function testGivenADateAndUnknownFrequencyWhenCalculateTheDateThenReturnsTheSameDateSuccessful(string $frequency, Carbon $from, Carbon $expected): void
    {
        Log::spy();

        $period          = $this->navigation->endOfPeriod($from, $frequency);
        $this->assertSame($expected->toDateString(), $period->toDateString());
        $expectedMessage = sprintf('Cannot do endOfPeriod for $repeat_freq "%s"', $frequency);

        Log::shouldHaveReceived('error', [$expectedMessage]);
    }

    public static function provideUnknownFrequencies(): Iterator
    {
        yield '1day' => ['1day', Carbon::now(), Carbon::now()];

        yield 'unknown' => ['unknown', Carbon::now(), Carbon::now()->startOfDay()];

        yield 'empty' => ['', Carbon::now(), Carbon::now()->startOfDay()];
    }
}

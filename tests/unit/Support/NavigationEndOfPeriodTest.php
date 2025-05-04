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

use Carbon\Carbon;
use FireflyIII\Support\Navigation;
use Illuminate\Support\Facades\Log;
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
    private readonly Navigation $navigation;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->navigation = new Navigation();
    }

    /**
     * @dataProvider        provideDates
     */
    public function testGivenADateAndFrequencyWhenCalculateTheDateThenReturnsTheExpectedDateSuccessful(string $frequency, Carbon $from, Carbon $expected): void
    {
        $period = clone $this->navigation->endOfPeriod($from, $frequency);
        self::assertSame($expected->toDateString(), $period->toDateString());
    }

    public static function provideDates(): iterable
    {
        return [
            '1D'                            => ['frequency' => '1D', 'from' => Carbon::now(), 'expected' => Carbon::now()->endOfDay()],
            'daily'                         => ['frequency' => 'daily', 'from' => Carbon::now(), 'expected' => Carbon::now()->endOfDay()],
            '1W'                            => ['frequency' => '1W', 'from' => Carbon::now(), 'expected' => Carbon::now()->addWeek()->subDay()->endOfDay()],
            'week'                          => ['frequency' => 'week', 'from' => Carbon::now(), 'expected' => Carbon::now()->addWeek()->subDay()->endOfDay()],
            'weekly'                        => ['frequency' => 'weekly', 'from' => Carbon::now(), 'expected' => Carbon::now()->addWeek()->subDay()->endOfDay()],
            'month'                         => ['frequency' => 'month', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonth()->subDay()->endOfDay()],
            '1M'                            => ['frequency' => '1M', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonth()->subDay()->endOfDay()],
            'monthly'                       => ['frequency' => 'monthly', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonth()->subDay()->endOfDay()],
            '3M'                            => ['frequency' => '3M', 'from' => Carbon::now(), 'expected' => Carbon::now()->addQuarter()->subDay()->endOfDay()],
            'quarter'                       => ['frequency' => 'quarter', 'from' => Carbon::now(), 'expected' => Carbon::now()->addQuarter()->subDay()->endOfDay()],
            'quarterly'                     => ['frequency' => 'quarterly', 'from' => Carbon::now(), 'expected' => Carbon::now()->addQuarter()->subDay()->endOfDay()],
            'year'                          => ['frequency' => 'year', 'from' => Carbon::now(), 'expected' => Carbon::now()->addYearNoOverflow()->subDay()->endOfDay()],
            'yearly'                        => ['frequency' => 'yearly', 'from' => Carbon::now(), 'expected' => Carbon::now()->addYearNoOverflow()->subDay()->endOfDay()],
            '1Y'                            => ['frequency' => '1Y', 'from' => Carbon::now(), 'expected' => Carbon::now()->addYearNoOverflow()->subDay()->endOfDay()],
            'half-year'                     => ['frequency' => 'half-year', 'from' => Carbon::parse('2023-05-20'), 'expected' => Carbon::parse('2023-11-19')->endOfDay()],
            '6M'                            => ['frequency' => '6M', 'from' => Carbon::parse('2023-08-20'), 'expected' => Carbon::parse('2024-02-19')],
            'last7'                         => ['frequency' => 'last7', 'from' => Carbon::now(), 'expected' => Carbon::now()->addDays(7)->endOfDay()],
            'last30'                        => ['frequency' => 'last30', 'from' => Carbon::now(), 'expected' => Carbon::now()->addDays(30)->endOfDay()],
            'last90'                        => ['frequency' => 'last90', 'from' => Carbon::now(), 'expected' => Carbon::now()->addDays(90)->endOfDay()],
            'last365'                       => ['frequency' => 'last365', 'from' => Carbon::now(), 'expected' => Carbon::now()->addDays(365)->endOfDay()],
            'MTD'                           => ['frequency' => 'MTD', 'from' => Carbon::now(),
                'expected'                                  => Carbon::now()->isSameMonth(Carbon::now()) ? Carbon::now()->endOfDay() : Carbon::now()->endOfMonth()],
            'QTD'                           => ['frequency' => 'QTD', 'from' => Carbon::now(), 'expected' => Carbon::now()->firstOfQuarter()->startOfDay()],
            'YTD'                           => ['frequency' => 'YTD', 'from' => Carbon::now(), 'expected' => Carbon::now()->firstOfYear()->startOfDay()],
            'week 2023-08-05 to 2023-08-11' => ['frequency' => '1W', 'from' => Carbon::parse('2023-08-05'), 'expected' => Carbon::parse('2023-08-11')->endOfDay()],
        ];
    }

    /**
     * @dataProvider provideUnknownFrequencies
     */
    public function testGivenADateAndUnknownFrequencyWhenCalculateTheDateThenReturnsTheSameDateSuccessful(string $frequency, Carbon $from, Carbon $expected): void
    {
        Log::spy();

        $period          = $this->navigation->endOfPeriod($from, $frequency);
        self::assertSame($expected->toDateString(), $period->toDateString());
        $expectedMessage = sprintf('Cannot do endOfPeriod for $repeat_freq "%s"', $frequency);

        Log::shouldHaveReceived('error', [$expectedMessage]);
    }

    public static function provideUnknownFrequencies(): iterable
    {
        return [
            '1day'    => ['frequency' => '1day', 'from' => Carbon::now(), 'expected' => Carbon::now()],
            'unknown' => ['frequency' => 'unknown', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfDay()],
            'empty'   => ['frequency' => '', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfDay()],
        ];
    }
}

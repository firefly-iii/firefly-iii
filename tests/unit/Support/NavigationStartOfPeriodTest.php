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

    protected function setUp(): void
    {
        parent::setUp();
        $this->navigation = new Navigation();
    }

    #[DataProvider('provideDates')]
    public function testGivenADateAndFrequencyWhenCalculateTheDateThenReturnsTheExpectedDateSuccessful(string $frequency, Carbon $from, Carbon $expected): void
    {
        $period = $this->navigation->startOfPeriod($from, $frequency);
        self::assertSame($expected->toDateString(), $period->toDateString());
    }

    public static function provideDates(): iterable
    {
        return [
            'custom'    => ['frequency' => 'custom', 'from' => Carbon::now(), 'expected' => Carbon::now()],
            '1D'        => ['frequency' => '1D', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfDay()],
            'daily'     => ['frequency' => 'daily', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfDay()],
            '1W'        => ['frequency' => '1W', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfWeek()],
            'week'      => ['frequency' => 'week', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfWeek()],
            'weekly'    => ['frequency' => 'weekly', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfWeek()],
            'month'     => ['frequency' => 'month', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfMonth()],
            '1M'        => ['frequency' => '1M', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfMonth()],
            'monthly'   => ['frequency' => 'monthly', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfMonth()],
            '3M'        => ['frequency' => '3M', 'from' => Carbon::now(), 'expected' => Carbon::now()->firstOfQuarter()],
            'quarter'   => ['frequency' => 'quarter', 'from' => Carbon::now(), 'expected' => Carbon::now()->firstOfQuarter()],
            'quarterly' => ['frequency' => 'quarterly', 'from' => Carbon::now(), 'expected' => Carbon::now()->firstOfQuarter()],
            'year'      => ['frequency' => 'year', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfYear()],
            'yearly'    => ['frequency' => 'yearly', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfYear()],
            '1Y'        => ['frequency' => '1Y', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfYear()],
            'half-year' => ['frequency' => 'half-year', 'from' => Carbon::parse('2023-05-20'), 'expected' => Carbon::parse('2023-01-01')->startOfYear()],
            '6M'        => ['frequency' => '6M', 'from' => Carbon::parse('2023-08-20'), 'expected' => Carbon::parse('2023-07-01')],
            'last7'     => ['frequency' => 'last7', 'from' => Carbon::now(), 'expected' => Carbon::now()->subDays(7)->startOfDay()],
            'last30'    => ['frequency' => 'last30', 'from' => Carbon::now(), 'expected' => Carbon::now()->subDays(30)->startOfDay()],
            'last90'    => ['frequency' => 'last90', 'from' => Carbon::now(), 'expected' => Carbon::now()->subDays(90)->startOfDay()],
            'last365'   => ['frequency' => 'last365', 'from' => Carbon::now(), 'expected' => Carbon::now()->subDays(365)->startOfDay()],
            'MTD'       => ['frequency' => 'MTD', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfMonth()->startOfDay()],
            'QTD'       => ['frequency' => 'QTD', 'from' => Carbon::now(), 'expected' => Carbon::now()->firstOfQuarter()->startOfDay()],
            'YTD'       => ['frequency' => 'YTD', 'from' => Carbon::now(), 'expected' => Carbon::now()->startOfYear()->startOfDay()],
        ];
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
        self::assertSame($expected->toDateString(), $period->toDateString());
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

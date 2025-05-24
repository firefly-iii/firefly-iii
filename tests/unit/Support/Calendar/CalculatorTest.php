<?php

/*
 * CalculatorTest.php
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

namespace Tests\unit\Support\Calendar;

use FireflyIII\Exceptions\IntervalException;
use FireflyIII\Support\Calendar\Calculator;
use FireflyIII\Support\Calendar\Periodicity;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\integration\TestCase;
use Tests\unit\Support\Calendar\Periodicity\BimonthlyTest;
use Tests\unit\Support\Calendar\Periodicity\DailyTest;
use Tests\unit\Support\Calendar\Periodicity\FortnightlyTest;
use Tests\unit\Support\Calendar\Periodicity\HalfYearlyTest;
use Tests\unit\Support\Calendar\Periodicity\IntervalProvider;
use Tests\unit\Support\Calendar\Periodicity\MonthlyTest;
use Tests\unit\Support\Calendar\Periodicity\QuarterlyTest;
use Tests\unit\Support\Calendar\Periodicity\WeeklyTest;
use Tests\unit\Support\Calendar\Periodicity\YearlyTest;

/**
 * @group unit-test
 * @group support
 * @group calendar
 * @group calculator
 *
 * @internal
 *
 * @coversNothing
 */
final class CalculatorTest extends TestCase
{
    private static function convert(Periodicity $periodicity, array $intervals): array
    {
        $periodicityIntervals = [];

        /** @var IntervalProvider $interval */
        foreach ($intervals as $index => $interval) {
            $calculator                                             = CalculatorProvider::from($periodicity, $interval);

            $periodicityIntervals["#{$index} {$calculator->label}"] = [$calculator];
        }

        return $periodicityIntervals;
    }

    /**
     *
     *
     * @throws IntervalException
     */
    #[DataProvider('provideAllPeriodicity')]
    public function testGivenADailyPeriodicityWhenCallTheNextDateByIntervalMethodThenReturnsTheExpectedDateSuccessful(CalculatorProvider $provider): void
    {
        $calculator = new Calculator();
        $period     = $calculator->nextDateByInterval($provider->epoch(), $provider->periodicity);
        self::assertSame($provider->expected()->toDateString(), $period->toDateString());
    }

    public static function provideAllPeriodicity(): iterable
    {
        $intervals = [];
        $intervals = array_merge($intervals, self::convert(Periodicity::Daily, DailyTest::provideIntervals()));
        $intervals = array_merge($intervals, self::convert(Periodicity::Weekly, WeeklyTest::provideIntervals()));
        $intervals = array_merge($intervals, self::convert(Periodicity::Fortnightly, FortnightlyTest::provideIntervals()));
        $intervals = array_merge($intervals, self::convert(Periodicity::Monthly, MonthlyTest::provideIntervals()));
        $intervals = array_merge($intervals, self::convert(Periodicity::Bimonthly, BimonthlyTest::provideIntervals()));
        $intervals = array_merge($intervals, self::convert(Periodicity::Quarterly, QuarterlyTest::provideIntervals()));
        $intervals = array_merge($intervals, self::convert(Periodicity::HalfYearly, HalfYearlyTest::provideIntervals()));
        $intervals = array_merge($intervals, self::convert(Periodicity::Yearly, YearlyTest::provideIntervals()));

        /** @var IntervalProvider $interval */
        foreach ($intervals as $label => $interval) {
            yield $label => $interval;
        }
    }

    /**
     *
     *
     * @throws IntervalException
     */
    #[DataProvider('provideSkippedIntervals')]
    public function testGivenAnEpochWithSkipIntervalNumberWhenCallTheNextDateBySkippedIntervalMethodThenReturnsTheExpectedDateSuccessful(CalculatorProvider $provider): void
    {
        $calculator = new Calculator();
        $period     = $calculator->nextDateByInterval($provider->epoch(), $provider->periodicity, $provider->skip);
        self::assertSame($provider->expected()->toDateString(), $period->toDateString());
    }

    public static function provideSkippedIntervals(): iterable
    {
        return CalculatorProvider::providePeriodicityWithSkippedIntervals();
    }
}

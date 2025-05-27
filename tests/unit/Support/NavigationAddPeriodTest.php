<?php

/*
 * NavigationAddPeriodTest.php
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
use FireflyIII\Support\Calendar\Periodicity;
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
final class NavigationAddPeriodTest extends TestCase
{
    private Navigation $navigation;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->navigation = new Navigation();
    }

    #[DataProvider('providePeriodsWithSkippingParam')]
    public function testGivenAFrequencyAndSkipIntervalWhenCalculateTheDateThenReturnsTheSkippedDateSuccessful(int $skip, string $frequency, Carbon $from, Carbon $expected): void
    {
        $period = $this->navigation->addPeriod($from, $frequency, $skip);
        self::assertSame($expected->toDateString(), $period->toDateString());
    }

    public static function providePeriodsWithSkippingParam(): iterable
    {
        $intervals = [
            '2019-01-31 to 2019-02-11' => ['skip' => 10, 'frequency' => 'daily', 'from' => Carbon::parse('2019-01-31'), 'expected' => Carbon::parse('2019-02-11')],
            '1D'                       => ['skip' => 1, 'frequency' => '1D', 'from' => Carbon::now(), 'expected' => Carbon::now()->addDays(2)],
            'daily'                    => ['skip' => 1, 'frequency' => 'daily', 'from' => Carbon::now(), 'expected' => Carbon::now()->addDays(2)],
            '1W'                       => ['skip' => 1, 'frequency' => '1W', 'from' => Carbon::now(), 'expected' => Carbon::now()->addWeeks(2)],
            'weekly'                   => ['skip' => 1, 'frequency' => 'weekly', 'from' => Carbon::now(), 'expected' => Carbon::now()->addWeeks(2)],
            'week'                     => ['skip' => 1, 'frequency' => 'week', 'from' => Carbon::now(), 'expected' => Carbon::now()->addWeeks(2)],
            '1M'                       => ['skip' => 1, 'frequency' => '1M', 'from' => Carbon::parse('2023-06-25'), 'expected' => Carbon::parse('2023-06-25')->addMonthsNoOverflow(2)],
            'month'                    => ['skip' => 1, 'frequency' => 'month', 'from' => Carbon::parse('2023-06-25'), 'expected' => Carbon::parse('2023-06-25')->addMonthsNoOverflow(2)],
            'monthly'                  => ['skip' => 1, 'frequency' => 'monthly', 'from' => Carbon::parse('2023-06-25'), 'expected' => Carbon::parse('2023-06-25')->addMonthsNoOverflow(2)],
            '2019-01-29 to 2019-03-29' => ['skip' => 1, 'frequency' => 'monthly', 'from' => Carbon::parse('2019-01-29'), 'expected' => Carbon::parse('2019-03-29')],
            '2019-01-30 to 2019-03-30' => ['skip' => 1, 'frequency' => 'monthly', 'from' => Carbon::parse('2019-01-30'), 'expected' => Carbon::parse('2019-03-30')],
            '2019-01-31 to 2019-03-31' => ['skip' => 1, 'frequency' => 'monthly', 'from' => Carbon::parse('2019-01-31'), 'expected' => Carbon::parse('2019-03-31')],
            '2023-03-31 to 2023-05-31' => ['skip' => 1, 'frequency' => 'monthly', 'from' => Carbon::parse('2023-03-31'), 'expected' => Carbon::parse('2023-05-31')],
            '2023-05-31 to 2023-07-31' => ['skip' => 1, 'frequency' => 'monthly', 'from' => Carbon::parse('2023-05-31'), 'expected' => Carbon::parse('2023-07-31')],
            '2023-08-31 to 2023-10-31' => ['skip' => 1, 'frequency' => 'monthly', 'from' => Carbon::parse('2023-08-31'), 'expected' => Carbon::parse('2023-10-31')],
            '2023-10-31 to 2023-12-31' => ['skip' => 1, 'frequency' => 'monthly', 'from' => Carbon::parse('2023-10-31'), 'expected' => Carbon::parse('2023-12-31')],
            '2023-01-31 to 2023-03-30' => ['skip' => 2, 'frequency' => 'monthly', 'from' => Carbon::parse('2023-01-31'), 'expected' => Carbon::parse('2023-04-30')],
            '3M'                       => ['skip' => 1, 'frequency' => '3M', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonthsNoOverflow(6)],
            'quarter'                  => ['skip' => 1, 'frequency' => 'quarter', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonthsNoOverflow(6)],
            'quarterly'                => ['skip' => 1, 'frequency' => 'quarterly', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonthsNoOverflow(6)],
            'quarter_2'                => ['skip' => 2, 'frequency' => 'quarter', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonthsNoOverflow(9)],
            'quarterly_2'              => ['skip' => 2, 'frequency' => 'quarterly', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonthsNoOverflow(9)],
            'quarter_3'                => ['skip' => 2, 'frequency' => 'quarter', 'from' => Carbon::parse('2023-01-01'), 'expected' => Carbon::parse('2023-10-01')],
            '6M'                       => ['skip' => 1, 'frequency' => '6M', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonthsNoOverflow(12)],
            'half-year'                => ['skip' => 1, 'frequency' => 'half-year', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonthsNoOverflow(12)],
            'year'                     => ['skip' => 1, 'frequency' => 'year', 'from' => Carbon::now(), 'expected' => Carbon::now()->addYears(2)],
            'yearly'                   => ['skip' => 1, 'frequency' => 'yearly', 'from' => Carbon::now(), 'expected' => Carbon::now()->addYears(2)],
            '1Y'                       => ['skip' => 1, 'frequency' => '1Y', 'from' => Carbon::now(), 'expected' => Carbon::now()->addYears(2)],
            '2023-02-01 to 2023-02-15' => ['skip' => 1, 'frequency' => 'last7', 'from' => Carbon::parse('2023-02-01'), 'expected' => Carbon::parse('2023-02-15')],
            'last7'                    => ['skip' => 1, 'frequency' => 'last7', 'from' => Carbon::now(), 'expected' => Carbon::now()->addDays(14)],
            'last30'                   => ['skip' => 1, 'frequency' => 'last30', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonthsNoOverflow(2)],
            'last90'                   => ['skip' => 1, 'frequency' => 'last90', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonthsNoOverflow(6)],
            'last365'                  => ['skip' => 1, 'frequency' => 'last365', 'from' => Carbon::now(), 'expected' => Carbon::now()->addYears(2)],
            'MTD'                      => ['skip' => 1, 'frequency' => 'MTD', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonthsNoOverflow(2)],
            'QTD'                      => ['skip' => 1, 'frequency' => 'QTD', 'from' => Carbon::now(), 'expected' => Carbon::now()->addMonthsNoOverflow(6)],
            'YTD'                      => ['skip' => 1, 'frequency' => 'YTD', 'from' => Carbon::now(), 'expected' => Carbon::now()->addYears(2)],
        ];
        foreach ($intervals as $interval) {
            yield "{$interval['frequency']} {$interval['from']->toDateString()} to {$interval['expected']->toDateString()}" => $interval;
        }
    }

    #[DataProvider('providePeriods')]
    public function testGivenAFrequencyWhenCalculateTheDateThenReturnsTheExpectedDateSuccessful(string $frequency, Carbon $from, Carbon $expected): void
    {
        $period = $this->navigation->addPeriod($from, $frequency, 0);
        self::assertSame($expected->toDateString(), $period->toDateString());
    }

    public static function providePeriods(): Iterator
    {
        yield '1D' => ['1D', Carbon::now(), Carbon::tomorrow()];

        yield 'daily' => ['daily', Carbon::now(), Carbon::tomorrow()];

        yield '1W' => ['1W', Carbon::now(), Carbon::now()->addWeeks(1)];

        yield 'weekly' => ['weekly', Carbon::now(), Carbon::now()->addWeeks(1)];

        yield 'week' => ['week', Carbon::now(), Carbon::now()->addWeeks(1)];

        yield '3M' => ['3M', Carbon::now(), Carbon::now()->addMonthsNoOverflow(3)];

        yield 'quarter' => ['quarter', Carbon::now(), Carbon::now()->addMonthsNoOverflow(3)];

        yield 'quarterly' => ['quarterly', Carbon::now(), Carbon::now()->addMonthsNoOverflow(3)];

        yield '6M' => ['6M', Carbon::now(), Carbon::now()->addMonthsNoOverflow(6)];

        yield 'half-year' => ['half-year', Carbon::now(), Carbon::now()->addMonthsNoOverflow(6)];

        yield 'year' => ['year', Carbon::now(), Carbon::now()->addYears(1)];

        yield 'yearly' => ['yearly', Carbon::now(), Carbon::now()->addYears(1)];

        yield '1Y' => ['1Y', Carbon::now(), Carbon::now()->addYears(1)];

        yield 'last7' => ['last7', Carbon::now(), Carbon::now()->addDays(7)];

        yield 'last30' => ['last30', Carbon::now(), Carbon::now()->addMonthsNoOverflow(1)];

        yield 'last90' => ['last90', Carbon::now(), Carbon::now()->addMonthsNoOverflow(3)];

        yield 'last365' => ['last365', Carbon::now(), Carbon::now()->addYears(1)];

        yield 'MTD' => ['MTD', Carbon::now(), Carbon::now()->addMonthsNoOverflow(1)];

        yield 'QTD' => ['QTD', Carbon::now(), Carbon::now()->addMonthsNoOverflow(3)];

        yield 'YTD' => ['YTD', Carbon::now(), Carbon::now()->addYears(1)];
    }

    #[DataProvider('provideFrequencies')]
    public function testGivenAIntervalWhenCallTheNextDateByIntervalMethodThenReturnsTheExpectedDateSuccessful(Periodicity $periodicity, Carbon $from, Carbon $expected): void
    {
        $period = $this->navigation->nextDateByInterval($from, $periodicity);
        self::assertSame($expected->toDateString(), $period->toDateString());
    }

    public static function provideFrequencies(): Iterator
    {
        yield Periodicity::Daily->name => [Periodicity::Daily, Carbon::now(), Carbon::tomorrow()];

        yield Periodicity::Weekly->name => [Periodicity::Weekly, Carbon::now(), Carbon::now()->addWeeks(1)];

        yield Periodicity::Fortnightly->name => [Periodicity::Fortnightly, Carbon::now(), Carbon::now()->addWeeks(2)];

        yield Periodicity::Monthly->name => [Periodicity::Monthly, Carbon::now(), Carbon::now()->addMonthsNoOverflow(1)];

        yield '2019-01-01 to 2019-02-01' => [Periodicity::Monthly, Carbon::parse('2019-01-01'), Carbon::parse('2019-02-01')];

        yield '2019-01-29 to 2019-02-28' => [Periodicity::Monthly, Carbon::parse('2019-01-29'), Carbon::parse('2019-02-28')];

        yield '2019-01-30 to 2019-02-28' => [Periodicity::Monthly, Carbon::parse('2019-01-30'), Carbon::parse('2019-02-28')];

        yield '2019-01-31 to 2019-02-28' => [Periodicity::Monthly, Carbon::parse('2019-01-31'), Carbon::parse('2019-02-28')];

        yield '2023-03-31 to 2023-04-30' => [Periodicity::Monthly, Carbon::parse('2023-03-31'), Carbon::parse('2023-04-30')];

        yield '2023-05-31 to 2023-06-30' => [Periodicity::Monthly, Carbon::parse('2023-05-31'), Carbon::parse('2023-06-30')];

        yield '2023-08-31 to 2023-09-30' => [Periodicity::Monthly, Carbon::parse('2023-08-31'), Carbon::parse('2023-09-30')];

        yield '2023-10-31 to 2023-11-30' => [Periodicity::Monthly, Carbon::parse('2023-10-31'), Carbon::parse('2023-11-30')];

        yield Periodicity::Quarterly->name => [Periodicity::Quarterly, Carbon::now(), Carbon::now()->addMonthsNoOverflow(3)];

        yield '2019-01-29 to 2020-04-29' => [Periodicity::Quarterly, Carbon::parse('2019-01-29'), Carbon::parse('2019-04-29')];

        yield '2019-01-30 to 2020-04-30' => [Periodicity::Quarterly, Carbon::parse('2019-01-30'), Carbon::parse('2019-04-30')];

        yield '2019-01-31 to 2020-04-30' => [Periodicity::Quarterly, Carbon::parse('2019-01-31'), Carbon::parse('2019-04-30')];

        yield Periodicity::HalfYearly->name => [Periodicity::HalfYearly, Carbon::now(), Carbon::now()->addMonthsNoOverflow(6)];

        yield '2019-01-31 to 2020-07-29' => [Periodicity::HalfYearly, Carbon::parse('2019-01-29'), Carbon::parse('2019-07-29')];

        yield '2019-01-31 to 2020-07-30' => [Periodicity::HalfYearly, Carbon::parse('2019-01-30'), Carbon::parse('2019-07-30')];

        yield '2019-01-31 to 2020-07-31' => [Periodicity::HalfYearly, Carbon::parse('2019-01-31'), Carbon::parse('2019-07-31')];

        yield Periodicity::Yearly->name => [Periodicity::Yearly, Carbon::now(), Carbon::now()->addYears(1)];

        yield '2020-02-29 to 2021-02-28' => [Periodicity::Yearly, Carbon::parse('2020-02-29'), Carbon::parse('2021-02-28')];
    }

    #[DataProvider('provideMonthPeriods')]
    public function testGivenAMonthFrequencyWhenCalculateTheDateThenReturnsTheLastDayOfMonthSuccessful(string $frequency, Carbon $from, Carbon $expected): void
    {
        $period = $this->navigation->addPeriod($from, $frequency, 0);
        self::assertSame($expected->toDateString(), $period->toDateString());
    }

    public static function provideMonthPeriods(): Iterator
    {
        yield '1M' => ['1M', Carbon::parse('2023-06-25'), Carbon::parse('2023-06-25')->addMonthsNoOverflow(1)];

        yield 'month' => ['month', Carbon::parse('2023-06-25'), Carbon::parse('2023-06-25')->addMonthsNoOverflow(1)];

        yield 'monthly' => ['monthly', Carbon::parse('2023-06-25'), Carbon::parse('2023-06-25')->addMonthsNoOverflow(1)];

        yield '2019-01-29 to 2019-02-28' => ['monthly', Carbon::parse('2019-01-29'), Carbon::parse('2019-02-28')];

        yield '2019-01-30 to 2019-02-28' => ['monthly', Carbon::parse('2019-01-30'), Carbon::parse('2019-02-28')];

        yield '2019-01-31 to 2019-02-28' => ['monthly', Carbon::parse('2019-01-31'), Carbon::parse('2019-02-28')];

        yield '2023-03-31 to 2023-04-30' => ['monthly', Carbon::parse('2023-03-31'), Carbon::parse('2023-04-30')];

        yield '2023-05-31 to 2023-06-30' => ['monthly', Carbon::parse('2023-05-31'), Carbon::parse('2023-06-30')];

        yield '2023-08-31 to 2023-09-30' => ['monthly', Carbon::parse('2023-08-31'), Carbon::parse('2023-09-30')];

        yield '2023-10-31 to 2023-11-30' => ['monthly', Carbon::parse('2023-10-31'), Carbon::parse('2023-11-30')];
    }
}

<?php


/*
 * CalculatorProvider.php
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

use Carbon\Carbon;
use FireflyIII\Support\Calendar\Periodicity;
use Generator;
use Tests\unit\Support\Calendar\Periodicity\IntervalProvider;

readonly class CalculatorProvider
{
    public IntervalProvider $intervalProvider;
    public string           $label;
    public Periodicity      $periodicity;
    public int              $skip;

    private function __construct(IntervalProvider $intervalProvider, Periodicity $periodicity, int $skip = 0)
    {
        $this->skip             = $skip;
        $this->intervalProvider = $intervalProvider;
        $this->periodicity      = $periodicity;
        $this->label            = "{$periodicity->name} {$intervalProvider->label}";
    }

    public static function providePeriodicityWithSkippedIntervals(): Generator
    {
        $intervals = [
            CalculatorProvider::from(Periodicity::Daily, new IntervalProvider(Carbon::now(), Carbon::now()->addDays(2)), 1),
            CalculatorProvider::from(Periodicity::Daily, new IntervalProvider(Carbon::now(), Carbon::now()->addDays(3)), 2),
            CalculatorProvider::from(Periodicity::Daily, new IntervalProvider(Carbon::parse('2023-01-31'), Carbon::parse('2023-02-11')), 10),

            CalculatorProvider::from(Periodicity::Weekly, new IntervalProvider(Carbon::now(), Carbon::now()->addWeeks(3)), 2),
            CalculatorProvider::from(Periodicity::Weekly, new IntervalProvider(Carbon::parse('2023-01-31'), Carbon::parse('2023-02-14')), 1),

            CalculatorProvider::from(Periodicity::Fortnightly, new IntervalProvider(Carbon::now(), Carbon::now()->addWeeks(4)), 1),
            CalculatorProvider::from(Periodicity::Fortnightly, new IntervalProvider(Carbon::parse('2023-01-29'), Carbon::parse('2023-02-26')), 1),
            CalculatorProvider::from(Periodicity::Fortnightly, new IntervalProvider(Carbon::parse('2023-01-30'), Carbon::parse('2023-02-27')), 1),
            CalculatorProvider::from(Periodicity::Fortnightly, new IntervalProvider(Carbon::parse('2023-01-31'), Carbon::parse('2023-02-28')), 1),

            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::now(), Carbon::now()->addMonthsNoOverflow(2)), 1),
            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::parse('2019-12-30'), Carbon::parse('2020-02-29')), 1),
            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::parse('2019-12-31'), Carbon::parse('2020-02-29')), 1),
            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::parse('2020-01-29'), Carbon::parse('2020-03-29')), 1),
            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::parse('2020-01-31'), Carbon::parse('2020-09-30')), 7),
            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::parse('2020-12-29'), Carbon::parse('2021-02-28')), 1),
            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::parse('2020-12-30'), Carbon::parse('2021-02-28')), 1),
            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::parse('2020-12-31'), Carbon::parse('2021-02-28')), 1),
            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::parse('2023-03-31'), Carbon::parse('2023-11-30')), 7),
            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::parse('2023-05-31'), Carbon::parse('2023-08-31')), 2),
            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::parse('2023-07-31'), Carbon::parse('2023-09-30')), 1),
            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::parse('2023-10-30'), Carbon::parse('2024-02-29')), 3),
            CalculatorProvider::from(Periodicity::Monthly, new IntervalProvider(Carbon::parse('2023-10-31'), Carbon::parse('2024-02-29')), 3),

            CalculatorProvider::from(Periodicity::Bimonthly, new IntervalProvider(Carbon::now(), Carbon::now()->addMonthsNoOverflow(6)), 2),
            CalculatorProvider::from(Periodicity::Bimonthly, new IntervalProvider(Carbon::parse('2019-08-29'), Carbon::parse('2020-02-29')), 2),
            CalculatorProvider::from(Periodicity::Bimonthly, new IntervalProvider(Carbon::parse('2019-08-30'), Carbon::parse('2020-02-29')), 2),
            CalculatorProvider::from(Periodicity::Bimonthly, new IntervalProvider(Carbon::parse('2019-08-31'), Carbon::parse('2020-02-29')), 2),
            CalculatorProvider::from(Periodicity::Bimonthly, new IntervalProvider(Carbon::parse('2019-10-30'), Carbon::parse('2020-02-29')), 1),
            CalculatorProvider::from(Periodicity::Bimonthly, new IntervalProvider(Carbon::parse('2019-10-31'), Carbon::parse('2020-02-29')), 1),
            CalculatorProvider::from(Periodicity::Bimonthly, new IntervalProvider(Carbon::parse('2020-02-29'), Carbon::parse('2021-02-28')), 5),
            CalculatorProvider::from(Periodicity::Bimonthly, new IntervalProvider(Carbon::parse('2020-08-29'), Carbon::parse('2021-02-28')), 2),
            CalculatorProvider::from(Periodicity::Bimonthly, new IntervalProvider(Carbon::parse('2020-08-30'), Carbon::parse('2021-02-28')), 2),
            CalculatorProvider::from(Periodicity::Bimonthly, new IntervalProvider(Carbon::parse('2020-08-31'), Carbon::parse('2021-02-28')), 2),

            CalculatorProvider::from(Periodicity::Quarterly, new IntervalProvider(Carbon::now(), Carbon::now()->addMonthsNoOverflow(9)), 2),
            CalculatorProvider::from(Periodicity::Quarterly, new IntervalProvider(Carbon::parse('2019-05-29'), Carbon::parse('2020-02-29')), 2),
            CalculatorProvider::from(Periodicity::Quarterly, new IntervalProvider(Carbon::parse('2019-05-30'), Carbon::parse('2020-02-29')), 2),
            CalculatorProvider::from(Periodicity::Quarterly, new IntervalProvider(Carbon::parse('2019-05-31'), Carbon::parse('2020-02-29')), 2),
            CalculatorProvider::from(Periodicity::Quarterly, new IntervalProvider(Carbon::parse('2020-02-29'), Carbon::parse('2021-02-28')), 3),
            CalculatorProvider::from(Periodicity::Quarterly, new IntervalProvider(Carbon::parse('2020-08-29'), Carbon::parse('2021-02-28')), 1),
            CalculatorProvider::from(Periodicity::Quarterly, new IntervalProvider(Carbon::parse('2020-08-30'), Carbon::parse('2021-02-28')), 1),
            CalculatorProvider::from(Periodicity::Quarterly, new IntervalProvider(Carbon::parse('2020-08-31'), Carbon::parse('2021-02-28')), 1),

            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::now(), Carbon::now()->addMonthsNoOverflow(12)), 1),
            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::now(), Carbon::now()->addMonthsNoOverflow(18)), 2),
            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::now(), Carbon::now()->addMonthsNoOverflow(24)), 3),
            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::parse('2018-08-29'), Carbon::parse('2020-02-29')), 2),
            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::parse('2018-08-30'), Carbon::parse('2020-02-29')), 2),
            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::parse('2018-08-31'), Carbon::parse('2020-02-29')), 2),
            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::parse('2019-01-31'), Carbon::parse('2021-01-31')), 3),
            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::parse('2019-02-28'), Carbon::parse('2021-08-28')), 4),
            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::parse('2020-01-31'), Carbon::parse('2021-01-31')), 1),
            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::parse('2020-02-29'), Carbon::parse('2021-02-28')), 1),
            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::parse('2020-08-29'), Carbon::parse('2022-02-28')), 2),
            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::parse('2020-08-30'), Carbon::parse('2022-02-28')), 2),
            CalculatorProvider::from(Periodicity::HalfYearly, new IntervalProvider(Carbon::parse('2020-08-31'), Carbon::parse('2022-02-28')), 2),

            CalculatorProvider::from(Periodicity::Yearly, new IntervalProvider(Carbon::now(), Carbon::now()->addYearsNoOverflow(3)), 2),
            CalculatorProvider::from(Periodicity::Yearly, new IntervalProvider(Carbon::parse('2019-01-29'), Carbon::parse('2025-01-29')), 5),
            CalculatorProvider::from(Periodicity::Yearly, new IntervalProvider(Carbon::parse('2020-02-29'), Carbon::parse('2031-02-28')), 10),
        ];

        /** @var IntervalProvider $interval */
        foreach ($intervals as $index => $interval) {
            yield "#{$index} {$interval->label}" => [$interval];
        }
    }

    public static function from(Periodicity $periodicity, IntervalProvider $interval, int $skip = 0): CalculatorProvider
    {
        return new self($interval, $periodicity, $skip);
    }

    public function epoch(): Carbon
    {
        return $this->intervalProvider->epoch;
    }

    public function expected(): Carbon
    {
        return $this->intervalProvider->expected;
    }
}

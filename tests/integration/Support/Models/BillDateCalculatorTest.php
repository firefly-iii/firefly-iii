<?php

/*
 * BillDateCalculatorTest.php
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

namespace Tests\integration\Support\Models;

use Carbon\Carbon;
use FireflyIII\Support\Models\BillDateCalculator;
use Tests\integration\TestCase;

/**
 * Class BillDateCalculatorTest
 *
 * @internal
 *
 * @coversNothing
 */
final class BillDateCalculatorTest extends TestCase
{
    private readonly BillDateCalculator $calculator;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->calculator = new BillDateCalculator();
    }

    /**
     * Stupid long method names I'm not going to do that.
     *
     * @dataProvider provideDates
     */
    public function testGivenSomeDataItWorks(Carbon $earliest, Carbon $latest, Carbon $billStart, string $period, int $skip, ?Carbon $lastPaid, array $expected): void
    {
        $result = $this->calculator->getPayDates($earliest, $latest, $billStart, $period, $skip, $lastPaid);
        self::assertSame($expected, $result);
    }

    public static function provideDates(): iterable
    {
        // Carbon $earliest, Carbon $latest, Carbon $billStart, string $period, int $skip, ?Carbon $lastPaid
        return [
            // basic monthly bill.x
            '1Ma' => ['earliest' => Carbon::parse('2023-11-01'), 'latest' => Carbon::parse('2023-11-30'), 'billStart' => Carbon::parse('2023-01-01'), 'period' => 'monthly', 'skip' => 0, 'lastPaid' => null, 'expected' => ['2023-11-01']],
            // already paid on the first, expect it next month.
            '1Mb' => ['earliest' => Carbon::parse('2023-11-01'), 'latest' => Carbon::parse('2023-11-30'), 'billStart' => Carbon::parse('2023-01-01'), 'period' => 'monthly', 'skip' => 0, 'lastPaid' => Carbon::parse('2023-11-01'), 'expected' => ['2023-12-01']],
            // already paid on the 12th, expect it next month.
            '1Mc' => ['earliest' => Carbon::parse('2023-11-01'), 'latest' => Carbon::parse('2023-11-30'), 'billStart' => Carbon::parse('2023-01-01'), 'period' => 'monthly', 'skip' => 0, 'lastPaid' => Carbon::parse('2023-11-12'), 'expected' => ['2023-12-01']],

            // every month, start on 2024-01-30, view is quarterly
            '1Md' => ['earliest' => Carbon::parse('2023-01-01'), 'latest' => Carbon::parse('2023-03-31'), 'billStart' => Carbon::parse('2023-01-29'), 'period' => 'monthly', 'skip' => 0, 'lastPaid' => null, 'expected' => ['2023-01-29', '2023-02-28', '2023-03-29']],

            // every month, start on 2024-01-30, view is quarterly
            '1Me' => ['earliest' => Carbon::parse('2024-01-01'), 'latest' => Carbon::parse('2024-03-31'), 'billStart' => Carbon::parse('2023-01-30'), 'period' => 'monthly', 'skip' => 0, 'lastPaid' => null, 'expected' => ['2024-01-30', '2024-02-29', '2024-03-30']],

            // yearly not due this month. Should jump to next year.
            '1Ya' => ['earliest' => Carbon::parse('2023-11-01'), 'latest' => Carbon::parse('2023-11-30'), 'billStart' => Carbon::parse('2021-05-01'), 'period' => 'yearly', 'skip' => 0, 'lastPaid' => Carbon::parse('2023-05-02'), 'expected' => ['2024-05-01']],
        ];
    }
}

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

use Override;
use Carbon\Carbon;
use FireflyIII\Support\Models\BillDateCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
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
    private BillDateCalculator $calculator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new BillDateCalculator();
    }

    /**
     * Stupid long method names I'm not going to do that.
     */
    #[DataProvider('provideDates')]
    public function testGivenSomeDataItWorks(Carbon $earliest, Carbon $latest, Carbon $billStart, string $period, int $skip, ?Carbon $lastPaid, array $expected): void
    {
        $result = $this->calculator->getPayDates($earliest, $latest, $billStart, $period, $skip, $lastPaid);
        $this->assertSame($expected, $result);
    }

    public static function provideDates(): iterable
    {
        // Carbon $earliest, Carbon $latest, Carbon $billStart, string $period, int $skip, ?Carbon $lastPaid
        // basic monthly bill.x
        yield '1Ma' => [Carbon::parse('2023-11-01'), Carbon::parse('2023-11-30'), Carbon::parse('2023-01-01'), 'monthly', 0, null, ['2023-11-01']];

        // already paid on the first, expect it next month.
        yield '1Mb' => [Carbon::parse('2023-11-01'), Carbon::parse('2023-11-30'), Carbon::parse('2023-01-01'), 'monthly', 0, Carbon::parse('2023-11-01'), ['2023-12-01']];

        // already paid on the 12th, expect it next month.
        yield '1Mc' => [Carbon::parse('2023-11-01'), Carbon::parse('2023-11-30'), Carbon::parse('2023-01-01'), 'monthly', 0, Carbon::parse('2023-11-12'), ['2023-12-01']];

        // every month, start on 2024-01-30, view is quarterly
        yield '1Md' => [Carbon::parse('2023-01-01'), Carbon::parse('2023-03-31'), Carbon::parse('2023-01-29'), 'monthly', 0, null, ['2023-01-29', '2023-02-28', '2023-03-29']];

        // every month, start on 2024-01-30, view is quarterly
        yield '1Me' => [Carbon::parse('2024-01-01'), Carbon::parse('2024-03-31'), Carbon::parse('2023-01-30'), 'monthly', 0, null, ['2024-01-30', '2024-02-29', '2024-03-30']];

        // yearly not due this month. Should jump to next year.
        yield '1Ya' => [Carbon::parse('2023-11-01'), Carbon::parse('2023-11-30'), Carbon::parse('2021-05-01'), 'yearly', 0, Carbon::parse('2023-05-02'), ['2024-05-01']];
    }
}

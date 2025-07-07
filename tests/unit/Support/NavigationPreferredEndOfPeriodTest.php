<?php

/*
 * NavigationPreferredEndOfPeriodTest.php
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
final class NavigationPreferredEndOfPeriodTest extends TestCase
{
    private Navigation $navigation;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->navigation = new Navigation();
    }

    #[DataProvider('providePeriods')]
    public function testGivenStartAndEndDatesWhenCallPreferredEndOfPeriodThenReturnsTheExpectedFormatSuccessful(Carbon $start, Carbon $end, string $expected): void
    {
        $formatPeriod = $this->navigation->preferredEndOfPeriod($start, $end);
        $this->assertSame($expected, $formatPeriod);
    }

    public static function providePeriods(): iterable
    {
        yield '1 week' => [Carbon::now(), Carbon::now()->addWeek(), 'endOfDay'];

        yield '1 month' => [Carbon::now(), Carbon::now()->addMonth(), 'endOfDay'];

        yield '2 months' => [Carbon::now(), Carbon::now()->addMonths(2), 'endOfMonth'];

        yield '3 months' => [Carbon::now(), Carbon::now()->addMonths(3), 'endOfMonth'];

        yield '6 months' => [Carbon::now(), Carbon::now()->addMonths(6), 'endOfMonth'];

        yield '7 months' => [Carbon::now(), Carbon::now()->addMonths(7), 'endOfMonth'];

        yield '11 months' => [Carbon::now(), Carbon::now()->addMonths(11), 'endOfMonth'];

        yield '12 months' => [Carbon::now(), Carbon::now()->addMonths(12), 'endOfMonth'];

        yield '13 months' => [Carbon::now(), Carbon::now()->addMonths(13), 'endOfYear'];

        yield '16 months' => [Carbon::now(), Carbon::now()->addMonths(16), 'endOfYear'];

        yield '1 year' => [Carbon::now(), Carbon::now()->addYear(), 'endOfMonth'];

        yield '2 years' => [Carbon::now(), Carbon::now()->addYears(2), 'endOfYear'];
    }
}

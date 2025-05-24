<?php

/*
 * NavigationPreferredCarbonFormatByPeriodTest.php
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
final class NavigationPreferredCarbonFormatByPeriodTest extends TestCase
{
    private Navigation $navigation;

    public function setUp(): void
    {
        parent::setUp();
        $this->navigation = new Navigation();
    }

    /**
     *
     */
    #[DataProvider('providePeriods')]
    public function testGivenAPeriodWhenCallPreferredCarbonFormatByPeriodThenReturnsExpectedFormat(string $period, string $expected): void
    {
        $formatPeriod = $this->navigation->preferredCarbonFormatByPeriod($period);
        self::assertSame($expected, $formatPeriod);
    }

    public static function providePeriods(): iterable
    {
        return [
            'unknown'     => ['period' => '1day', 'expected' => 'Y-m-d'],
            'week'        => ['period' => '1W', 'expected' => '\WW,Y'],
            'month'       => ['period' => '1M', 'expected' => 'Y-m'],
            'quarterly'   => ['period' => '3M', 'expected' => '\QQ,Y'],
            'half-yearly' => ['period' => '6M', 'expected' => '\QQ,Y'],
            'yearly'      => ['period' => '1Y', 'expected' => 'Y'],
        ];
    }
}

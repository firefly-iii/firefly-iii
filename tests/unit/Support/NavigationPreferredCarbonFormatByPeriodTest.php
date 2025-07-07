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

use Override;
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

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->navigation = new Navigation();
    }

    #[DataProvider('providePeriods')]
    public function testGivenAPeriodWhenCallPreferredCarbonFormatByPeriodThenReturnsExpectedFormat(string $period, string $expected): void
    {
        $formatPeriod = $this->navigation->preferredCarbonFormatByPeriod($period);
        $this->assertSame($expected, $formatPeriod);
    }

    public static function providePeriods(): iterable
    {
        yield 'unknown' => ['1day', 'Y-m-d'];

        yield 'week' => ['1W', '\WW,Y'];

        yield 'month' => ['1M', 'Y-m'];

        yield 'quarterly' => ['3M', '\QQ,Y'];

        yield 'half-yearly' => ['6M', '\QQ,Y'];

        yield 'yearly' => ['1Y', 'Y'];
    }
}

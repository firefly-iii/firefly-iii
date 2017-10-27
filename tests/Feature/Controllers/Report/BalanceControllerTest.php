<?php
/**
 * BalanceControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Report;


use FireflyIII\Helpers\Collection\Balance;
use FireflyIII\Helpers\Report\BalanceReportHelperInterface;
use Tests\TestCase;

/**
 * Class BalanceControllerTest
 *
 * @package Tests\Feature\Controllers\Report
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BalanceControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Report\BalanceController::general
     */
    public function testGeneral()
    {
        $balance = $this->mock(BalanceReportHelperInterface::class);
        $balance->shouldReceive('getBalanceReport')->andReturn(new Balance);

        $this->be($this->user());
        $response = $this->get(route('report-data.balance.general', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

}

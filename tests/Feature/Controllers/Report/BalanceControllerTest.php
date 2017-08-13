<?php
/**
 * BalanceControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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

<?php
/**
 * ReportControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Chart;


use Tests\TestCase;

class ReportControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController::netWorth
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController::__construct
     */
    public function testNetWorth()
    {
        $this->be($this->user());
        $response = $this->get(route('chart.report.net-worth', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController::operations
     */
    public function testOperations()
    {
        $this->be($this->user());
        $response = $this->get(route('chart.report.operations', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController::sum
     */
    public function testSum()
    {
        $this->be($this->user());
        $response = $this->get(route('chart.report.sum', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

}

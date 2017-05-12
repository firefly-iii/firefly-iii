<?php
/**
 * ReportControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Chart;


use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use Steam;
use Tests\TestCase;

/**
 * Class ReportControllerTest
 *
 * @package Tests\Feature\Controllers\Chart
 */
class ReportControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController::netWorth
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController::arraySum
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController::__construct
     */
    public function testNetWorth()
    {
        $generator = $this->mock(GeneratorInterface::class);
        $tasker    = $this->mock(AccountTaskerInterface::class);

        Steam::shouldReceive('balancesById')->andReturn(['5', '10']);
        $generator->shouldReceive('singleSet')->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.report.net-worth', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController::operations
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController::getChartData
     */
    public function testOperations()
    {
        $generator = $this->mock(GeneratorInterface::class);
        $tasker    = $this->mock(AccountTaskerInterface::class);
        $income    = [1 => ['sum' => '100']];
        $expense   = [2 => ['sum' => '-100']];
        $tasker->shouldReceive('getIncomeReport')->once()->andReturn($income);
        $tasker->shouldReceive('getExpenseReport')->once()->andReturn($expense);
        $generator->shouldReceive('multiSet')->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.report.operations', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController::sum
     * @covers \FireflyIII\Http\Controllers\Chart\ReportController::getChartData
     */
    public function testSum()
    {
        $generator = $this->mock(GeneratorInterface::class);
        $tasker    = $this->mock(AccountTaskerInterface::class);

        $income  = [];
        $expense = [];
        $tasker->shouldReceive('getIncomeReport')->andReturn($income)->times(1);
        $tasker->shouldReceive('getExpenseReport')->andReturn($expense)->times(1);

        $generator->shouldReceive('multiSet')->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.report.sum', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

}

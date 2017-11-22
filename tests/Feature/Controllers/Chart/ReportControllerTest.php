<?php
/**
 * ReportControllerTest.php
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

namespace Tests\Feature\Controllers\Chart;

use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use Steam;
use Tests\TestCase;

/**
 * Class ReportControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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

        Steam::shouldReceive('balancesByAccounts')->andReturn(['5', '10']);
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

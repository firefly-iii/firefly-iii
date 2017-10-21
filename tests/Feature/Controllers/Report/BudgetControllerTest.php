<?php
/**
 * BudgetControllerTest.php
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


use FireflyIII\Helpers\Report\BudgetReportHelperInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class BudgetControllerTest
 *
 * @package Tests\Feature\Controllers\Report
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BudgetControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Report\BudgetController::general
     */
    public function testGeneral()
    {
        $return = [];
        $helper = $this->mock(BudgetReportHelperInterface::class);
        $helper->shouldReceive('getBudgetReport')->andReturn($return);

        $this->be($this->user());
        $response = $this->get(route('report-data.budget.general', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\BudgetController::period
     * @covers \FireflyIII\Http\Controllers\Report\BudgetController::filterBudgetPeriodReport
     */
    public function testPeriod()
    {
        $first      = [1 => ['entries' => ['1', '1']]];
        $second     = ['entries' => ['1', '1']];
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('getBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getBudgetPeriodReport')->andReturn($first);
        $repository->shouldReceive('getNoBudgetPeriodReport')->andReturn($second);

        $this->be($this->user());
        $response = $this->get(route('report-data.budget.period', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

}

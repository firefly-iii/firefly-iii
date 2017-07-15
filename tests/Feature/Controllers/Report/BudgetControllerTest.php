<?php
/**
 * BudgetControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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

<?php
/**
 * BudgetControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Tests\TestCase;
class BudgetControllerTest extends TestCase
{

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetController::budget
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testBudget(string $range)
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepository->shouldReceive('firstUseDate')->andReturn(new Carbon('2015-01-01'));
        $budgetRepository->shouldReceive('spentInPeriod')->andReturn('-100');


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.budget.budget', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetController::budgetLimit
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testBudgetLimit(string $range)
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepository->shouldReceive('spentInPeriod')->andReturn('-100');

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.budget.budget-limit', [1,1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetController::frontpage
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testFrontpage(string $range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.budget.frontpage'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\BudgetController::period
     */
    public function testPeriod()
    {
        $this->be($this->user());
        $response = $this->get(route('chart.budget.period', [1,'1','20120101','20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\BudgetController::periodNoBudget
     */
    public function testPeriodNoBudget()
    {
        $this->be($this->user());
        $response = $this->get(route('chart.budget.period.no-budget', ['1','20120101','20120131']));
        $response->assertStatus(200);
    }

}

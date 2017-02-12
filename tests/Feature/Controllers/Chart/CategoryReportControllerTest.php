<?php
/**
 * CategoryReportControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Chart;


use Tests\TestCase;

class CategoryReportControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController::accountExpense
     */
    public function testAccountExpense()
    {
        $this->be($this->user());
        $response = $this->get(route('chart.category.account-expense', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController::accountIncome
     */
    public function testAccountIncome()
    {
        $this->be($this->user());
        $response = $this->get(route('chart.category.account-income', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController::categoryExpense
     */
    public function testCategoryExpense()
    {
        $this->be($this->user());
        $response = $this->get(route('chart.category.category-expense', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController::categoryIncome
     */
    public function testCategoryIncome()
    {
        $this->be($this->user());
        $response = $this->get(route('chart.category.category-income', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController::mainChart
     */
    public function testMainChart()
    {
        $this->be($this->user());
        $response = $this->get(route('chart.category.main', ['1', '1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

}
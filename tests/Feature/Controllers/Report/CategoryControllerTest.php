<?php
/**
 * CategoryControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Report;


use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Report\CategoryController::expenses
     */
    public function testExpenses()
    {
        $this->be($this->user());
        $response = $this->get(route('report-data.category.expenses', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\CategoryController::income
     */
    public function testIncome()
    {
        $this->be($this->user());
        $response = $this->get(route('report-data.category.income', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\CategoryController::operations
     */
    public function testOperations()
    {
        $this->be($this->user());
        $response = $this->get(route('report-data.category.operations', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

}

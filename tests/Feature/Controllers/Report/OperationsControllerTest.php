<?php
/**
 * OperationsControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Report;


use Tests\TestCase;

class OperationsControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Report\OperationsController::expenses
     */
    public function testExpenses()
    {
        $this->be($this->user());
        $response = $this->get(route('report-data.operations.expenses', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\OperationsController::income
     */
    public function testIncome()
    {
        $this->be($this->user());
        $response = $this->get(route('report-data.operations.income', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\OperationsController::operations
     */
    public function testOperations()
    {
        $this->be($this->user());
        $response = $this->get(route('report-data.operations.operations', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

}
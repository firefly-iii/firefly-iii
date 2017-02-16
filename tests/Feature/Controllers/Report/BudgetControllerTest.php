<?php
/**
 * BudgetControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Report;


use Tests\TestCase;

class BudgetControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Report\BudgetController::general
     */
    public function testGeneral()
    {
        $this->be($this->user());
        $response = $this->get(route('report-data.budget.general', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\BudgetController::period
     */
    public function testPeriod()
    {
        $this->be($this->user());
        $response = $this->get(route('report-data.budget.period', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

}

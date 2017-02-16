<?php
/**
 * ReportControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;

class ReportControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::auditReport
     */
    public function testAuditReport()
    {
        $this->be($this->user());
        $response = $this->get(route('reports.report.audit', [1, '20160101', '20160131']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::budgetReport
     */
    public function testBudgetReport()
    {
        $this->be($this->user());
        $response = $this->get(route('reports.report.budget', [1, 1, '20160101', '20160131']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::categoryReport
     */
    public function testCategoryReport()
    {
        $this->be($this->user());
        $response = $this->get(route('reports.report.category', [1, 1, '20160101', '20160131']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::defaultReport
     */
    public function testDefaultReport()
    {
        $this->be($this->user());
        $response = $this->get(route('reports.report.default', [1, '20160101', '20160131']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::index
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::options
     */
    public function testOptions()
    {
        $this->be($this->user());
        $response = $this->get(route('reports.options', ['default']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::postIndex
     */
    public function testPostIndex()
    {
        $this->be($this->user());
        $response = $this->post(route('reports.index.post'));
        $response->assertStatus(302);
    }

}

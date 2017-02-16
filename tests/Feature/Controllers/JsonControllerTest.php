<?php
/**
 * JsonControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;

class JsonControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::action
     */
    public function testAction()
    {
        $this->be($this->user());
        $response = $this->get(route('json.action'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::boxBillsPaid
     */
    public function testBoxBillsPaid()
    {
        $this->be($this->user());
        $response = $this->get(route('json.box.paid'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::boxBillsUnpaid
     */
    public function testBoxBillsUnpaid()
    {
        $this->be($this->user());
        $response = $this->get(route('json.box.unpaid'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::boxIn
     */
    public function testBoxIn()
    {
        $this->be($this->user());
        $response = $this->get(route('json.box.in'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::boxOut
     */
    public function testBoxOut()
    {
        $this->be($this->user());
        $response = $this->get(route('json.box.out'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::categories
     */
    public function testCategories()
    {
        $this->be($this->user());
        $response = $this->get(route('json.categories'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::endTour
     */
    public function testEndTour()
    {
        $this->be($this->user());
        $response = $this->post(route('json.end-tour'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::expenseAccounts
     */
    public function testExpenseAccounts()
    {
        $this->be($this->user());
        $response = $this->get(route('json.expense-accounts'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::revenueAccounts
     */
    public function testRevenueAccounts()
    {
        $this->be($this->user());
        $response = $this->get(route('json.revenue-accounts'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::tags
     */
    public function testTags()
    {
        $this->be($this->user());
        $response = $this->get(route('json.tags'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::tour
     */
    public function testTour()
    {
        $this->be($this->user());
        $response = $this->get(route('json.tour'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::transactionJournals
     */
    public function testTransactionJournals()
    {
        $this->be($this->user());
        $response = $this->get(route('json.transaction-journals', ['deposit']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\JsonController::trigger
     */
    public function testTrigger()
    {
        $this->be($this->user());
        $response = $this->get(route('json.trigger'));
        $response->assertStatus(200);
    }

}

<?php
/**
 * TransactionControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;

class TransactionControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::index
     * @covers \FireflyIII\Http\Controllers\TransactionController::__construct
     */
    public function testIndex()
    {

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['transfer']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::indexAll
     */
    public function testIndexAll()
    {
        $this->be($this->user());
        $response = $this->get(route('transactions.index.all', ['transfer']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::indexByDate
     */
    public function testIndexByDate()
    {
        $this->be($this->user());
        $response = $this->get(route('transactions.index.date', ['transfer', '2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::reorder
     */
    public function testReorder()
    {
        $data = [
            'items' => [],
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.reorder'), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::show
     */
    public function testShow()
    {
        $this->be($this->user());
        $response = $this->get(route('transactions.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Controller::redirectToAccount
     * @covers \FireflyIII\Http\Controllers\TransactionController::show
     */
    public function testShowOpeningBalance()
    {
        $this->be($this->user());
        $journal  = $this->user()->transactionJournals()->where('transaction_type_id', 4)->first();
        $response = $this->get(route('transactions.show', [$journal->id]));
        $response->assertStatus(302);
    }

}

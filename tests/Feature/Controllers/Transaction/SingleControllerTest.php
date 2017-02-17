<?php
/**
 * SingleControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Transaction;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Tests\TestCase;

/**
 * Class SingleControllerTest
 *
 * @package Tests\Feature\Controllers\Transaction
 */
class SingleControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::create
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::__construct
     */
    public function testCreate()
    {
        $this->be($this->user());
        $response = $this->get(route('transactions.create', ['withdrawal']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->get(route('transactions.delete', [12]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::destroy
     */
    public function testDestroy()
    {
        $this->session(['transactions.delete.url' => 'http://localhost']);
        $this->be($this->user());

        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('delete')->once();

        $response = $this->post(route('transactions.destroy', [13]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->get(route('transactions.edit', [13]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SingleController::store
     */
    public function testStore()
    {
        $this->session(['transactions.create.url' => 'http://localhost']);
        $this->be($this->user());

        $data = [
            'what'                      => 'withdrawal',
            'amount'                    => '10',
            'amount_currency_id_amount' => 1,
            'source_account_id'         => 1,
            'destination_account_name'  => 'Some destination',
            'date'                      => '2016-01-01',
            'description'               => 'Test descr',
        ];
        $response = $this->post(route('transactions.store', ['withdrawal']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::update
     */
    public function testUpdate()
    {
        $this->session(['transactions.edit.url' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'id'                        => 123,
            'what'                      => 'withdrawal',
            'description'               => 'Updated groceries',
            'source_account_id'         => 1,
            'destination_account_name'  => 'PLUS',
            'amount'                    => '123',
            'amount_currency_id_amount' => 1,
            'budget_id'                 => 1,
            'category'                  => 'Daily groceries',
            'tags'                      => '',
            'date'                      => '2016-01-01',
        ];

        $response = $this->post(route('transactions.update', [123]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $response = $this->get(route('transactions.show', [123]));
        $response->assertStatus(200);
        $response->assertSee('Updated groceries');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');

    }

}

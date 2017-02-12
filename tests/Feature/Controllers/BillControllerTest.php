<?php
/**
 * BillControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;


use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BillControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\BillController::create
     */
    public function testCreate()
    {
        $this->be($this->user());
        $response = $this->get(route('bills.create'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->get(route('bills.delete', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController::destroy
     */
    public function testDestroy()
    {
        $repository = $this->mock(BillRepositoryInterface::class);
        $repository->shouldReceive('destroy')->andReturn(true);

        $this->session(['bills.delete.url' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('bills.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->get(route('bills.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController::index
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('bills.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController::rescan
     */
    public function testRescan()
    {
        $repository = $this->mock(BillRepositoryInterface::class);
        $repository->shouldReceive('getPossiblyRelatedJournals')->once()->andReturn(new Collection);
        $this->be($this->user());
        $response = $this->get(route('bills.rescan', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController::show
     */
    public function testShow()
    {
        $this->be($this->user());
        $response = $this->get(route('bills.show', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController::store
     */
    public function testStore()
    {
        $data = [
            'name'                          => 'New Bill ' . rand(1000, 9999),
            'match'                         => 'some words',
            'amount_min'                    => '100',
            'amount_currency_id_amount_min' => 1,
            'amount_currency_id_amount_max' => 1,
            'skip'                          => 0,
            'amount_max'                    => '100',
            'date'                          => '2016-01-01',
            'repeat_freq'                   => 'monthly',
        ];
        $this->session(['bills.create.url' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('bills.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // list must be updated
        $this->be($this->user());
        $response = $this->get(route('bills.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee($data['name']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BillController::update
     */
    public function testUpdate()
    {
        $data = [
            'name'                          => 'Updated Bill ' . rand(1000, 9999),
            'match'                         => 'some more words',
            'amount_min'                    => '100',
            'amount_currency_id_amount_min' => 1,
            'amount_currency_id_amount_max' => 1,
            'skip'                          => 0,
            'amount_max'                    => '100',
            'date'                          => '2016-01-01',
            'repeat_freq'                   => 'monthly',
        ];
        $this->session(['bills.edit.url' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('bills.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // list must be updated
        $this->be($this->user());
        $response = $this->get(route('bills.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee($data['name']);
    }

}
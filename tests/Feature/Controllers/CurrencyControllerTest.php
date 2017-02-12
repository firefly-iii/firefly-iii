<?php
/**
 * CurrencyControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Tests\TestCase;

class CurrencyControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::create
     */
    public function testCreate()
    {
        $this->be($this->user());
        $response = $this->get(route('currencies.create'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::defaultCurrency
     */
    public function testDefaultCurrency()
    {
        $this->be($this->user());
        $response = $this->get(route('currencies.default', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->get(route('currencies.delete', [2]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::destroy
     */
    public function testDestroy()
    {
        $this->session(['currencies.delete.url' => 'http://localhost']);
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('canDeleteCurrency')->andReturn(true);
        $repository->shouldReceive('destroy')->andReturn(true);

        $this->be($this->user());
        $response = $this->post(route('currencies.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->get(route('currencies.edit', [2]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::index
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('currencies.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::store
     */
    public function testStore()
    {
        $this->session(['currencies.create.url' => 'http://localhost']);
        $data = [
            'name'           => 'XX',
            'code'           => 'XXX',
            'symbol'         => 'x',
            'decimal_places' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('currencies.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CurrencyController::update
     */
    public function testUpdate()
    {
        $this->session(['currencies.edit.url' => 'http://localhost']);
        $data = [
            'name'           => 'XA',
            'code'           => 'XAX',
            'symbol'         => 'a',
            'decimal_places' => 2,
        ];
        $this->be($this->user());
        $response = $this->post(route('currencies.update', [2]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

}
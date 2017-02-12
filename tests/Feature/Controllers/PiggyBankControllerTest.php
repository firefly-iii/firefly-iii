<?php
/**
 * PiggyBankControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use FireflyIII\Models\PiggyBank;
use Tests\TestCase;

class PiggyBankControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::add
     */
    public function testAdd()
    {
        $this->be($this->user());
        $response = $this->get(route('piggy-banks.add', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::addMobile
     */
    public function testAddMobile()
    {
        $this->be($this->user());
        $response = $this->get(route('piggy-banks.add-money-mobile', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::create
     */
    public function testCreate()
    {
        $this->be($this->user());
        $response = $this->get(route('piggy-banks.create'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->get(route('piggy-banks.delete', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::destroy
     */
    public function testDestroy()
    {
        $repository = $this->mock(PiggyBankRepositoryInterface::class);
        $repository->shouldReceive('destroy')->andReturn(true);

        $this->session(['piggy-banks.delete.url' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.destroy', [2]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->get(route('piggy-banks.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::index
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('piggy-banks.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::order
     */
    public function testOrder()
    {
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.order', [1, 2]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::postAdd
     */
    public function testPostAdd()
    {
        $data = ['amount' => '1.123'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.add', [1]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('piggy-banks.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Add the exact amount to fill a piggy bank
     *
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::postAdd
     */
    public function testPostAddExact()
    {
        // find a piggy with current amount = 0.
        $piggy = PiggyBank::leftJoin('piggy_bank_repetitions', 'piggy_bank_repetitions.piggy_bank_id', '=', 'piggy_banks.id')
                          ->where('currentamount', 0)
                          ->first(['piggy_banks.id', 'targetamount']);


        $data = ['amount' => strval($piggy->targetamount)];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.add', [$piggy->id]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('piggy-banks.index'));
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::postRemove
     */
    public function testPostRemove()
    {
        $data = ['amount' => '1.123'];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.remove', [1]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('piggy-banks.index'));
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::remove
     */
    public function testRemove()
    {
        $this->be($this->user());
        $response = $this->get(route('piggy-banks.remove', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::removeMobile
     */
    public function testRemoveMobile()
    {
        $this->be($this->user());
        $response = $this->get(route('piggy-banks.remove-money-mobile', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::show
     */
    public function testShow()
    {
        $this->be($this->user());
        $response = $this->get(route('piggy-banks.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::store
     */
    public function testStore()
    {
        $this->session(['piggy-banks.create.url' => 'http://localhost']);
        $data = [
            'name'                            => 'Piggy ' . rand(999, 10000),
            'targetamount'                    => '100.123',
            'account_id'                      => 2,
            'amount_currency_id_targetamount' => 1,

        ];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\PiggyBankController::update
     */
    public function testUpdate()
    {
        $this->session(['piggy-banks.edit.url' => 'http://localhost']);
        $data = [
            'name'                            => 'Updated Piggy ' . rand(999, 10000),
            'targetamount'                    => '100.123',
            'account_id'                      => 2,
            'amount_currency_id_targetamount' => 1,

        ];
        $this->be($this->user());
        $response = $this->post(route('piggy-banks.update', [3]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertRedirect(route('index'));
    }


}
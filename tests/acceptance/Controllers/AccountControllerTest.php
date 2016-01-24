<?php
/**
 * AccountControllerTest.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

/**
 * Class AccountControllerTest
 */
class AccountControllerTest extends TestCase
{
    /**
     * @covers FireflyIII\Http\Controllers\AccountController::create
     */
    public function testCreate()
    {
        $this->be($this->user());
        $response = $this->call('GET', '/accounts/create/asset');
        $this->assertEquals(200, $response->status());
    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->call('GET', '/accounts/delete/1');
        $this->assertEquals(200, $response->status());
    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::destroy
     */
    public function testDestroy()
    {
        $this->be($this->user());
        $this->session(['accounts.delete.url' => 'http://localhost']);
        $response = $this->call('POST', '/accounts/destroy/6');
        $this->assertSessionHas('success');
        $this->assertEquals(302, $response->status());
    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->call('GET', '/accounts/edit/1');
        $this->assertEquals(200, $response->status());
    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::index
     * @covers FireflyIII\Http\Controllers\AccountController::isInArray
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->call('GET', '/accounts/asset');
        $this->assertEquals(200, $response->status());
    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::show
     */
    public function testShow()
    {
        $this->be($this->user());
        $response = $this->call('GET', '/accounts/show/1');
        $this->assertEquals(200, $response->status());
    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::store
     */
    public function testStore()
    {
        $this->be($this->user());
        $this->session(['accounts.create.url' => 'http://localhost']);
        $args = [
            'name'                              => 'Some kind of test account.',
            'what'                              => 'asset',
            'amount_currency_id_virtualBalance' => 1,
            'amount_currency_id_openingBalance' => 1,
        ];

        $response = $this->call('POST', '/accounts/store', $args);
        $this->assertEquals(302, $response->status());
        $this->assertSessionHas('success');

    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::update
     */
    public function testUpdate()
    {
        $this->session(['accounts.edit.url' => 'http://localhost']);
        $args = [
            'id'     => 1,
            'name'   => 'TestData new name',
            'active' => 1,
        ];
        $this->be($this->user());

        $response = $this->call('POST', '/accounts/update/1', $args);
        $this->assertEquals(302, $response->status());
        $this->assertSessionHas('success');

    }
}

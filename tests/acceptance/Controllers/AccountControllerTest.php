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

        $args = [
            '_token' => Session::token(),
        ];

        $this->session(['accounts.delete.url' => 'http://localhost']);

        $response = $this->call('POST', '/accounts/destroy/6', $args);
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
            '_token'                            => Session::token(),
            'name'                              => 'Some kind of test account.',
            'what'                              => 'asset',
            'amount_currency_id_virtualBalance' => 1,
            'amount_currency_id_openingBalance' => 1,
        ];

        $response = $this->call('POST', '/accounts/store', $args);
        $this->assertEquals(302, $response->status());
        $this->assertSessionHas('success');

        $this->markTestIncomplete();
    }

    /**
     * @covers FireflyIII\Http\Controllers\AccountController::update
     * @todo   Implement testUpdate().
     */
    public function testUpdate()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}

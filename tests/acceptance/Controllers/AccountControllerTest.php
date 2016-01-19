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
    public function testCreate()
    {
        $this->be($this->user());
        $response = $this->call('GET', '/accounts/create/asset');
        $this->assertEquals(200, $response->status());
    }

    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->call('GET', '/accounts/delete/1');
        $this->assertEquals(200, $response->status());
    }

    public function testDestroy()
    {
        $this->markTestIncomplete();
    }

    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->call('GET', '/accounts/edit/1');
        $this->assertEquals(200, $response->status());
    }

    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->call('GET', '/accounts/asset');
        $this->assertEquals(200, $response->status());
    }

    public function testShow()
    {
        $this->be($this->user());
        $response = $this->call('GET', '/accounts/show/1');
        $this->assertEquals(200, $response->status());
    }

    public function testStore()
    {
        $this->markTestIncomplete();
    }

    public function testUpdate()
    {
        $this->markTestIncomplete();
    }

}

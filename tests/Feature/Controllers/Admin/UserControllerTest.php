<?php
/**
 * UserControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);


namespace Tests\Feature\Controllers\Admin;

use Tests\TestCase;

class UserControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->get(route('admin.users.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController::index
     * @covers \FireflyIII\Http\Controllers\Admin\UserController::__construct
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('admin.users'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController::show
     */
    public function testShow()
    {
        $this->be($this->user());
        $response = $this->get(route('admin.users.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }


}

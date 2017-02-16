<?php
/**
 * ProfileControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use FireflyIII\Repositories\User\UserRepositoryInterface;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::changePassword
     */
    public function testChangePassword()
    {
        $this->be($this->user());
        $response = $this->get(route('profile.change-password'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::deleteAccount
     */
    public function testDeleteAccount()
    {
        $this->be($this->user());
        $response = $this->get(route('profile.delete-account'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::index
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('profile.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::postChangePassword
     */
    public function testPostChangePassword()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('changePassword');

        $data = [
            'current_password'          => 'james',
            'new_password'              => 'james2',
            'new_password_confirmation' => 'james2',
        ];
        $this->be($this->user());
        $response = $this->post(route('profile.change-password.post'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ProfileController::postDeleteAccount
     */
    public function testPostDeleteAccount()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('destroy');
        $data = [
            'password' => 'james',
        ];
        $this->be($this->user());
        $response = $this->post(route('profile.delete-account.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
    }

}

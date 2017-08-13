<?php
/**
 * ForgotPasswordControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Auth;


use FireflyIII\Repositories\User\UserRepositoryInterface;
use Tests\TestCase;

/**
 * Class ForgotPasswordControllerTest
 *
 * @package Tests\Feature\Controllers\Auth
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ForgotPasswordControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Auth\ForgotPasswordController::__construct
     * @covers \FireflyIII\Http\Controllers\Auth\ForgotPasswordController::sendResetLinkEmail
     */
    public function testSendResetLinkEmail()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->andReturn(false);
        $data = [
            'email' => 'thegrumpydictator@gmail.com',
        ];

        $response = $this->post(route('password.email'), $data);
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Auth\ForgotPasswordController::__construct
     * @covers \FireflyIII\Http\Controllers\Auth\ForgotPasswordController::sendResetLinkEmail
     */
    public function testSendResetLinkEmailDemo()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->andReturn(true);
        $data = [
            'email' => 'thegrumpydictator@gmail.com',
        ];

        $response = $this->post(route('password.email'), $data);
        $response->assertStatus(302);
    }
}

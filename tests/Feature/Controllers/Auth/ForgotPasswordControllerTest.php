<?php
/**
 * ForgotPasswordControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Auth;

use FireflyIII\Repositories\User\UserRepositoryInterface;
use Tests\TestCase;

/**
 * Class ForgotPasswordControllerTest
 *
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

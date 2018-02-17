<?php
/**
 * AuthenticateTest.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class AuthenticateTest
 */
class AuthenticateTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Middleware\Authenticate::handle
     */
    public function testMiddleware()
    {
        $response = $this->get('/_test/authenticate');
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertRedirect(route('login'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Authenticate::handle
     */
    public function testMiddlewareAjax()
    {
        //$this->withoutExceptionHandling();
        $server   = ['HTTP_X-Requested-With' => 'XMLHttpRequest'];
        $response = $this->get('/_test/authenticate', $server);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Authenticate::handle
     */
    public function testMiddlewareAuth()
    {
        $this->be($this->user());
        $response = $this->get('/_test/authenticate');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Authenticate::handle
     */
    public function testMiddlewareBlockedUser()
    {
        $user          = $this->user();
        $user->blocked = 1;

        $this->be($user);
        $response = $this->get('/_test/authenticate');
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertSessionHas('logoutMessage', strval(trans('firefly.block_account_logout')));
        $response->assertRedirect(route('login'));

    }

    /**
     * @covers \FireflyIII\Http\Middleware\Authenticate::handle
     */
    public function testMiddlewareEmail()
    {
        //$this->withoutExceptionHandling();
        $user               = $this->user();
        $user->blocked      = 1;
        $user->blocked_code = 'email_changed';
        $this->be($user);
        $response = $this->get('/_test/authenticate');
        //$this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertSessionHas('logoutMessage', strval(trans('firefly.email_changed_logout')));
        //$response->assertRedirect(route('login'));
    }

    /**
     * Set up test
     */
    protected function setUp()
    {
        parent::setUp();

        Route::middleware('auth')->any(
            '/_test/authenticate', function () {
            return 'OK';
        }
        );
    }
}
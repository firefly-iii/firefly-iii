<?php
/**
 * AuthenticateTest.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Log;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class AuthenticateTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AuthenticateTest extends TestCase
{
    /**
     * Set up test
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
        Route::middleware('auth')->any(
            '/_test/authenticate', function () {
            return 'OK';
        }
        );
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Authenticate
     */
    public function testMiddleware(): void
    {
        Log::debug('Now at testMiddleware');
        $response = $this->get('/_test/authenticate');
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertRedirect(route('login'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Authenticate
     */
    public function testMiddlewareAjax(): void
    {
        Log::debug('Now at testMiddlewareAjax');
        $server   = ['HTTP_X-Requested-With' => 'XMLHttpRequest'];
        $response = $this->get('/_test/authenticate', $server);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Authenticate
     */
    public function testMiddlewareAuth(): void
    {
        Log::debug('Now at testMiddlewareAuth');
        $this->be($this->user());
        $response = $this->get('/_test/authenticate');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Authenticate
     */
    public function testMiddlewareBlockedUser(): void
    {
        Log::debug('Now at testMiddlewareBlockedUser');
        $user          = $this->user();
        $user->blocked = 1;

        $this->be($user);
        $response = $this->get('/_test/authenticate');
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertSessionHas('logoutMessage', (string)trans('firefly.block_account_logout'));
        $response->assertRedirect(route('login'));

    }

    /**
     * @covers \FireflyIII\Http\Middleware\Authenticate
     */
    public function testMiddlewareEmail(): void
    {
        Log::debug('Now at testMiddlewareEmail');
        $user               = $this->user();
        $user->blocked      = 1;
        $user->blocked_code = 'email_changed';
        $this->be($user);
        $response = $this->get('/_test/authenticate');
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertSessionHas('logoutMessage', (string)trans('firefly.email_changed_logout'));
        $response->assertRedirect(route('login'));
    }
}

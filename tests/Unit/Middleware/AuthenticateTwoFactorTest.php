<?php
/**
 * AuthenticateTwoFactorTest.php
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

use FireflyIII\Http\Middleware\AuthenticateTwoFactor;
use FireflyIII\Models\Preference;
use Preferences;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class AuthenticateTwoFactorTest
 */
class AuthenticateTwoFactorTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Middleware\AuthenticateTwoFactor::handle
     */
    public function testMiddleware()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/_test/authenticate');
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertRedirect(route('login'));
    }

    /**
     * tests for user with no 2FA, should just go to requested page.
     *
     * 2FA enabled: false
     * 2FA secret : false
     * cookie     : false
     *
     *
     * @covers \FireflyIII\Http\Middleware\AuthenticateTwoFactor::handle
     */
    public function testMiddlewareNoTwoFA()
    {
        $this->withoutExceptionHandling();
        $user          = $this->user();
        $user->blocked = 0;
        $this->be($user);

        // pref for has 2fa is false
        $preference       = new Preference;
        $preference->data = false;
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->once()->andReturn($preference);

        // pref for no twoFactorAuthSecret
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->once()->andReturn(null);

        // no cookie
        $cookie   = [];
        $response = $this->call('GET', '/_test/authenticate', [], $cookie);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * tests for user with 2FA and secret and cookie. Continue to page.
     *
     * 2FA enabled: true
     * 2FA secret : 'abcde'
     * cookie     : false
     *
     *
     * @covers \FireflyIII\Http\Middleware\AuthenticateTwoFactor::handle
     */
    public function testMiddlewareTwoFAAuthed()
    {
        $this->withoutExceptionHandling();
        $user          = $this->user();
        $user->blocked = 0;
        $this->be($user);

        // pref for has 2fa is true
        $preference       = new Preference;
        $preference->data = true;
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->once()->andReturn($preference);

        // pref for twoFactorAuthSecret
        $secret       = new Preference;
        $secret->data = 'SomeSecret';
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->once()->andReturn($secret);

        // no cookie
        $cookie   = ['twoFactorAuthenticated' => 'true'];
        $response = $this->call('GET', '/_test/authenticate', [], $cookie);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * tests for user with 2FA but no secret. 2FA is not fired.
     *
     * 2FA enabled: true
     * 2FA secret : false
     * cookie     : false
     *
     *
     * @covers \FireflyIII\Http\Middleware\AuthenticateTwoFactor::handle
     */
    public function testMiddlewareTwoFANoSecret()
    {
        $this->withoutExceptionHandling();
        $user          = $this->user();
        $user->blocked = 0;
        $this->be($user);

        // pref for has 2fa is true
        $preference       = new Preference;
        $preference->data = true;
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->once()->andReturn($preference);

        // pref for no twoFactorAuthSecret
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->once()->andReturn(null);

        // no cookie
        $cookie   = [];
        $response = $this->call('GET', '/_test/authenticate', [], $cookie);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * tests for user with 2FA and secret. 2FA is checked
     *
     * 2FA enabled: true
     * 2FA secret : 'abcde'
     * cookie     : false
     *
     *
     * @covers \FireflyIII\Http\Middleware\AuthenticateTwoFactor::handle
     */
    public function testMiddlewareTwoFASecret()
    {
        $this->withoutExceptionHandling();
        $user          = $this->user();
        $user->blocked = 0;
        $this->be($user);

        // pref for has 2fa is true
        $preference       = new Preference;
        $preference->data = true;
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->once()->andReturn($preference);

        // pref for twoFactorAuthSecret
        $secret       = new Preference;
        $secret->data = 'SomeSecret';
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->once()->andReturn($secret);

        // no cookie
        $cookie   = [];
        $response = $this->call('GET', '/_test/authenticate', [], $cookie);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertRedirect(route('two-factor.index'));
    }

    /**
     * Set up test
     */
    protected function setUp()
    {
        parent::setUp();

        Route::middleware(AuthenticateTwoFactor::class)->any(
            '/_test/authenticate', function () {
            return 'OK';
        }
        );
    }
}
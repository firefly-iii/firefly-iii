<?php
/**
 * RedirectIf2FAAuthenticatedTest.php
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

use FireflyIII\Http\Middleware\RedirectIfTwoFactorAuthenticated;
use FireflyIII\Models\Preference;
use Preferences;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class RedirectIf2FAAuthenticatedTest
 */
class RedirectIf2FAAuthenticatedTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Middleware\RedirectIfTwoFactorAuthenticated::handle
     */
    public function testMiddleware()
    {
        $response = $this->get('/_test/authenticate');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\RedirectIfTwoFactorAuthenticated::handle
     */
    public function testMiddlewareAuthenticated()
    {
        // pref for has 2fa is true
        $preference       = new Preference;
        $preference->data = true;
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->once()->andReturn($preference);

        // pref for twoFactorAuthSecret
        $secret       = new Preference;
        $secret->data = 'SomeSecret';
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->once()->andReturn($secret);

        // no cookie
        $cookie = ['twoFactorAuthenticated' => 'true'];

        $this->be($this->user());
        $response = $this->call('GET', '/_test/authenticate', [], $cookie);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\RedirectIfTwoFactorAuthenticated::handle
     */
    public function testMiddlewareLightAuth()
    {
        $this->be($this->user());
        $response = $this->get('/_test/authenticate');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Set up test
     */
    protected function setUp()
    {
        parent::setUp();

        Route::middleware(RedirectIfTwoFactorAuthenticated::class)->any(
            '/_test/authenticate', function () {
            return 'OK';
        }
        );
    }
}
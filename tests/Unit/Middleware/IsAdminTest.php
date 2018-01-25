<?php
/**
 * IsAdminTest.php
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

use FireflyIII\Http\Middleware\IsAdmin;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class IsAdminTest
 */
class IsAdminTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Middleware\IsAdmin::handle
     */
    public function testMiddleware()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/_test/is-admin');
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertRedirect(route('login'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\IsAdmin::handle
     */
    public function testMiddlewareAjax()
    {
        $server = ['HTTP_X-Requested-With' => 'XMLHttpRequest'];
        $this->withoutExceptionHandling();
        $response = $this->get('/_test/is-admin', $server);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\IsAdmin::handle
     */
    public function testMiddlewareNotOwner()
    {
        $this->withoutExceptionHandling();
        $this->be($this->emptyUser());
        $response = $this->get('/_test/is-admin');
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertRedirect(route('home'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\IsAdmin::handle
     */
    public function testMiddlewareOwner()
    {
        $this->be($this->user());
        $this->withoutExceptionHandling();
        $response = $this->get('/_test/is-admin');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Set up test
     */
    protected function setUp()
    {
        parent::setUp();

        Route::middleware(IsAdmin::class)->any(
            '/_test/is-admin', function () {
            return 'OK';
        }
        );
    }
}
<?php
/**
 * RedirectIfAuthenticatedTest.php
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

use FireflyIII\Http\Middleware\RedirectIfAuthenticated;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class RedirectIfAuthenticatedTest
 */
class RedirectIfAuthenticatedTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Middleware\RedirectIfAuthenticated::handle
     */
    public function testMiddleware()
    {
        $response = $this->get('/_test/authenticate');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\RedirectIfAuthenticated::handle
     */
    public function testMiddlewareAuthenticated()
    {
        $this->be($this->user());
        $response = $this->get('/_test/authenticate');
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertRedirect(route('index'));
    }

    /**
     * Set up test
     */
    protected function setUp()
    {
        parent::setUp();

        Route::middleware(RedirectIfAuthenticated::class)->any(
            '/_test/authenticate', function () {
            return 'OK';
        }
        );
    }
}
<?php
/**
 * SandstormTest.php
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

use FireflyIII\Http\Middleware\Sandstorm;
use FireflyIII\Models\Role;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class RangeTest
 */
class SandstormTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Middleware\Sandstorm::handle
     */
    public function testMiddlewareAnonEmpty()
    {
        putenv('SANDSTORM=1');

        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('count')->once()->andReturn(0);

        $response = $this->get('/_test/sandstorm');
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $response->assertSee('The first visit to a new Firefly III administration cannot be by a guest user.');

        putenv('SANDSTORM=0');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Sandstorm::handle
     */
    public function testMiddlewareAnonLoggedIn()
    {
        putenv('SANDSTORM=1');

        $this->be($this->user());
        $response = $this->get('/_test/sandstorm');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('sandstorm-anon: true');

        putenv('SANDSTORM=0');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Sandstorm::handle
     */
    public function testMiddlewareAnonUser()
    {
        putenv('SANDSTORM=1');

        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('count')->twice()->andReturn(1);

        $response = $this->get('/_test/sandstorm');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('sandstorm-anon: true');

        putenv('SANDSTORM=0');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Sandstorm::handle
     */
    public function testMiddlewareLoggedIn()
    {
        putenv('SANDSTORM=1');

        $this->be($this->user());
        $response = $this->get('/_test/sandstorm', ['X-Sandstorm-User-Id' => 'abcd']);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('sandstorm-anon: false');

        putenv('SANDSTORM=0');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Sandstorm::handle
     */
    public function testMiddlewareMultiUser()
    {
        putenv('SANDSTORM=1');

        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('count')->once()->andReturn(2);

        $response = $this->get('/_test/sandstorm');
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $response->assertSee('Your Firefly III installation has more than one user, which is weird.');

        putenv('SANDSTORM=0');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Sandstorm::handle
     */
    public function testMiddlewareNoUser()
    {
        putenv('SANDSTORM=1');

        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('count')->twice()->andReturn(0);
        $repository->shouldReceive('store')->once()->andReturn($this->user());
        $repository->shouldReceive('attachRole')->twice()->andReturn(true);
        $repository->shouldReceive('getRole')->once()->andReturn(new Role);

        $response = $this->get('/_test/sandstorm', ['X-Sandstorm-User-Id' => 'abcd']);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('sandstorm-anon: false');

        putenv('SANDSTORM=0');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Sandstorm::handle
     */
    public function testMiddlewareNotSandstorm()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/_test/sandstorm');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Sandstorm::handle
     */
    public function testMiddlewareOneUser()
    {
        putenv('SANDSTORM=1');

        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('count')->twice()->andReturn(1);
        $repository->shouldReceive('first')->once()->andReturn($this->user());

        $response = $this->get('/_test/sandstorm', ['X-Sandstorm-User-Id' => 'abcd']);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('sandstorm-anon: false');

        putenv('SANDSTORM=0');
    }

    /**
     * Set up test
     */
    protected function setUp()
    {
        parent::setUp();

        Route::middleware(Sandstorm::class)->any(
            '/_test/sandstorm', function () {
            return view('test.test');
        }
        );
    }
}
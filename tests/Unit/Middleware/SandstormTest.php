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
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mockery;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class RangeTest
 */
class SandstormTest extends TestCase
{
    /**
     * Set up test
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
        Route::middleware(Sandstorm::class)->any(
            '/_test/sandstorm', function () {
            return view('test.test');
        }
        );
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Sandstorm
     */
    public function testMiddlewareBasic(): void
    {
        putenv('SANDSTORM=1');

        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')->withArgs(['anonymous@firefly'])->once()->andReturn($this->user());
        // single user, checks if user is admin
        $repository->shouldReceive('count')->andReturn(1);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->once();
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->once();
        $repository->shouldReceive('attachRole')->withArgs([Mockery::any(), 'owner'])->once();

        $response = $this->get('/_test/sandstorm');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('sandstorm-anon: false');

        putenv('SANDSTORM=0');
    }



    /**
     * @covers \FireflyIII\Http\Middleware\Sandstorm
     */
    public function testMiddlewareNotSandstorm(): void
    {
        putenv('SANDSTORM=0');

        $response = $this->get('/_test/sandstorm');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('sandstorm-anon: false');
    }
}

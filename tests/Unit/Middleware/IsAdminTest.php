<?php
/**
 * IsAdminTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use FireflyIII\Http\Middleware\IsAdmin;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mockery;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class IsAdminTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IsAdminTest extends TestCase
{
    /**
     * Set up test
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
        Route::middleware(IsAdmin::class)->any(
            '/_test/is-admin', function () {
            return 'OK';
        }
        );

    }

    /**
     * @covers \FireflyIII\Http\Middleware\IsAdmin
     */
    public function testMiddleware(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $this->withoutExceptionHandling();
        $response = $this->get('/_test/is-admin');
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertRedirect(route('login'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\IsAdmin
     */
    public function testMiddlewareAjax(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $server = ['HTTP_X-Requested-With' => 'XMLHttpRequest'];
        $this->withoutExceptionHandling();
        $response = $this->get('/_test/is-admin', $server);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\IsAdmin
     */
    public function testMiddlewareNotOwner(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(false);

        $this->withoutExceptionHandling();
        $this->be($this->emptyUser());
        $response = $this->get('/_test/is-admin');
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertRedirect(route('home'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\IsAdmin
     */
    public function testMiddlewareOwner(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $this->withoutExceptionHandling();
        $response = $this->get('/_test/is-admin');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}

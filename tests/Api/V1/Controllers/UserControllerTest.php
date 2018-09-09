<?php
/**
 * UserControllerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Api\V1\Controllers;


use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Laravel\Passport\Passport;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class UserControllerTest
 */
class UserControllerTest extends TestCase
{

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', \get_class($this)));

    }

    /**
     * Delete a user.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testDelete(): void
    {
        $userRepository = $this->mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $userRepository->shouldReceive('destroy')->once();
        // create a user first:
        // call API
        $response = $this->delete('/api/v1/users/' . $this->user()->id);
        $response->assertStatus(204);
    }

    /**
     * Delete a user as non admin
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testDeleteNoAdmin(): void
    {
        $userRepository = $this->mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(false);
        Passport::actingAs($this->emptyUser());

        // create a user first:
        $user = User::create(['email' => 'some@newu' . random_int(1, 10000) . 'ser.nl', 'password' => 'hello', 'blocked' => 0]);

        // call API
        $response = $this->delete('/api/v1/users/' . $user->id);
        $response->assertStatus(302);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /**
     * Show list of users.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     */
    public function testIndex(): void
    {
        // create stuff
        $users = factory(User::class, 10)->create();
        // mock stuff:
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->once()->andReturn(true);

        // mock calls:
        $repository->shouldReceive('all')->withAnyArgs()->andReturn($users)->once();

        // test API
        $response = $this->get('/api/v1/users');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 10, 'count' => 10, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(
            ['links' => ['self' => true, 'first' => true, 'last' => true,],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show single user.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     */
    public function testShow(): void
    {
        $user       = User::first();
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->once()->andReturn(true);

        // test API
        $response = $this->get('/api/v1/users/' . $user->id);
        $response->assertStatus(200);
        $response->assertSee($user->email);
    }

    /**
     * Store new user.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testStoreBasic(): void
    {
        $data = [
            'email'   => 'some_new@user' . random_int(1, 10000) . '.com',
            'blocked' => 0,
        ];

        // mock
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->twice()->andReturn(true);
        $userRepos->shouldReceive('store')->once()->andReturn($this->user());

        // test API
        $response = $this->post('/api/v1/users', $data);
        $response->assertStatus(200);
        $response->assertSee($this->user()->email);
    }

    /**
     * Store user with info already used.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testStoreNotUnique(): void
    {
        $data = [
            'email'   => $this->user()->email,
            'blocked' => 0,
        ];

        // mock
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->twice()->andReturn(true);
        // test API
        $response = $this->post('/api/v1/users', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'email' => [
                        'The email address has already been taken.',
                    ],
                ],
            ]
        );
    }

    /**
     * Update user.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testUpdate(): void
    {
        // create a user first:
        $user = User::create(['email' => 'some@newu' . random_int(1, 10000) . 'ser.nl', 'password' => 'hello', 'blocked' => 0]);

        // data:
        $data = [
            'email'   => 'some-new@email' . random_int(1, 10000) . '.com',
            'blocked' => 0,
        ];

        // mock
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('update')->once()->andReturn($user);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->twice()->andReturn(true);

        // call API
        $response = $this->put('/api/v1/users/' . $user->id, $data);
        $response->assertStatus(200);

    }

}

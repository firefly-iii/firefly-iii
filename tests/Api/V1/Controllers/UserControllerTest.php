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
use Tests\TestCase;

/**
 * Class UserControllerTest
 */
class UserControllerTest extends TestCase
{

    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::debug(sprintf('Now in %s.', \get_class($this)));

    }

    /**
     * Delete a user.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController::__construct
     * @covers \FireflyIII\Api\V1\Controllers\UserController::delete
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testDelete()
    {
        // create a user first:
        $user = User::create(['email' => 'some@newu' . random_int(1, 1000) . 'ser.nl', 'password' => 'hello', 'blocked' => 0]);

        // call API
        $response = $this->delete('/api/v1/users/' . $user->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /**
     * Delete a user as non admin
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController::__construct
     * @covers \FireflyIII\Api\V1\Controllers\UserController::delete
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testDeleteNoAdmin()
    {
        Passport::actingAs($this->emptyUser());

        // create a user first:
        $user = User::create(['email' => 'some@newu' . random_int(1, 1000) . 'ser.nl', 'password' => 'hello', 'blocked' => 0]);

        // call API
        $response = $this->delete('/api/v1/users/' . $user->id);
        $response->assertStatus(302);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\UserController::__construct
     * @covers \FireflyIII\Api\V1\Controllers\UserController::index
     */
    public function testIndex()
    {
        // create stuff
        $users = factory(User::class, 10)->create();
        // mock stuff:
        $repository = $this->mock(UserRepositoryInterface::class);

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
     * @covers \FireflyIII\Api\V1\Controllers\UserController::show
     */
    public function testShow()
    {
        $user = User::first();

        // test API
        $response = $this->get('/api/v1/users/' . $user->id);
        $response->assertStatus(200);
        $response->assertSee($user->email);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\UserController::store
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testStoreBasic()
    {
        $data = [
            'email'   => 'some_new@user' . random_int(1, 1000) . '.com',
            'blocked' => 0,
        ];

        // mock
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('store')->once()->andReturn($this->user());

        // test API
        $response = $this->post('/api/v1/users', $data);
        $response->assertStatus(200);
        $response->assertSee($this->user()->email);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\UserController::store
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testStoreNotUnique()
    {
        $data = [
            'email'   => $this->user()->email,
            'blocked' => 0,
        ];

        // mock
        $userRepos = $this->mock(UserRepositoryInterface::class);

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
     * @covers \FireflyIII\Api\V1\Controllers\UserController::update
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testUpdate()
    {
        // create a user first:
        $user = User::create(['email' => 'some@newu' . random_int(1, 1000) . 'ser.nl', 'password' => 'hello', 'blocked' => 0]);

        // data:
        $data = [
            'email'   => 'some-new@email' . random_int(1, 1000) . '.com',
            'blocked' => 0,
        ];

        // mock
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('update')->once()->andReturn($user);

        // call API
        $response = $this->put('/api/v1/users/' . $user->id, $data);
        $response->assertStatus(200);

    }

}

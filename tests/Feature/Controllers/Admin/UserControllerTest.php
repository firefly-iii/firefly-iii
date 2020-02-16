<?php
/**
 * UserControllerTest.php
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

namespace Tests\Feature\Controllers\Admin;

use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class UserControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UserControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController
     */
    public function testDelete(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(2)->andReturn(true);

        $this->mockDefaultSession();

        $this->be($this->user());
        $response = $this->get(route('admin.users.delete', [$this->user()->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController
     */
    public function testDestroy(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('destroy')->once();
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->once()->andReturn(true);

        $this->mockDefaultSession();

        $this->be($this->user());
        $response = $this->post(route('admin.users.destroy', [$this->user()->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController
     */
    public function testEdit(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(2)->andReturn(true);

        $this->mockDefaultSession();

        $this->be($this->user());
        $response = $this->get(route('admin.users.edit', [$this->user()->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController
     */
    public function testIndex(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(3)->andReturn(true);
        $user = $this->user();
        $repository->shouldReceive('all')->andReturn(new Collection([$user]));
        $this->mockDefaultSession();

        $this->be($user);
        $response = $this->get(route('admin.users'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController
     */
    public function testShow(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->times(2)->andReturn(true);
        $repository->shouldReceive('getUserData')->andReturn(
            [
                'export_jobs_success' => 0,
                'import_jobs_success' => 0,
                'attachments_size'    => 0,
            ]
        );

        $this->mockDefaultSession();

        $this->be($this->user());
        $response = $this->get(route('admin.users.show', [$this->user()->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController
     * @covers \FireflyIII\Http\Requests\UserFormRequest
     */
    public function testUpdate(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('changePassword')->once();
        $repository->shouldReceive('changeStatus')->once();
        $repository->shouldReceive('updateEmail')->once();
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);
        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->once()->andReturn(true);

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark');

        $data = [
            'id'                    => 1,
            'email'                 => 'test@example.com',
            'password'              => 'james',
            'password_confirmation' => 'james',
            'blocked_code'          => 'blocked',
            'blocked'               => 1,
        ];

        $this->be($this->user());
        $response = $this->post(route('admin.users.update', ['1']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}

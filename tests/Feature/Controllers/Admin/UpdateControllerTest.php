<?php
/**
 * UpdateControllerTest.php
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

namespace Tests\Feature\Controllers\Admin;

use Carbon\Carbon;
use FireflyConfig;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Configuration;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\FireflyIIIOrg\Update\UpdateRequest;
use FireflyIII\Services\Github\Object\Release;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class UpdateControllerTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UpdateControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Admin\UpdateController
     */
    public function testIndex(): void
    {
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);

        // mock calls
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $this->mockDefaultSession();

        // mock update calls.
        $config              = new Configuration;
        $config->data        = -1;
        $channelConfig       = new Configuration;
        $channelConfig->data = 'stable';

        FireflyConfig::shouldReceive('get')->withArgs(['permission_update_check', -1])->once()->andReturn($config);
        FireflyConfig::shouldReceive('get')->withArgs(['update_channel', 'stable'])->once()->andReturn($channelConfig);
        // call service
        $this->be($this->user());
        $response = $this->get(route('admin.update-check'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UpdateController
     */
    public function testPost(): void
    {
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);

        // mock calls
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();
        $this->mockDefaultSession();

        // mock update calls
        FireflyConfig::shouldReceive('set')->withArgs(['permission_update_check', 1])->once()->andReturn(new Configuration);
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn(new Configuration);
        FireflyConfig::shouldReceive('set')->withArgs(['update_channel','stable'])->once()->andReturn(new Configuration);
        //FireflyConfig::shouldReceive('get')->withArgs(['update_channel', 'stable'])->once()->andReturn($channelConfig);

        // call service
        $this->be($this->user());
        $response = $this->post(route('admin.update-check.post'), ['check_for_updates' => 1]);
        $response->assertSessionHas('success');
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.update-check'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UpdateController
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testUpdateCheck(): void
    {
        // mock stuff
        $userRepos = $this->mock(UserRepositoryInterface::class);

        // mock calls
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn(new Configuration);
        $this->mockDefaultSession();

        $return = [
            'version' => '2.0.0',
            'date' => '2020-01-01'
        ];

        // set some data
        $updater  = $this->mock(UpdateRequest::class);
        $updater->shouldReceive('getVersion')->withArgs(['stable'])->atLeast()->once()
            ->andReturn($return);


        $channelConfig       = new Configuration;
        $channelConfig->data = 'stable';
        FireflyConfig::shouldReceive('get')->withArgs(['update_channel', 'stable'])->atleast()->once()->andReturn($channelConfig);

        $this->be($this->user());
        $response = $this->post(route('admin.update-check.manual'));
        $response->assertStatus(200);
        $response->assertSee(config('firefly.version'));
        $response->assertSee('which is newer than the latest release');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UpdateController
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testUpdateCheckCurrent(): void
    {

        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();
        $this->mockDefaultSession();

        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn(new Configuration);
        $channelConfig       = new Configuration;
        $channelConfig->data = 'stable';
        FireflyConfig::shouldReceive('get')->withArgs(['update_channel', 'stable'])->atleast()->once()->andReturn($channelConfig);

        $return = [
            'version' => config('firefly.version'),
            'date' => '2020-01-01'
        ];

        // set some data
        $updater  = $this->mock(UpdateRequest::class);
        $updater->shouldReceive('getVersion')->withArgs(['stable'])->atLeast()->once()
                ->andReturn($return);

        $this->be($this->user());
        $response = $this->post(route('admin.update-check.manual'));
        $response->assertStatus(200);
        $response->assertSee(config('firefly.version'));
        $response->assertSee('the latest available release');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UpdateController
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testUpdateCheckError(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();
        $this->mockDefaultSession();

        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn(new Configuration);
        $channelConfig       = new Configuration;
        $channelConfig->data = 'stable';
        FireflyConfig::shouldReceive('get')->withArgs(['update_channel', 'stable'])->atleast()->once()->andReturn($channelConfig);

        $updater  = $this->mock(UpdateRequest::class);
        $updater->shouldReceive('getVersion')->withArgs(['stable'])->atLeast()->once()
                ->andThrow(new FireflyException('Something broke.'));



        $this->be($this->user());
        $response = $this->post(route('admin.update-check.manual'));
        $response->assertStatus(200);
        $response->assertSee('An error occurred while checking');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UpdateController
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testUpdateCheckNewer(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();
        $this->mockDefaultSession();

        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn(new Configuration);

        $channelConfig       = new Configuration;
        $channelConfig->data = 'stable';
        FireflyConfig::shouldReceive('get')->withArgs(['update_channel', 'stable'])->atleast()->once()->andReturn($channelConfig);

        $return = [
            'version' => '100',
            'date' => '2020-01-01'
        ];

        // set some data
        $updater  = $this->mock(UpdateRequest::class);
        $updater->shouldReceive('getVersion')->withArgs(['stable'])->atLeast()->once()
                ->andReturn($return);

        // expect a new release (because of .1)
        $this->be($this->user());
        $response = $this->post(route('admin.update-check.manual'));
        $response->assertStatus(200);
        $response->assertSee('A new version of Firefly III is available');
    }
}

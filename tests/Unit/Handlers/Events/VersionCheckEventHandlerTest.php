<?php
/**
 * VersionCheckEventHandlerTest.php
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

namespace Tests\Unit\Handlers\Events;


use FireflyConfig;
use FireflyIII\Events\RequestedVersionCheckStatus;
use FireflyIII\Handlers\Events\VersionCheckEventHandler;
use FireflyIII\Models\Configuration;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\Github\Object\Release;
use FireflyIII\Services\Github\Request\UpdateRequest;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class VersionCheckEventHandlerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class VersionCheckEventHandlerTest extends TestCase
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
     * @covers \FireflyIII\Events\RequestedVersionCheckStatus
     * @covers \FireflyIII\Handlers\Events\VersionCheckEventHandler
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testCheckForUpdatesError(): void
    {
        $updateConfig       = new Configuration;
        $updateConfig->data = 1;
        $checkConfig        = new Configuration;
        $checkConfig->data  = time() - 604810;

        $channelConfig       = new Configuration;
        $channelConfig->data = 'stable';

        $permissionConfig       = new Configuration;
        $permissionConfig->data = 1;


        $event   = new RequestedVersionCheckStatus($this->user());
        $request = $this->mock(UpdateRequest::class);
        $repos   = $this->mock(UserRepositoryInterface::class);
        $repos->shouldReceive('hasRole')->andReturn(true)->once();

        // report on config variables:
        FireflyConfig::shouldReceive('get')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);
        FireflyConfig::shouldReceive('get')->withArgs(['update_channel', 'stable'])->once()->andReturn($channelConfig);
        FireflyConfig::shouldReceive('get')->withArgs(['permission_update_check', -1])->once()->andReturn($permissionConfig);

        // request thing:
        //$request->shouldReceive('call')->once()->andThrow(new FireflyException('Errrr'));
        //$request->shouldNotReceive('getReleases');


        $handler = new VersionCheckEventHandler;
        $handler->checkForUpdates($event);
    }

    /**
     * @covers \FireflyIII\Events\RequestedVersionCheckStatus
     * @covers \FireflyIII\Handlers\Events\VersionCheckEventHandler
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testCheckForUpdatesNewer(): void
    {
        $updateConfig           = new Configuration;
        $updateConfig->data     = 1;
        $checkConfig            = new Configuration;
        $checkConfig->data      = time() - 604800;
        $channelConfig          = new Configuration;
        $channelConfig->data    = 'stable';
        $permissionConfig       = new Configuration;
        $permissionConfig->data = 1;

        $event   = new RequestedVersionCheckStatus($this->user());
        $request = $this->mock(UpdateRequest::class);
        $repos   = $this->mock(UserRepositoryInterface::class);
        $repos->shouldReceive('hasRole')->andReturn(true)->once();

        // is newer than default return:
        $version = config('firefly.version');
        $first   = new Release(['id' => '1', 'title' => $version . '.1', 'updated' => '2017-05-01', 'content' => '']);
        // report on config variables:
        FireflyConfig::shouldReceive('get')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);
        FireflyConfig::shouldReceive('get')->withArgs(['update_channel', 'stable'])->once()->andReturn($channelConfig);
        FireflyConfig::shouldReceive('get')->withArgs(['permission_update_check', -1])->once()->andReturn($permissionConfig);

        // request thing:
        //$request->shouldReceive('call')->once();
        //$request->shouldReceive('getReleases')->once()->andReturn([$first]);


        $handler = new VersionCheckEventHandler;
        $handler->checkForUpdates($event);
    }

    /**
     * @covers \FireflyIII\Events\RequestedVersionCheckStatus
     * @covers \FireflyIII\Handlers\Events\VersionCheckEventHandler
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testCheckForUpdatesSameVersion(): void
    {
        $updateConfig           = new Configuration;
        $updateConfig->data     = 1;
        $checkConfig            = new Configuration;
        $checkConfig->data      = time() - 604800;
        $channelConfig          = new Configuration;
        $channelConfig->data    = 'stable';
        $permissionConfig       = new Configuration;
        $permissionConfig->data = 1;


        $event   = new RequestedVersionCheckStatus($this->user());
        $request = $this->mock(UpdateRequest::class);
        $repos   = $this->mock(UserRepositoryInterface::class);
        $repos->shouldReceive('hasRole')->andReturn(true)->once();

        // is newer than default return:
        $version = config('firefly.version');
        $first   = new Release(['id' => '1', 'title' => $version, 'updated' => '2017-05-01', 'content' => '']);
        // report on config variables:
        FireflyConfig::shouldReceive('get')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);
        FireflyConfig::shouldReceive('get')->withArgs(['update_channel', 'stable'])->once()->andReturn($channelConfig);
        FireflyConfig::shouldReceive('get')->withArgs(['permission_update_check', -1])->once()->andReturn($permissionConfig);

        // request thing:
        //$request->shouldReceive('call')->once();
        //$request->shouldReceive('getReleases')->once()->andReturn([$first]);

        $handler = new VersionCheckEventHandler;
        $handler->checkForUpdates($event);
    }

    /**
     * @covers \FireflyIII\Events\RequestedVersionCheckStatus
     * @covers \FireflyIII\Handlers\Events\VersionCheckEventHandler
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testCheckForUpdatesNoAdmin(): void
    {
        $updateConfig           = new Configuration;
        $updateConfig->data     = 1;
        $checkConfig            = new Configuration;
        $checkConfig->data      = time() - 604800;
        $permissionConfig       = new Configuration;
        $permissionConfig->data = 1;


        $event = new RequestedVersionCheckStatus($this->user());
        $repos = $this->mock(UserRepositoryInterface::class);
        $repos->shouldReceive('hasRole')->andReturn(false)->once();
        FireflyConfig::shouldReceive('get')->withArgs(['permission_update_check', -1])->once()->andReturn($permissionConfig);

        $handler = new VersionCheckEventHandler;
        $handler->checkForUpdates($event);
    }

    /**
     * @covers \FireflyIII\Events\RequestedVersionCheckStatus
     * @covers \FireflyIII\Handlers\Events\VersionCheckEventHandler
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testCheckForUpdatesNoPermission(): void
    {
        $updateConfig           = new Configuration;
        $updateConfig->data     = -1;
        $checkConfig            = new Configuration;
        $checkConfig->data      = time() - 604800;
        $channelConfig          = new Configuration;
        $channelConfig->data    = 'stable';
        $permissionConfig       = new Configuration;
        $permissionConfig->data = 1;

        $event = new RequestedVersionCheckStatus($this->user());
        $repos = $this->mock(UserRepositoryInterface::class);
        $repos->shouldReceive('hasRole')->andReturn(true)->once();
        FireflyConfig::shouldReceive('get')->withArgs(['permission_update_check', -1])->once()->andReturn($permissionConfig);

        // report on config variables:
        FireflyConfig::shouldReceive('get')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);
        FireflyConfig::shouldReceive('get')->withArgs(['update_channel', 'stable'])->once()->andReturn($channelConfig);

        $handler = new VersionCheckEventHandler;
        $handler->checkForUpdates($event);
    }

    /**
     * @covers \FireflyIII\Events\RequestedVersionCheckStatus
     * @covers \FireflyIII\Handlers\Events\VersionCheckEventHandler
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testCheckForUpdatesTooRecent(): void
    {
        $updateConfig       = new Configuration;
        $updateConfig->data = 1;
        $checkConfig        = new Configuration;
        $checkConfig->data  = time() - 800;
        $permissionConfig       = new Configuration;
        $permissionConfig->data = 1;


        $event = new RequestedVersionCheckStatus($this->user());
        $repos = $this->mock(UserRepositoryInterface::class);
        $repos->shouldReceive('hasRole')->andReturn(true)->once();

        // report on config variables:
        FireflyConfig::shouldReceive('get')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);
        FireflyConfig::shouldReceive('get')->withArgs(['permission_update_check', -1])->once()->andReturn($permissionConfig);

        $handler = new VersionCheckEventHandler;
        $handler->checkForUpdates($event);
    }

}

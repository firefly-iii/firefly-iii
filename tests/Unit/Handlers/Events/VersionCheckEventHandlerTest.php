<?php
/**
 * VersionCheckEventHandlerTest.php
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

namespace Tests\Unit\Handlers\Events;


use FireflyConfig;
use FireflyIII\Events\RequestedVersionCheckStatus;
use FireflyIII\Exceptions\FireflyException;
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


        $event   = new RequestedVersionCheckStatus($this->user());
        $request = $this->mock(UpdateRequest::class);
        $repos   = $this->mock(UserRepositoryInterface::class);
        $repos->shouldReceive('hasRole')->andReturn(true)->once();

        // report on config variables:
        FireflyConfig::shouldReceive('get')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);

        // request thing:
        $request->shouldReceive('call')->once()->andThrow(new FireflyException('Errrr'));
        $request->shouldNotReceive('getReleases');


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
        $updateConfig       = new Configuration;
        $updateConfig->data = 1;
        $checkConfig        = new Configuration;
        $checkConfig->data  = time() - 604800;


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

        // request thing:
        $request->shouldReceive('call')->once();
        $request->shouldReceive('getReleases')->once()->andReturn([$first]);


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
        $updateConfig       = new Configuration;
        $updateConfig->data = 1;
        $checkConfig        = new Configuration;
        $checkConfig->data  = time() - 604800;


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

        // request thing:
        $request->shouldReceive('call')->once();
        $request->shouldReceive('getReleases')->once()->andReturn([$first]);

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
        $updateConfig       = new Configuration;
        $updateConfig->data = 1;
        $checkConfig        = new Configuration;
        $checkConfig->data  = time() - 604800;


        $event = new RequestedVersionCheckStatus($this->user());
        $repos = $this->mock(UserRepositoryInterface::class);
        $repos->shouldReceive('hasRole')->andReturn(false)->once();

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
        $updateConfig       = new Configuration;
        $updateConfig->data = -1;
        $checkConfig        = new Configuration;
        $checkConfig->data  = time() - 604800;


        $event = new RequestedVersionCheckStatus($this->user());
        $repos = $this->mock(UserRepositoryInterface::class);
        $repos->shouldReceive('hasRole')->andReturn(true)->once();

        // report on config variables:
        FireflyConfig::shouldReceive('get')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);

        $handler = new VersionCheckEventHandler;
        $handler->checkForUpdates($event);
    }

    /**
     * @covers \FireflyIII\Events\RequestedVersionCheckStatus
     * @covers \FireflyIII\Handlers\Events\VersionCheckEventHandler
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testCheckForUpdatesSandstorm(): void
    {
        putenv('SANDSTORM=1');

        $event   = new RequestedVersionCheckStatus($this->user());
        $handler = new VersionCheckEventHandler;
        $handler->checkForUpdates($event);
        putenv('SANDSTORM=0');
        $this->assertTrue(true);
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


        $event = new RequestedVersionCheckStatus($this->user());
        $repos = $this->mock(UserRepositoryInterface::class);
        $repos->shouldReceive('hasRole')->andReturn(true)->once();


        // report on config variables:
        FireflyConfig::shouldReceive('get')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn($checkConfig);

        $handler = new VersionCheckEventHandler;
        $handler->checkForUpdates($event);
    }

}

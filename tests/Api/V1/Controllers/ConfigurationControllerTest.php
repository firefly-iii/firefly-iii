<?php
/**
 * ConfigurationControllerTest.php
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

use FireflyConfig;
use FireflyIII\Models\Configuration;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Laravel\Passport\Passport;
use Log;
use Mockery;
use Tests\TestCase;

/**
 *
 * Class ConfigurationControllerTest
 */
class ConfigurationControllerTest extends TestCase
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
     * Get configuration variables.
     *
     * @covers \FireflyIII\Api\V1\Controllers\ConfigurationController
     */
    public function testIndex(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $demoConfig       = new Configuration;
        $demoConfig->name = 'is_demo_site';
        $demoConfig->data = false;

        $permConfig       = new Configuration;
        $permConfig->name = 'permission_update_check';
        $permConfig->data = -1;

        $lastConfig       = new Configuration;
        $lastConfig->name = 'last_update_check';
        $lastConfig->data = 123456789;

        $singleConfig       = new Configuration;
        $singleConfig->name = 'single_user_mode';
        $singleConfig->data = true;

        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site'])->andReturn($demoConfig)->once();
        FireflyConfig::shouldReceive('get')->withArgs(['permission_update_check'])->andReturn($permConfig)->once();
        FireflyConfig::shouldReceive('get')->withArgs(['last_update_check'])->andReturn($lastConfig)->once();
        FireflyConfig::shouldReceive('get')->withArgs(['single_user_mode'])->andReturn($singleConfig)->once();

        $expected = [
            'data' => [
                'is_demo_site'            => false,
                'permission_update_check' => -1,
                'last_update_check'       => 123456789,
                'single_user_mode'        => true,
            ],
        ];

        $response = $this->get(route('api.v1.configuration.index'));
        $response->assertStatus(200);
        $response->assertExactJson($expected);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }


    /**
     * Get configuration variables.
     *
     * @covers \FireflyIII\Api\V1\Controllers\ConfigurationController
     */
    public function testIndexNotOwner(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(false);

        Passport::actingAs($this->emptyUser());
        $response = $this->get(route('api.v1.configuration.index'));
        $response->assertStatus(500);
        $response->assertSee('No access to method.');
    }

    /**
     * Set configuration variables.
     *
     * @covers \FireflyIII\Api\V1\Controllers\ConfigurationController
     * @covers \FireflyIII\Api\V1\Requests\ConfigurationRequest
     */
    public function testUpdate(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $data = [
            'value' => 1,

        ];

        $demoConfig       = new Configuration;
        $demoConfig->name = 'is_demo_site';
        $demoConfig->data = false;

        $permConfig       = new Configuration;
        $permConfig->name = 'permission_update_check';
        $permConfig->data = -1;

        $lastConfig       = new Configuration;
        $lastConfig->name = 'last_update_check';
        $lastConfig->data = 123456789;

        $singleConfig       = new Configuration;
        $singleConfig->name = 'single_user_mode';
        $singleConfig->data = true;

        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site'])->andReturn($demoConfig)->once();
        FireflyConfig::shouldReceive('get')->withArgs(['permission_update_check'])->andReturn($permConfig)->once();
        FireflyConfig::shouldReceive('get')->withArgs(['last_update_check'])->andReturn($lastConfig)->once();
        FireflyConfig::shouldReceive('get')->withArgs(['single_user_mode'])->andReturn($singleConfig)->once();
        FireflyConfig::shouldReceive('set')->once()->withArgs(['permission_update_check', 1]);


        $expected = [
            'data' => [
                'is_demo_site'            => false,
                'permission_update_check' => -1,
                'last_update_check'       => 123456789,
                'single_user_mode'        => true,
            ],
        ];
        $response = $this->post(route('api.v1.configuration.update', ['permission_update_check']), $data);
        $response->assertStatus(200);
        $response->assertExactJson($expected);
    }

    /**
     * Set configuration variables.
     *
     * @covers \FireflyIII\Api\V1\Controllers\ConfigurationController
     * @covers \FireflyIII\Api\V1\Requests\ConfigurationRequest
     */
    public function testUpdateBoolean(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $data = [
            'value' => 'true',

        ];

        $demoConfig       = new Configuration;
        $demoConfig->name = 'is_demo_site';
        $demoConfig->data = false;

        $permConfig       = new Configuration;
        $permConfig->name = 'permission_update_check';
        $permConfig->data = -1;

        $lastConfig       = new Configuration;
        $lastConfig->name = 'last_update_check';
        $lastConfig->data = 123456789;

        $singleConfig       = new Configuration;
        $singleConfig->name = 'single_user_mode';
        $singleConfig->data = true;

        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site'])->andReturn($demoConfig)->once();
        FireflyConfig::shouldReceive('get')->withArgs(['permission_update_check'])->andReturn($permConfig)->once();
        FireflyConfig::shouldReceive('get')->withArgs(['last_update_check'])->andReturn($lastConfig)->once();
        FireflyConfig::shouldReceive('get')->withArgs(['single_user_mode'])->andReturn($singleConfig)->once();
        FireflyConfig::shouldReceive('set')->once()->withArgs(['single_user_mode', true]);


        $expected = [
            'data' => [
                'is_demo_site'            => false,
                'permission_update_check' => -1,
                'last_update_check'       => 123456789,
                'single_user_mode'        => true,
            ],
        ];

        $response = $this->post(route('api.v1.configuration.update', ['single_user_mode']), $data);
        $response->assertStatus(200);
        $response->assertExactJson($expected);
    }

    /**
     * Set configuration variable that you're not allowed to change
     *
     * @covers \FireflyIII\Api\V1\Controllers\ConfigurationController
     * @covers \FireflyIII\Api\V1\Requests\ConfigurationRequest
     */
    public function testUpdateInvalid(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $data      = [
            'value' => 'true',
        ];
        $response  = $this->post('/api/v1/configuration/last_update_check', $data);
        $response->assertStatus(404);
    }

    /**
     * Set configuration variables but you're  not the owner.
     *
     * @covers \FireflyIII\Api\V1\Controllers\ConfigurationController
     * @covers \FireflyIII\Api\V1\Requests\ConfigurationRequest
     */
    public function testUpdateNotOwner(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(false);

        Passport::actingAs($this->emptyUser());
        $response = $this->post(route('api.v1.configuration.update', ['single_user_mode']));
        $response->assertStatus(500);
        $response->assertSee('No access to method.');
    }


}

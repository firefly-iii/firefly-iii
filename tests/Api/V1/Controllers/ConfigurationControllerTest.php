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
use Laravel\Passport\Passport;
use Log;
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
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * Get configuration variables.
     *
     * @covers \FireflyIII\Api\V1\Controllers\ConfigurationController
     */
    public function testIndex(): void
    {
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

        $response = $this->get('/api/v1/configuration');
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
        Passport::actingAs($this->emptyUser());
        $response = $this->get('/api/v1/configuration');
        $response->assertStatus(500);
        $response->assertSee('No access to method.');
    }

    /**
     * Set configuration variables.
     *
     * @covers \FireflyIII\Api\V1\Controllers\ConfigurationController
     */
    public function testUpdate(): void
    {
        $data = [
            'name'  => 'permission_update_check',
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

        $response = $this->post('/api/v1/configuration', $data);
        $response->assertStatus(200);
        $response->assertExactJson($expected);
    }

    /**
     * Set configuration variables.
     *
     * @covers \FireflyIII\Api\V1\Controllers\ConfigurationController
     */
    public function testUpdateBoolean(): void
    {
        $data = [
            'name'  => 'single_user_mode',
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

        $response = $this->post('/api/v1/configuration', $data);
        $response->assertStatus(200);
        $response->assertExactJson($expected);
    }

    /**
     * Set configuration variable that you're not allowed to change
     *
     * @covers \FireflyIII\Api\V1\Controllers\ConfigurationController
     */
    public function testUpdateInvalid(): void
    {
        $data     = [
            'name'  => 'last_update_check',
            'value' => 'true',
        ];
        $response = $this->post('/api/v1/configuration', $data);
        $response->assertStatus(500);
        $response->assertSee('You cannot edit this configuration value.');
    }

    /**
     * Set configuration variables but you're  not the owner.
     *
     * @covers \FireflyIII\Api\V1\Controllers\ConfigurationController
     */
    public function testUpdateNotOwner(): void
    {
        Passport::actingAs($this->emptyUser());
        $response = $this->post('/api/v1/configuration');
        $response->assertStatus(500);
        $response->assertSee('No access to method.');
    }


}

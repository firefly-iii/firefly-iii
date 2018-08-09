<?php
/**
 * ConfigurationControllerTest.php
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

namespace Tests\Feature\Controllers\Admin;

use FireflyConfig;
use FireflyIII\Models\Configuration;
use Log;
use Tests\TestCase;

/**
 * Class ConfigurationControllerTest
 */
class ConfigurationControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\ConfigurationController
     * @covers \FireflyIII\Http\Controllers\Admin\ConfigurationController
     */
    public function testIndex(): void
    {
        $this->be($this->user());

        $falseConfig       = new Configuration;
        $falseConfig->data = false;

        $trueConfig       = new Configuration;
        $trueConfig->data = true;

        FireflyConfig::shouldReceive('get')->withArgs(['single_user_mode', true])->once()->andReturn($trueConfig);
        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site', false])->times(2)->andReturn($falseConfig);

        $response = $this->get(route('admin.configuration.index'));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\ConfigurationController
     */
    public function testPostIndex(): void
    {
        $falseConfig       = new Configuration;
        $falseConfig->data = false;

        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site', false])->once()->andReturn($falseConfig);
        FireflyConfig::shouldReceive('set')->withArgs(['single_user_mode', false])->once();
        FireflyConfig::shouldReceive('set')->withArgs(['is_demo_site', false])->once();

        $this->be($this->user());
        $response = $this->post(route('admin.configuration.index.post'));
        $response->assertSessionHas('success');
        $response->assertStatus(302);
    }
}

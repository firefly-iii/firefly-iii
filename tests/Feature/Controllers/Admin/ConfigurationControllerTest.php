<?php
/**
 * ConfigurationControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Admin;


use FireflyConfig;
use FireflyIII\Models\Configuration;
use Tests\TestCase;

/**
 * Class ConfigurationControllerTest
 *
 * @package Tests\Feature\Controllers\Admin
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurationControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\ConfigurationController::index
     * @covers \FireflyIII\Http\Controllers\Admin\ConfigurationController::__construct
     */
    public function testIndex()
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
     * @covers \FireflyIII\Http\Controllers\Admin\ConfigurationController::postIndex
     */
    public function testPostIndex()
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

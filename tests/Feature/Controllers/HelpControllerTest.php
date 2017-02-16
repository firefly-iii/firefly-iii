<?php
/**
 * HelpControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use FireflyIII\Helpers\Help\HelpInterface;
use Tests\TestCase;

class HelpControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\HelpController::show
     */
    public function testShow()
    {
        $help = $this->mock(HelpInterface::class);
        $help->shouldReceive('hasRoute')->andReturn(true)->once();
        $help->shouldReceive('inCache')->andReturn(false)->once();
        $help->shouldReceive('getFromGithub')->andReturn('Help content here.')->once();
        $help->shouldReceive('putInCache')->once();

        $this->be($this->user());
        $response = $this->get(route('help.show', ['index']));
        $response->assertStatus(200);
    }

}

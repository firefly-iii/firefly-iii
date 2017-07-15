<?php
/**
 * HomeControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Admin;

use Tests\TestCase;

/**
 * Class HomeControllerTest
 *
 * @package Tests\Feature\Controllers\Admin
 */
class HomeControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\HomeController::index
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('admin.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

}

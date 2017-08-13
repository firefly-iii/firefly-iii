<?php
/**
 * ImportControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;

/**
 * Class ImportControllerTest
 *
 * @package Tests\Feature\Controllers
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::index
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('import.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }


}

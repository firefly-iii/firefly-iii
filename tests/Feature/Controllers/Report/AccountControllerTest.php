<?php
/**
 * AccountControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Report;


use FireflyIII\Repositories\Account\AccountTaskerInterface;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 *
 * @package Tests\Feature\Controllers\Report
 */
class AccountControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Report\AccountController::general
     */
    public function testGeneral()
    {
        $return = [
            'accounts'   => [],
            'start'      => '0',
            'end'        => '0',
            'difference' => '0',
        ];

        $tasker = $this->mock(AccountTaskerInterface::class);
        $tasker->shouldReceive('getAccountReport')->andReturn($return);


        $this->be($this->user());
        $response = $this->get(route('report-data.account.general', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

}

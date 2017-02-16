<?php
/**
 * PiggyBankControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Chart;


use Tests\TestCase;

class PiggyBankControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Chart\PiggyBankController::history
     */
    public function testHistory()
    {
        $this->be($this->user());
        $response = $this->get(route('chart.piggy-bank.history', [1]));
        $response->assertStatus(200);
    }


}

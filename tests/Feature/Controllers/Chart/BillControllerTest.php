<?php
/**
 * BillControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Chart;


use Tests\TestCase;


class BillControllerTest extends TestCase
{
    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BillController::frontpage
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testFrontpage(string $range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.bill.frontpage'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\BillController::single
     */
    public function testSingle()
    {
        $this->be($this->user());
        $response = $this->get(route('chart.bill.single', [1]));
        $response->assertStatus(200);
    }

}
<?php
/**
 * HomeControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\HomeController::dateRange
     * @covers \FireflyIII\Http\Controllers\HomeController::__construct
     */
    public function testDateRange()
    {

        $this->be($this->user());

        $args = [
            'start' => '2012-01-01',
            'end'   => '2012-04-01',
        ];

        $response = $this->post(route('daterange'), $args);
        $response->assertStatus(200);
        $response->assertSessionHas('warning', '91 days of data may take a while to load.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HomeController::displayError
     */
    public function testDisplayError()
    {
        $this->be($this->user());
        $response = $this->get(route('error'));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HomeController::flush
     */
    public function testFlush()
    {
        $this->be($this->user());
        $response = $this->get(route('flush'));
        $response->assertStatus(302);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\HomeController::index
     * @covers       \FireflyIII\Http\Controllers\HomeController::__construct
     * @covers       \FireflyIII\Http\Controllers\Controller::__construct
     * @dataProvider dateRangeProvider
     *
     * @param $range
     */
    public function testIndex(string $range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('index'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HomeController::testFlash
     */
    public function testTestFlash()
    {
        $this->be($this->user());
        $response = $this->get(route('test-flash'));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionHas('info');
        $response->assertSessionHas('warning');
        $response->assertSessionHas('error');
    }

}

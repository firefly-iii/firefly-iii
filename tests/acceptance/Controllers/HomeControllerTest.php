<?php

/**
 * HomeControllerTest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
class HomeControllerTest extends TestCase
{
    public function displayError()
    {
        $this->assertTrue(true);
    }

    /**
     * @covers FireflyIII\Http\Controllers\HomeController::dateRange
     * @covers FireflyIII\Http\Controllers\HomeController::__construct
     */
    public function testDateRange()
    {

        $this->be($this->user());

        $args = [
            'start' => '2012-01-01',
            'end'   => '2012-04-01',
        ];

        $this->call('POST', route('daterange'), $args);
        $this->assertResponseStatus(200);
        $this->assertSessionHas('warning', '91 days of data may take a while to load.');
    }

    /**
     * @covers FireflyIII\Http\Controllers\HomeController::displayError
     */
    public function testDisplayError()
    {
        $this->be($this->user());
        $this->call('GET', route('error'));
        $this->assertResponseStatus(500);
    }

    /**
     * @covers FireflyIII\Http\Controllers\HomeController::flush
     */
    public function testFlush()
    {
        $this->be($this->user());
        $this->call('GET', route('flush'));
        $this->assertResponseStatus(302);
    }

    /**
     * @covers       FireflyIII\Http\Controllers\HomeController::index
     * @covers       FireflyIII\Http\Controllers\Controller::__construct
     * @dataProvider dateRangeProvider
     *
     * @param $range
     */
    public function testIndex($range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $this->call('GET', route('index'));
        $this->assertResponseStatus(200);
    }

    /**
     * @covers       FireflyIII\Http\Controllers\HomeController::routes
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testRoutes(string $range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $this->call('GET', route('allRoutes'));
        $this->assertResponseStatus(200);
    }

    /**
     * @covers FireflyIII\Http\Controllers\HomeController::testFlash
     */
    public function testTestFlash()
    {
        $this->be($this->user());
        $this->call('GET', route('testFlash'));
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');
        $this->assertSessionHas('info');
        $this->assertSessionHas('warning');
        $this->assertSessionHas('error');
    }
}

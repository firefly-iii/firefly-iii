<?php

/**
 * HomeControllerTest.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */
class HomeControllerTest extends TestCase
{
    /**
     * @covers FireflyIII\Http\Controllers\HomeController::dateRange
     */
    public function testDateRange()
    {

        $this->be($this->user());

        $args = [
            'start'  => '2012-01-01',
            'end'    => '2012-04-01',
        ];

        // if date range is > 50, should have flash.
        $this->call('POST', '/daterange', $args);
        $this->assertResponseStatus(200);
        $this->assertSessionHas('warning', '91 days of data may take a while to load.');
    }

    /**
     * @covers FireflyIII\Http\Controllers\HomeController::flush
     */
    public function testFlush()
    {
        $this->be($this->user());
        $this->call('GET', '/flush');
        $this->assertResponseStatus(302);
    }

    /**
     * @covers FireflyIII\Http\Controllers\HomeController::index
     * @covers FireflyIII\Http\Controllers\Controller::__construct
     */
    public function testIndex()
    {
        $this->be($this->user());
        $this->call('GET', '/');
        $this->assertResponseStatus(200);
    }
}
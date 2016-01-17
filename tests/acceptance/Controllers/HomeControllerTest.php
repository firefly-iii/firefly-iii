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
    public function testDateRange()
    {

        $this->be($this->user());

        $args = [
            'start' => '2012-01-01',
            'end'   => '2012-04-01',
            '_token' => Session::token(),
        ];

        // if date range is > 50, should have flash.
        $response = $this->call('POST', '/daterange', $args);
        $this->assertEquals(200, $response->status());
        $this->assertSessionHas('warning', '91 days of data may take a while to load.');
    }

    public function testFlush()
    {
        $this->be($this->user());
        $response = $this->call('GET', '/flush');
        $this->assertEquals(302, $response->status());
    }

    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->call('GET', '/');
        $this->assertEquals(200, $response->status());
    }


}
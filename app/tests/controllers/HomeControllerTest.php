<?php

class HomeControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testIndex()
    {
        // mock:
        View::shouldReceive('make')->with('index');
        Auth::shouldReceive('check')->andReturn(true);

        // call
        $this->call('GET', '/');

        // test
        $this->assertResponseOk();
    }

} 
<?php

class MigrationControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testIndex()
    {
        // mock:
        View::shouldReceive('share');
        View::shouldReceive('make')->with('migrate.index');

        // call
        $this->call('GET', '/migrate');

        // test
        $this->assertResponseOk();
    }

    public function tearDown()
    {
        Mockery::close();
    }
}
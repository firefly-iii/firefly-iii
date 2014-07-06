<?php

class AccountControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testCreate()
    {
        // mock:
        View::shouldReceive('share');
        View::shouldReceive('make')->with('accounts.create');

        // call
        $this->call('GET', '/accounts/create');

        // test
        $this->assertResponseOk();
    }

    public function testShow()
    {

        // the route filters on accounts using Eloquent, maybe fix that instead?
        $this->assertTrue(true);
    }
}
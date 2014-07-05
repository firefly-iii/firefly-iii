<?php
class AccountControllerTest extends TestCase {
    public function setUp()
    {
        parent::setUp();
    }

    public function testCreate()
    {
        // mock:
        View::shouldReceive('make')->with('accounts.create');

        // call
        $this->call('GET', '/accounts/create');

        // test
        $this->assertResponseOk();
    }
}
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
        View::shouldReceive('make')->with('index')->once()->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()         // Pass a 'with' parameter
            ->with('count', 0)
        ;
        Auth::shouldReceive('check')->andReturn(true);
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('count')->andReturn(0);

        // call
        $this->call('GET', '/');

        // test
        $this->assertResponseOk();
    }

} 
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
        View::shouldReceive('share');
        View::shouldReceive('make')->with('index')->once()->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()         // Pass a 'with' parameter
            ->with('count', 0)
            ->andReturn(Mockery::self())
            ->shouldReceive('with')->once() // another 'with' parameter.
            ->with('accounts',[])
            ->andReturn(Mockery::self())
            ;
        Auth::shouldReceive('check')->andReturn(true);

        // mock account repository
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('count')->andReturn(0);
        $accounts->shouldReceive('getActiveDefault')->andReturn([]);

        // mock preferences helper:
        $preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $preferences->shouldReceive('get')->with('frontpageAccounts',[])->andReturn(new \Preference)->once();
        $preferences->shouldReceive('get')->with('viewRange', 'week')->once()->andReturn('week');

        // call
        $this->call('GET', '/');

        // test
        $this->assertResponseOk();
    }

    public function tearDown()
    {
        Mockery::close();
    }
} 
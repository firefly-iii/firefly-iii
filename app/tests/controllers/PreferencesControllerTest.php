<?php

class PreferencesControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testIndex()
    {

        // mock preferences helper:
        $pref = $this->mock('Preference');
        $pref->shouldReceive('getAttribute', 'data')->andReturn([]);

        $viewPref = $this->mock('Preference');
        $viewPref->shouldReceive('getAttribute', 'data')->andReturn('1M');


        // mock view:
        View::shouldReceive('share');
        View::shouldReceive('make')->with('preferences.index')->once()->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()->with('accounts', [])->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()->with('viewRange', '1M')->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()->with('frontpageAccounts', $pref)->andReturn(\Mockery::self());


        $preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $preferences->shouldReceive('get')->with('frontpageAccounts', [])->andReturn($pref);
        $preferences->shouldReceive('get')->with('viewRange', '1M')->andReturn($viewPref);

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('accounts')->andReturn([]);
        $accounts->shouldReceive('getDefault')->andReturn([]);

        // call
        $this->call('GET', '/preferences');

        // test
        $this->assertResponseOk();
    }

    public function testPostIndex()
    {
        // mock
        $preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $preferences->shouldReceive('set')->with('frontpageAccounts', [1])->andReturn(true);
        $preferences->shouldReceive('set')->with('viewRange', '1M')->andReturn(true);

        // call
        $this->call('POST', '/preferences', ['frontpageAccounts' => [1], 'viewRange' => '1M']);


        // test
        $this->assertSessionHas('success');
        $this->assertRedirectedToRoute('preferences');
    }
    public function tearDown()
    {
        Mockery::close();
    }
} 
<?php

use League\FactoryMuffin\Facade\FactoryMuffin;

/**
 * Class HomeControllerTest
 */
class HomeControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
    }

    public function testIndex()
    {
        // mock:
        View::shouldReceive('share');
        View::shouldReceive('make')->with('index')->once()->andReturn(\Mockery::self())
            ->shouldReceive('with')->once() // Pass a 'with' parameter
            ->with('count', 0)->andReturn(\Mockery::self())
            ->shouldReceive('with')->once() // Pass a 'with' parameter
            ->with('transactions', [])->andReturn(\Mockery::self());

        // pass another
        Auth::shouldReceive('check')->andReturn(true);


        // mock account repository
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('count')->andReturn(0);
        $accounts->shouldReceive('getActiveDefault')->andReturn([]);

        // mock preferences & pref:
        $pref = $this->mock('Preference');
        $pref->shouldReceive('getAttribute', 'data')->andReturn([]);

        $preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $preferences->shouldReceive('get')->with('frontpageAccounts', [])->once()->andReturn($pref);

        // call
        $this->call('GET', '/');

        // test
        $this->assertResponseOk();
    }

    public function testIndexWithAccounts()
    {
        // mock:
        View::shouldReceive('share');
        View::shouldReceive('make')->with('index')->once()->andReturn(\Mockery::self())
            ->shouldReceive('with')->once() // Pass a 'with' parameter
            ->with('count', 1)->andReturn(\Mockery::self())
            ->shouldReceive('with')->once() // Pass a 'with' parameter
            ->with('transactions', [])->andReturn(\Mockery::self());

        // pass another
        Auth::shouldReceive('check')->andReturn(true);

        // make account:
        $account = FactoryMuffin::create('Account');


        // mock account repository
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('count')->andReturn(1);
        $accounts->shouldReceive('getByIds')->with([$account->id])->andReturn([$account]);

        // mock transaction journal repository:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('getByAccount')->with($account, 15)->andReturn([]);

        // mock preferences & pref:
        $pref = $this->mock('Preference');
        $pref->shouldReceive('getAttribute', 'data')->andReturn([$account->id]);

        $preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $preferences->shouldReceive('get')->with('frontpageAccounts', [])->once()->andReturn($pref);

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
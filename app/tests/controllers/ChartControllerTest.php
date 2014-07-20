<?php

use League\FactoryMuffin\Facade\FactoryMuffin;

class ChartControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
    }

    public function testHomeAccount()
    {
        // mock preference:
        $pref = $this->mock('Preference');
        $pref->shouldReceive('getAttribute', 'data')->andReturn('1M');
        $pref2 = $this->mock('Preference');
        $pref2->shouldReceive('getAttribute', 'data')->andReturn([]);

        // mock preferences helper:
        $preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $preferences->shouldReceive('get')->with('viewRange', '1M')->once()->andReturn($pref);
        $preferences->shouldReceive('get')->with('frontpageAccounts', [])->once()->andReturn($pref2);


        // mock toolkit:
        $toolkit = $this->mock('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->shouldReceive('getDateRange')->andReturn(null);

        // create a semi-mocked collection of accounts:

        // mock account(s)
        $personal = $this->mock('AccountType');
        $personal->shouldReceive('jsonSerialize')->andReturn('');

        $one = $this->mock('Account');
        $one->shouldReceive('getAttribute')->andReturn($personal);
        $one->shouldReceive('balance')->andReturn(0);
        $one->shouldReceive('predict')->andReturn(null);

        // collection:
        $c = new \Illuminate\Database\Eloquent\Collection([$one]);

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('getActiveDefault')->andReturn($c);


        // call
        $this->call('GET', '/chart/home/account');

        // test
        $this->assertResponseOk();
    }

    public function testHomeAccountWithInput()
    {
        // save actual account:
        $account = FactoryMuffin::create('Account');

        // mock preference:
        $pref = $this->mock('Preference');
        $pref->shouldReceive('getAttribute', 'data')->andReturn('week');

        // mock preferences helper:
        $preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $preferences->shouldReceive('get')->with('viewRange', '1M')->once()->andReturn($pref);

        // mock toolkit:
        $toolkit = $this->mock('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->shouldReceive('getDateRange')->andReturn(null);

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('find')->with(1)->andReturn($account);


        // call
        $this->call('GET', '/chart/home/account/' . $account->id);

        // test
        $this->assertResponseOk();
    }

    public function testhomeCategories()
    {
        $start = new \Carbon\Carbon;
        $end = new \Carbon\Carbon;

        $toolkit = $this->mock('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->shouldReceive('getDateRange')->andReturn(null);

        // mock toolkit:
//        $class = $this->getMockClass('Foo', array('baz'));
//        $toolkit = $this->mock('Firefly\Helper\Toolkit\ToolkitInterface');
//        $toolkit::static  ->shouldReceive('getDateRange')->once()->andReturn([$start, $end]);

//        // mock transaction journal
//        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
//        $tj->shouldReceive('getByDateRange')->with($start, $end)->andReturn([]);
//



        // call
        $this->call('GET', '/chart/home/categories');

        // test
        $this->assertResponseOk();
    }

    public function tearDown()
    {
        Mockery::close();
    }
}
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
        $pref2 = $this->mock('Preference');
        $pref2->shouldReceive('getAttribute', 'data')->andReturn([]);

        // mock preferences helper:
        $preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $preferences->shouldReceive('get')->with('frontpageAccounts', [])->once()->andReturn($pref2);


        // mock toolkit:
        $start = new Carbon\Carbon;
        $start->startOfMonth();
        $end = new \Carbon\Carbon;
        $end->endOfMonth();
        $toolkit = $this->mock('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->shouldReceive('getDateRange')->with()->once()->andReturn([$start, $end]);

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

    public function testHomeAccountWithPref()
    {

        // mock toolkit:
        $start = new Carbon\Carbon;
        $start->startOfMonth();
        $end = new \Carbon\Carbon;
        $end->endOfMonth();
        $toolkit = $this->mock('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->shouldReceive('getDateRange')->with()->once()->andReturn([$start, $end]);

        // mock account(s)
        $personal = $this->mock('AccountType');
        $personal->shouldReceive('jsonSerialize')->andReturn('');

        $one = $this->mock('Account');
        $one->shouldReceive('getAttribute')->andReturn($personal);
        $one->shouldReceive('balance')->andReturn(0);
        $one->shouldReceive('predict')->andReturn(null);

        // collection:
        $c = new \Illuminate\Database\Eloquent\Collection([$one]);

        // mock preference:
        $pref2 = $this->mock('Preference');
        $pref2->shouldReceive('getAttribute', 'data')->andReturn([$one->id]);

        // mock preferences helper:
        $preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $preferences->shouldReceive('get')->with('frontpageAccounts', [])->once()->andReturn($pref2);


        // create a semi-mocked collection of accounts:


        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('getByIds')->andReturn($c);


        // call
        $this->call('GET', '/chart/home/account');

        // test
        $this->assertResponseOk();
    }

    public function testHomeAccountWithInput()
    {
        // save actual account:
        $account = FactoryMuffin::create('Account');

        // mock toolkit:
        $start = new Carbon\Carbon;
        $start->startOfMonth();
        $end = new \Carbon\Carbon;
        $end->endOfMonth();
        $toolkit = $this->mock('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->shouldReceive('getDateRange')->with()->once()->andReturn([$start, $end]);

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

        // mock journals:
        $transaction = FactoryMuffin::create('Transaction');
        $journal = FactoryMuffin::create('TransactionJournal');
        $journal->transactions()->save($transaction);
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('getByDateRange')->once()->with($start, $end)->andReturn([$journal]);


        // mock toolkit
        $toolkit = $this->mock('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->shouldReceive('getDateRange')->andReturn([$start, $end]);

        // call
        $this->call('GET', '/chart/home/categories');

        // test
        $this->assertResponseOk();
    }

    /**
     * @expectedException \Firefly\Exception\FireflyException
     */
    public function testhomeCategoriesException()
    {
        $start = new \Carbon\Carbon;
        $end = new \Carbon\Carbon;

        // mock journals:
        $journal = FactoryMuffin::create('TransactionJournal');
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('getByDateRange')->once()->with($start, $end)->andReturn([$journal]);


        // mock toolkit
        $toolkit = $this->mock('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->shouldReceive('getDateRange')->andReturn([$start, $end]);

        // call
        $this->call('GET', '/chart/home/categories');

        // test
        $this->assertResponseOk();
    }

    public function testHomeAccountInfo()
    {
        $account = FactoryMuffin::create('Account');
        $second = FactoryMuffin::create('Account');
        $date = \Carbon\Carbon::createFromDate('2012', '01', '01');
        $transaction = FactoryMuffin::create('Transaction');
        $transaction->account()->associate($second);
        $journal = FactoryMuffin::create('TransactionJournal');
        $journal->transactions()->save($transaction);

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('findByName')->with($account->name)->andReturn($account);

        // mock journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('getByAccountAndDate')->once()->andReturn([$journal]);


        // call
        $this->call('GET', '/chart/home/info/' . $account->name . '/' . $date->format('d/m/Y'));

        // test
        $this->assertResponseOk();


    } //($name, $day, $month, $year)

    public function tearDown()
    {
        Mockery::close();
    }
}
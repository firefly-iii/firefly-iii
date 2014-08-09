<?php
use Carbon\Carbon as Carbon;
use Mockery as m;
use Zizaco\FactoryMuff\Facade\FactoryMuff as f;

/**
 * Class HomeControllerTest
 */
class HomeControllerTest extends TestCase
{
    protected $_accounts;
    protected $_repository;
    protected $_preferences;
    protected $_journals;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_accounts = $this->mock('Firefly\Helper\Controllers\AccountInterface');
        $this->_repository = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $this->_preferences = $this->mock('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $this->_journals = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');


    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testFlush()
    {
        $this->action('GET', 'HomeController@flush');
        $this->assertRedirectedToRoute('index');

    }

    public function testIndex()
    {
        // mock preference:
        $preference = $this->mock('Preference');
        $preference->shouldReceive('getAttribute')->with('data')->andReturn([]);


        // mock accounts:
        $this->_repository->shouldReceive('count')->once()->andReturn(0);
        $this->_repository->shouldReceive('getActiveDefault')->once()->andReturn([]);

        // mock preferences:
        $this->_preferences->shouldReceive('get')->with('frontpageAccounts', [])->andReturn($preference);

        $this->action('GET', 'HomeController@index');
        $this->assertResponseOk();
    }

    public function testIndexWithAccount()
    {
        $account = f::create('Account');
        $start = new Carbon;
        $end = new Carbon;
        $this->session(['start' => $start, 'end' => $end]);


        // mock preference:
        $preference = $this->mock('Preference');
        $preference->shouldReceive('getAttribute')->with('data')->andReturn([$account->id]);


        // mock accounts:
        $this->_repository->shouldReceive('count')->once()->andReturn(0);
        $this->_repository->shouldReceive('getByIds')->with([$account->id])->once()->andReturn([$account]);

        // mock preferences:
        $this->_preferences->shouldReceive('get')->with('frontpageAccounts', [])->andReturn($preference);

        // mock journals:
        $this->_journals->shouldReceive('getByAccountInDateRange')->once()->with($account, 15, $start, $end)->andReturn(
            [1, 2]
        );

        $this->action('GET', 'HomeController@index');
        $this->assertResponseOk();
    }

    public function testIndexWithAccounts()
    {
        $accountOne = f::create('Account');
        $accountTwo = f::create('Account');
        $accounThree = f::create('Account');
        $set = [$accountOne, $accountTwo, $accounThree];
        $ids = [$accountOne->id, $accountTwo->id, $accounThree->id];
        $start = new Carbon;
        $end = new Carbon;
        $this->session(['start' => $start, 'end' => $end]);


        // mock preference:
        $preference = $this->mock('Preference');
        $preference->shouldReceive('getAttribute')->with('data')->andReturn($ids);


        // mock accounts:
        $this->_repository->shouldReceive('count')->once()->andReturn(0);
        $this->_repository->shouldReceive('getByIds')->with($ids)->once()->andReturn(
            $set
        );

        // mock preferences:
        $this->_preferences->shouldReceive('get')->with('frontpageAccounts', [])->andReturn($preference);

        // mock journals:
        $this->_journals->shouldReceive('getByAccountInDateRange')->andReturn([1, 2]);

        $this->action('GET', 'HomeController@index');
        $this->assertResponseOk();
    }
} 
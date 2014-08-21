<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Mockery as m;
use League\FactoryMuffin\Facade as f;


/**
 * Class ChartControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class ChartControllerTest extends TestCase
{
    protected $_user;
//    protected $_repository;
    protected $_accounts;
    protected $_charts;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $this->_charts = $this->mock('Firefly\Helper\Controllers\ChartInterface');
//        $this->_category = $this->mock('Firefly\Helper\Controllers\CategoryInterface');
        $this->_user = m::mock('User', 'Eloquent');

    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testCategoryShowChart()
    {
        $this->session(['start' => new Carbon, 'end' => new Carbon, 'range' => '1M']);
        $category = f::create('Category');

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($category->user_id);

        $this->_charts->shouldReceive('categoryShowChart')->once()->andReturn([]);


        $this->action('GET', 'ChartController@categoryShowChart', $category->id);
        $this->assertResponseOk();
    }

    public function testHomeAccount()
    {
        $account = f::create('Account');
        $collection = new Collection();
        $collection->add($account);
        $this->session(['start' => new Carbon, 'end' => new Carbon, 'range' => '1M']);

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('bla@bla');
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->_accounts->shouldReceive('getByIds')->andReturn($collection);

        $this->_charts->shouldReceive('account')->once()->andReturn([]);


        $this->action('GET', 'ChartController@homeAccount');
        $this->assertResponseOk();
    }

    public function testHomeAccountInfo()
    {
        $account = f::create('Account');
        $type = f::create('AccountType');
        $type->description = 'Default account';
        $type->save();
        $account->accounttype()->associate($type);
        $account->save();
        // for successful binding:
        Auth::shouldReceive('user')->andReturn($account->user()->first());
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('bla@bla');
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($account->user_id);
        $this->_accounts->shouldReceive('findByName')->andReturn($account);

        $this->_charts->shouldReceive('accountDailySummary')->once()->andReturn(['rows' => [], 'sum' => 0]);

        $this->call('GET', 'chart/home/info/' . $account->name . '/01/08/2014');
        $this->assertResponseOk();

    }

    public function testHomeAccountWithAccount()
    {
        $account = f::create('Account');
        $this->session(['start' => new Carbon, 'end' => new Carbon, 'range' => '1M']);

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('bla@bla');
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($account->user_id);

        $this->_charts->shouldReceive('account')->once()->andReturn([]);


        $this->action('GET', 'ChartController@homeAccount', $account->id);
        $this->assertResponseOk();
    }

    public function testHomeBudgets()
    {
        $date = new Carbon;
        $this->session(['start' => $date]);
        $this->_charts->shouldReceive('budgets')->once()->with($date)->andReturn([]);

        $this->action('GET', 'ChartController@homeBudgets');
        $this->assertResponseOk();
    }

    public function testHomeCategories()
    {
        $start = new Carbon;
        $end = new Carbon;

        $this->_charts->shouldReceive('categories')->once()->with($start, $end)->andReturn([]);

        $this->session(['start' => $start, 'end' => $end]);
        $this->action('GET', 'ChartController@homeCategories');
        $this->assertResponseOk();
    }


}
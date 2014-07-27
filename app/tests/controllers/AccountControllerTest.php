<?php

use Mockery as m;
use \League\FactoryMuffin\Facade\FactoryMuffin as f;
use Illuminate\Database\Eloquent\Collection;

class AccountControllerTest extends TestCase
{
    protected $_repository;
    protected $_user;
    protected $_accounts;
    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_repository = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $this->_accounts = $this->mock('Firefly\Helper\Controllers\AccountInterface');
        $this->_user = m::mock('User','Eloquent');
        $this->app->instance('User', $this->_user);

    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testCreate()
    {
        $this->action('GET', 'AccountController@create');
        $this->assertResponseOk();

    }

    public function testDelete()
    {

        $account = f::create('Account');
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn($account->email);

        $this->action('GET', 'AccountController@delete',$account->id);
        $this->assertResponseOk();
    }

    public function testDestroy()
    {
        $account = f::create('Account');
        Auth::shouldReceive('user')->andReturn($this->_user);
        $this->_repository->shouldReceive('destroy')->once()->with("")->andReturn(true);
        $this->action('POST', 'AccountController@destroy',$account->id);
        $this->assertRedirectedToRoute('accounts.index');
        $this->assertSessionHas('success');
    }

    public function testDestroyFails()
    {
        $account = f::create('Account');
        $this->_repository->shouldReceive('destroy')->once()->with("")->andReturn(false);
        $this->action('POST', 'AccountController@destroy',$account->id);
        $this->assertRedirectedToRoute('accounts.index');
        $this->assertSessionHas('error');
    }

    public function testEdit()
    {
        $account = f::create('Account');

        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn($account->email);
        $this->_accounts->shouldReceive('openingBalanceTransaction')->once()->andReturn(null);

        $this->action('GET', 'AccountController@edit',$account->id);
        $this->assertResponseOk();
    }

    public function testIndex()
    {
        $account = f::create('Account');
        $collection = new Collection();
        $collection->add($account);

        $list = [
            'personal'      => [],
            'beneficiaries' => [],
            'initial'       => [],
            'cash'          => []
        ];

        $this->_repository->shouldReceive('get')->with()->once()->andReturn($collection);
        $this->_accounts->shouldReceive('index')->with($collection)->once()->andReturn($list);
        $this->action('GET', 'AccountController@index');
        $this->assertResponseOk();
    }

    public function testShow()
    {
        $account = f::create('Account');

        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn($account->email);
        $this->_accounts->shouldReceive('paginate')->with($account,40)->once()->andReturn();

        $this->action('GET', 'AccountController@show',$account->id);
        $this->assertResponseOk();
    }

    public function testStore()
    {
//        $this->action('POST', 'AccountController@store');
//        $this->assertResponseOk();
    }


//
//    public function testIndex()
//    {
////        // mock account type(s):
////        $personal = $this->mock('AccountType');
////        $personal->shouldReceive('getAttribute', 'description')->andReturn('Default account');
////
////        $bene = $this->mock('AccountType');
////        $bene->shouldReceive('getAttribute', 'description')->andReturn('Beneficiary account');
////
////        $initial = $this->mock('AccountType');
////        $initial->shouldReceive('getAttribute', 'description')->andReturn('Initial balance account');
////
////        $cash = $this->mock('AccountType');
////        $cash->shouldReceive('getAttribute', 'description')->andReturn('Cash account');
////
////
////        // mock account(s)
////        $one = $this->mock('Account');
////        $one->shouldReceive('getAttribute')->andReturn($personal);
////
////        $two = $this->mock('Account');
////        $two->shouldReceive('getAttribute')->andReturn($bene);
////
////        $three = $this->mock('Account');
////        $three->shouldReceive('getAttribute')->andReturn($initial);
////
////        $four = $this->mock('Account');
////        $four->shouldReceive('getAttribute')->andReturn($cash);
////        $c = new \Illuminate\Database\Eloquent\Collection([$one, $two, $three, $four]);
////
////        // mock account repository:
////        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
////        $accounts->shouldReceive('get')->andReturn($c);
////
////
////        $list = [
////            'personal'      => [$one],
////            'beneficiaries' => [$two],
////            'initial'       => [$three],
////            'cash'          => [$four]
////        ];
////
////        // mock:
////        View::shouldReceive('share');
////        View::shouldReceive('make')->with('accounts.index')->once()->andReturn(\Mockery::self())
////            ->shouldReceive('with')->once()->with('accounts', $list)->andReturn(\Mockery::self())
////            ->shouldReceive('with')->once()->with('total', 4)->andReturn(\Mockery::self());
////
//
//        // call
//        $this->call('GET', '/accounts');
//
//        // test
//        $this->assertResponseOk();
//
//    }
////
////    public function testCreate()
////    {
////        // mock:
////        View::shouldReceive('share');
////        View::shouldReceive('make')->with('accounts.create');
////
////        // call
////        $this->call('GET', '/accounts/create');
////
////        // test
////        $this->assertResponseOk();
////    }
////
////    public function testShow()
////    {
////        // mock account repository:
////        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
////        $accounts->shouldReceive('get')->with(1)->andReturn([]);
////
////        // call
////        $this->call('GET', '/accounts/1');
////
////        // test
////        $this->assertResponseOk();
////    }
////

    public function testUpdate()
    {
    }
}
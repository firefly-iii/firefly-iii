<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use League\FactoryMuffin\Facade as f;
use Mockery as m;

/**
 * Class AccountControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @coversDefaultClass \AccountController
 */
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
        $this->_user = m::mock('User', 'Eloquent');

    }

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers ::create
     */
    public function testCreate()
    {
        View::shouldReceive('make')->with('accounts.create')->once();

        $this->action('GET', 'AccountController@create');
        $this->assertResponseOk();


    }

    /**
     * @covers ::delete
     */
    public function testDelete()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Default account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'AccountController@delete', $account->id);
        $this->assertViewHas('account');
        $this->assertResponseOk();
    }

    /**
     * @covers ::delete
     */
    public function testDeleteWrongType()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Initial balance account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'AccountController@delete', $account->id);
        $this->assertViewHas('message');
        $this->assertResponseOk();
    }

    /**
     * @covers ::destroy
     */
    public function testDestroy()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Default account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();


        // for successful binding:
        Auth::shouldReceive('user')->once()->andReturn($this->_user);
        Auth::shouldReceive('check')->once()->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_repository->shouldReceive('destroy')->once()->andReturn(true);

        $this->action('POST', 'AccountController@destroy', $account->id);
        $this->assertRedirectedToRoute('accounts.index');
        $this->assertSessionHas('success');
    }

    /**
     * @covers ::destroy
     */
    public function testDestroyWrongType()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Initial balance account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();


        // for successful binding:
        Auth::shouldReceive('user')->once()->andReturn($this->_user);
        Auth::shouldReceive('check')->once()->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);

        $this->action('POST', 'AccountController@destroy', $account->id);
        $this->assertViewHas('message');
        $this->assertResponseOk();
    }

    /**
     * @covers ::destroy
     */
    public function testDestroyFails()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Default account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding:
        Auth::shouldReceive('user')->once()->andReturn($this->_user);
        Auth::shouldReceive('check')->once()->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_repository->shouldReceive('destroy')->once()->andReturn(false);

        $this->action('POST', 'AccountController@destroy', $account->id);
        $this->assertRedirectedToRoute('accounts.index');
        $this->assertSessionHas('error');
    }

    public function testEdit()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Default account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');
        $this->_accounts->shouldReceive('openingBalanceTransaction')->once()->andReturn(null);

        // test if the view works:
        View::shouldReceive('make')->with('accounts.edit')->once()->andReturn(m::self())->shouldReceive('with')->with(
            'account', m::any()
        )
            ->andReturn(m::self())->shouldReceive('with')->with('openingBalance', null)->andReturn(m::self());

        $this->action('GET', 'AccountController@edit', $account->id);
        $this->assertResponseOk();
    }


    public function testEditWrongType()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Initial balance account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'AccountController@edit', $account->id);
        $this->assertViewHas('message');
        $this->assertResponseOk();
    }

    public function testIndex()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Default account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        $collection = new Collection();
        $collection->add($account);

        $list = [
            'personal' => [],
            'beneficiaries' => [],
            'initial' => [],
            'cash' => []
        ];

        $this->_repository->shouldReceive('get')->once()->andReturn($collection);
        $this->_accounts->shouldReceive('index')->with($collection)->once()->andReturn($list);
        $this->action('GET', 'AccountController@index');
        $this->assertResponseOk();
    }

    public function testShow()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Default account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn($account->email);
        $this->session(['start' => new Carbon, 'end' => new Carbon]);

        // some more mockery
        $paginator = \Paginator::make([], 0, 10);

        $data = [
            'statistics' => [
                'period' => [
                    'in' => 0,
                    'out' => 0,
                    'diff' => 0,
                    't_in' => 0,
                    't_out' => 0,
                    't_diff' => 0
                ],
                'categories' => [],
                'budgets' => [],
                'accounts' => []
            ],
            'journals' => $paginator,
        ];

        $this->_accounts->shouldReceive('show')->once()->andReturn($data);
        $this->action('GET', 'AccountController@show', $account->id);
        $this->assertResponseOk();
    }

    public function testShowWrongType()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Initial balance account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn($account->email);


        $this->action('GET', 'AccountController@show', $account->id);
        $this->assertViewHas('message');
        $this->assertResponseOk();
    }

    public function testStore()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Default account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        $this->_repository->shouldReceive('store')->andReturn($account);
        $this->action('POST', 'AccountController@store');
        $this->assertRedirectedToRoute('accounts.index');
    }

    public function testStoreFails()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Default account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        unset($account->name);
        $this->_repository->shouldReceive('store')->andReturn($account);
        $this->action('POST', 'AccountController@store');
        $this->assertRedirectedToRoute('accounts.create');
    }

    public function testStoreRecreate()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Default account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        $this->_repository->shouldReceive('store')->andReturn($account);
        $this->action('POST', 'AccountController@store', ['create' => '1']);
        $this->assertRedirectedToRoute('accounts.create');
    }

    public function testUpdate()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Default account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_repository->shouldReceive('update')->andReturn($account);

        $this->action('POST', 'AccountController@update', $account->id);
        $this->assertRedirectedToRoute('accounts.index');

    }

    public function testUpdateWrongType()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Initial balance account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_repository->shouldReceive('update')->andReturn($account);

        $this->action('POST', 'AccountController@update', $account->id);
        $this->assertViewHas('message');
        $this->assertResponseOk();

    }

    public function testUpdateFails()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = f::create('AccountType');
        $accountType->description = 'Default account';
        $accountType->save();
        $account->accountType()->associate($accountType);
        $account->save();

        unset($account->name);
        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_repository->shouldReceive('update')->andReturn($account);

        $this->action('POST', 'AccountController@update', $account->id);
        $this->assertRedirectedToRoute('accounts.edit', $account->id);

    }
}
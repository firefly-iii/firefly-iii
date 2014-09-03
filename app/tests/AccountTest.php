<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use League\FactoryMuffin\Facade as f;
use Mockery as m;

/**
 * Class AccountTest
 *
 * Test EVERYTHING related to accounts. Models, views, and controllers.
 *
 * This class does not cover the /lib/ map, it is for a later date.
 *
 * As far as I am concerned, this class is complete! Yay!
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @coversDefaultClass \AccountController
 */
class AccountTest extends TestCase
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
        $this->_accounts   = $this->mock('Firefly\Helper\Controllers\AccountInterface');
        $this->_user       = m::mock('User', 'Eloquent');

    }

    public function tearDown()
    {

        Mockery::close();
    }

    public function testAccountModel()
    {
        // create account and user:
        $account = f::create('Account');
        $user    = f::create('User');
        $user->accounts()->save($account);

        // new account? balance should be 0.00
        $this->assertEquals(0.0, $account->balance());

        // create and link two transactions / piggybanks:
        for ($i = 0; $i < 2; $i++) {
            $transaction = f::create('Transaction');
            $transaction->account()->associate($account);
            $transaction->save();

            $piggy = f::create('Piggybank');
            $piggy->account()->associate($account);
            $piggy->save();

        }
        // test related models
        $this->assertCount(2, $account->transactions()->get());
        $this->assertCount(2, $account->piggybanks()->get());

        // predict should always be null:
        $this->assertNull($account->predict(new Carbon));

        // user should equal test user:
        $this->assertEquals($user->id, $account->user()->first()->id);

        $this->assertEquals('testing', \App::environment());

        \Log::debug('Hello from test!');
        \Log::debug('Number of accounts: ' . \Account::count());
        \Log::debug('Number of account types: ' . \AccountType::count());

        foreach (\AccountType::get() as $t) {
            \Log::debug('AccountType: #' . $t->id . ', ' . $t->type);
        }

        // whatever the account type of this account, searching for it using the
        // scope method should return one account:
        $accountType = $account->accounttype()->first();
        $accounts    = $accountType->accounts()->count();
        $this->assertCount($accounts, \Account::AccountTypeIn([$accountType->type])->get());

    }

    /**
     * @covers ::create
     */
    public function testCreate()
    {
        // test the view:
        View::shouldReceive('make')->once()->with('accounts.create')->andReturn(m::self())
            ->shouldReceive('with')->once()->with('title', 'Create account');

        // call and final test:
        $this->action('GET', 'AccountController@create');
        $this->assertResponseOk();

    }

    /**
     * @covers ::delete
     */
    public function testDelete()
    {
        // some prep work.
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = \AccountType::whereType('Default account')->first();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding with the account to delete:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        // test the view:
        View::shouldReceive('make')->once()->with('accounts.delete')->andReturn(m::self())
            ->shouldReceive('with')->once()->with('account', m::any())->andReturn(m::self())
            ->shouldReceive('with')->once()->with('title', 'Delete account "' . $account->name . '"');

        // call and final test:
        $this->action('GET', 'AccountController@delete', $account->id);
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
        $accountType = \AccountType::whereType('Default account')->first();
        $account->accountType()->associate($accountType);
        $account->save();


        // for successful binding with the account to destroy:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        // test if the repository receives an argument:
        $this->_repository->shouldReceive('destroy')->once();

        // post it:
        $this->action('POST', 'AccountController@destroy', $account->id);
        $this->assertRedirectedToRoute('accounts.index');
        $this->assertSessionHas('success');
    }

    /**
     * @covers ::edit
     */
    public function testEdit()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = \AccountType::whereType('Default account')->first();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding with the account to edit:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        // test if the repository works:
        $this->_accounts->shouldReceive('openingBalanceTransaction')->once()->with(m::any())->andReturn(null);

        // test if the view works:
        View::shouldReceive('make')->once()->with('accounts.edit')->andReturn(m::self())
            ->shouldReceive('with')->once()->with('account', m::any())->andReturn(m::self())
            ->shouldReceive('with')->once()->with('openingBalance', null)->andReturn(m::self())
            ->shouldReceive('with')->once()->with('title', 'Edit account "' . $account->name . '"');

        $this->action('GET', 'AccountController@edit', $account->id);
        $this->assertResponseOk();
    }

    /**
     * @covers ::index
     */
    public function testIndex()
    {
        // two account types:
        $personalType = \AccountType::whereType('Default account')->first();
        $benType      = \AccountType::whereType('Beneficiary account')->first();

        // create two accounts:
        /** @var \Account $account */
        $personal = f::create('Account');
        $personal->accountType()->associate($personalType);
        $personal->save();
        $ben = f::create('Account');
        $ben->accountType()->associate($benType);
        $ben->save();

        /** @var \AccountType $accountType */
        $collection = new Collection();
        $collection->add($personal);
        $collection->add($ben);

        $list = [
            'personal'      => [$personal],
            'beneficiaries' => [$ben],
        ];

        // test repository:
        $this->_repository->shouldReceive('get')->once()->andReturn($collection);

        // test view:
        View::shouldReceive('make')->once()->with('accounts.index')->andReturn(m::self())
            ->shouldReceive('with')->once()->with('accounts', $list)->andReturn(m::self())
            ->shouldReceive('with')->once()->with('title', 'All your accounts');

        $this->action('GET', 'AccountController@index');
        $this->assertResponseOk();
    }

    /**
     * @covers ::show
     */
    public function testShow()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = \AccountType::whereType('Default account')->first();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding with the account to show:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        // test view:
        View::shouldReceive('make')->once()->with('accounts.show')->andReturn(m::self())
            ->shouldReceive('with')->once()->with('account', m::any())->andReturn(m::self())
            ->shouldReceive('with')->once()->with('show', [])->andReturn(m::self())
            ->shouldReceive('with')->once()->with('title', 'Details for account "' . $account->name . '"');

        $this->_accounts->shouldReceive('show')->once()->andReturn([]);


        $this->action('GET', 'AccountController@show', $account->id);
        $this->assertResponseOk();
    }

    /**
     * @covers ::store
     */
    public function testStore()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = \AccountType::whereType('Default account')->first();
        $account->accountType()->associate($accountType);
        $account->save();

        $this->_repository->shouldReceive('store')->andReturn($account);
        $this->action('POST', 'AccountController@store');
        $this->assertRedirectedToRoute('accounts.index');
        $this->assertSessionHas('success');
    }

    /**
     * @covers ::store
     */
    public function testStoreFails()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = \AccountType::whereType('Default account')->first();
        $account->accountType()->associate($accountType);
        $account->save();

        unset($account->name);
        $this->_repository->shouldReceive('store')->andReturn($account);
        $this->action('POST', 'AccountController@store');
        $this->assertRedirectedToRoute('accounts.create');
        $this->assertSessionHas('error');
    }

    /**
     * @covers ::store
     */
    public function testStoreRecreate()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = \AccountType::whereType('Default account')->first();
        $account->accountType()->associate($accountType);
        $account->save();

        $this->_repository->shouldReceive('store')->andReturn($account);
        $this->action('POST', 'AccountController@store', ['create' => '1']);
        $this->assertRedirectedToRoute('accounts.create');
        $this->assertSessionHas('success');
    }

    /**
     * @covers ::update
     */
    public function testUpdate()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = \AccountType::whereType('Default account')->first();
        $account->accountType()->associate($accountType);
        $account->save();

        // for successful binding with the account to update:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        // test
        $this->_repository->shouldReceive('update')->andReturn($account);

        $this->action('POST', 'AccountController@update', $account->id);
        $this->assertRedirectedToRoute('accounts.index');
        $this->assertSessionHas('success');

    }

    /**
     * @covers ::update
     */
    public function testUpdateFails()
    {
        /** @var \Account $account */
        $account = f::create('Account');

        /** @var \AccountType $accountType */
        $accountType = \AccountType::whereType('Default account')->first();
        $account->accountType()->associate($accountType);
        $account->save();

        unset($account->name);

        // for successful binding with the account to show:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($account->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        // test
        $this->_repository->shouldReceive('update')->andReturn($account);

        $this->action('POST', 'AccountController@update', $account->id);
        $this->assertRedirectedToRoute('accounts.edit', $account->id);
        $this->assertSessionHas('error');

    }
}
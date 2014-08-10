<?php

use Mockery as m;
use Zizaco\FactoryMuff\Facade\FactoryMuff as f;


/**
 * Class PiggybankControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class PiggybankControllerTest extends TestCase
{
    protected $_accounts;
    protected $_piggybanks;
    protected $_user;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_user = m::mock('User', 'Eloquent');
        $this->_accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $this->_piggybanks = $this->mock('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');

    }

    public function tearDown()
    {
        m::close();
    }

    public function testCreate()
    {
        $this->_accounts->shouldReceive('getActiveDefaultAsSelectList')->once()->andReturn([]);

        $this->action('GET', 'PiggybankController@create');
        $this->assertResponseOk();
    }

    public function testDelete()
    {
        $piggyBank = f::create('Piggybank');

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn('some@email');


        $this->action('GET', 'PiggybankController@delete', $piggyBank->id);
        $this->assertResponseOk();
    }

    public function testDestroy()
    {
        $piggyBank = f::create('Piggybank');
        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('POST', 'PiggybankController@destroy', $piggyBank->id);
        $this->assertResponseStatus(302);
    }

    public function testEdit()
    {
        $piggyBank = f::create('Piggybank');

        $this->_accounts->shouldReceive('getActiveDefaultAsSelectList')->once()->andReturn([]);


        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn('some@email');

        $this->action('GET', 'PiggybankController@edit', $piggyBank->id);
        $this->assertResponseOk();
    }

    public function testIndex()
    {
        $aOne = f::create('Account');
        $aTwo = f::create('Account');

        $one = f::create('Piggybank');
        $one->account()->associate($aOne);
        $two = f::create('Piggybank');
        $two->account()->associate($aOne);
        $three = f::create('Piggybank');
        $three->account()->associate($aTwo);
        $this->_piggybanks->shouldReceive('get')->andReturn([$one, $two, $three]);
        $this->_piggybanks->shouldReceive('count')->andReturn(1);
        $this->action('GET', 'PiggybankController@index');
        $this->assertResponseOk();
    }

    public function testShow()
    {
        $piggyBank = f::create('Piggybank');
        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->andReturn('some@email');

        $this->action('GET', 'PiggybankController@show', $piggyBank->id);
        $this->assertResponseOk();
    }

    public function testStore()
    {
        $piggyBank = f::create('Piggybank');
        $this->_piggybanks->shouldReceive('store')->andReturn($piggyBank);
        $this->action('POST', 'PiggybankController@store');
        $this->assertResponseStatus(302);
    }

    public function testStoreFails()
    {
        $piggyBank = f::create('Piggybank');
        unset($piggyBank->amount);
        $this->_piggybanks->shouldReceive('store')->andReturn($piggyBank);
        $this->action('POST', 'PiggybankController@store');
        $this->assertResponseStatus(302);
    }

    public function testStoreRedirect()
    {
        $piggyBank = f::create('Piggybank');
        $this->_piggybanks->shouldReceive('store')->andReturn($piggyBank);
        $this->action('POST', 'PiggybankController@store', ['create' => '1']);
        $this->assertResponseStatus(302);
    }

    public function testUpdate()
    {
        $piggyBank = f::create('Piggybank');

        $this->_piggybanks->shouldReceive('update')->andReturn($piggyBank);

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('POST', 'PiggybankController@update', $piggyBank->id);
        $this->assertResponseStatus(302);
    }

    public function testUpdateFails()
    {
        $piggyBank = f::create('Piggybank');
        unset($piggyBank->amount);

        $this->_piggybanks->shouldReceive('update')->andReturn($piggyBank);

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('POST', 'PiggybankController@update', $piggyBank->id);
        $this->assertResponseStatus(302);
    }

    public function testUpdateAmount()
    {
        $piggyBank = f::create('Piggybank');
        $this->_piggybanks->shouldReceive('updateAmount')->andReturn($piggyBank);
        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn(
            $piggyBank->account()->first()->user_id
        );
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('POST', 'PiggybankController@updateAmount', $piggyBank->id);
        $this->assertResponseOk();
    }


} 
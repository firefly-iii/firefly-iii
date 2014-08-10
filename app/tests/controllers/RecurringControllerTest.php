<?php
use Mockery as m;
use Zizaco\FactoryMuff\Facade\FactoryMuff as f;

/**
 * Class RecurringControllerTest
 */
class RecurringControllerTest extends TestCase
{
    protected $_user;
    protected $_repository;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_user = m::mock('User', 'Eloquent');
        $this->_repository = $this->mock(
            'Firefly\Storage\RecurringTransaction\RecurringTransactionRepositoryInterface'
        );

    }

    public function tearDown()
    {
        m::close();
    }

    public function testCreate()
    {
        $this->action('GET', 'RecurringController@create');
        $this->assertResponseOk();
    }

    public function testDelete()
    {
        $recurringTransaction = f::create('RecurringTransaction');

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($recurringTransaction->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn('some@email');


        $this->action('GET', 'RecurringController@delete', $recurringTransaction->id);
        $this->assertResponseOk();
    }

    public function testDestroy()
    {
        $recurringTransaction = f::create('RecurringTransaction');

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($recurringTransaction->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');
        $this->_repository->shouldReceive('destroy')->andReturn(true);

        $this->action('POST', 'RecurringController@destroy', $recurringTransaction->id);
        $this->assertResponseStatus(302);
    }

    public function testDestroyFails()
    {
        $recurringTransaction = f::create('RecurringTransaction');

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($recurringTransaction->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');
        $this->_repository->shouldReceive('destroy')->andReturn(false);

        $this->action('POST', 'RecurringController@destroy', $recurringTransaction->id);
        $this->assertResponseStatus(302);
    }

    public function testEdit()
    {
        $recurringTransaction = f::create('RecurringTransaction');

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($recurringTransaction->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn('some@email');

        $this->action('GET', 'RecurringController@edit', $recurringTransaction->id);
        $this->assertResponseOk();
    }

    public function testIndex()
    {

        $this->_repository->shouldReceive('get')->andReturn([]);

        $this->action('GET', 'RecurringController@index');
        $this->assertResponseOk();
    }

    public function testShow()
    {
        $recurringTransaction = f::create('RecurringTransaction');

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($recurringTransaction->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');


        $this->action('GET', 'RecurringController@show', $recurringTransaction->id);
        $this->assertResponseOk();
    }

    public function testStore()
    {
        $recurringTransaction = f::create('RecurringTransaction');

        $this->_repository->shouldReceive('store')->andReturn($recurringTransaction);
        $this->action('POST', 'RecurringController@store');
        $this->assertResponseStatus(302);
    }

    public function testStoreRedirect()
    {
        $recurringTransaction = f::create('RecurringTransaction');

        $this->_repository->shouldReceive('store')->andReturn($recurringTransaction);
        $this->action('POST', 'RecurringController@store', ['create' => '1']);
        $this->assertResponseStatus(302);
    }

    public function testStoreFails()
    {
        $recurringTransaction = f::create('RecurringTransaction');
        unset($recurringTransaction->active);
        unset($recurringTransaction->automatch);

        $this->_repository->shouldReceive('store')->andReturn($recurringTransaction);
        $this->action('POST', 'RecurringController@store', ['create' => '1']);
        $this->assertResponseStatus(302);
    }

    public function testUpdate()
    {
        $recurringTransaction = f::create('RecurringTransaction');

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($recurringTransaction->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');


        $this->action('POST', 'RecurringController@update', $recurringTransaction->id);
        $this->assertResponseOk();
    }
} 
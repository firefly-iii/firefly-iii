<?php
use League\FactoryMuffin\Facade as f;
use Mockery as m;

/**
 * Class LimitControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class LimitControllerTest extends TestCase
{

    protected $_budgets;
    protected $_limits;
    protected $_user;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_user = m::mock('User', 'Eloquent');
        $this->_budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $this->_limits = $this->mock('Firefly\Storage\Limit\LimitRepositoryInterface');
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testCreate()
    {
        $this->_budgets->shouldReceive('getAsSelectList')->andReturn([]);
        $this->action('GET', 'LimitController@create');
        $this->assertResponseOk();
    }

    public function testDelete()
    {
        $limit = f::create('Limit');
        $limitrepetition = f::create('LimitRepetition');
        $limit->limitrepetitions()->save($limitrepetition);

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($limit->budget()->first()->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn('some@email');

        $this->action('GET', 'LimitController@delete', $limit->id);
        $this->assertResponseOk();
    }

    public function testDestroy()
    {
        $limit = f::create('Limit');
        $limitrepetition = f::create('LimitRepetition');
        $limit->limitrepetitions()->save($limitrepetition);

        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($limit->budget()->first()->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->_limits->shouldReceive('destroy')->once()->andReturn(true);

        $this->action('POST', 'LimitController@destroy', $limit->id);
        $this->assertResponseStatus(302);
    }

    public function testDestroyFails()
    {
        $limit = f::create('Limit');
        $limitrepetition = f::create('LimitRepetition');
        $limit->limitrepetitions()->save($limitrepetition);

        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($limit->budget()->first()->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->_limits->shouldReceive('destroy')->once()->andReturn(false);

        $this->action('POST', 'LimitController@destroy', $limit->id);
        $this->assertResponseStatus(302);
    }

    public function testDestroyRedirect()
    {
        $limit = f::create('Limit');
        $limitrepetition = f::create('LimitRepetition');
        $limit->limitrepetitions()->save($limitrepetition);

        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($limit->budget()->first()->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->_limits->shouldReceive('destroy')->once()->andReturn(true);

        $this->action('POST', 'LimitController@destroy', [$limit->id, 'from' => 'date']);
        $this->assertResponseStatus(302);
    }

    public function testEdit()
    {
        $limit = f::create('Limit');
        $limitrepetition = f::create('LimitRepetition');
        $limit->limitrepetitions()->save($limitrepetition);

        $this->_budgets->shouldReceive('getAsSelectList')->andReturn([]);

        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($limit->budget()->first()->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'LimitController@edit', $limit->id);
        $this->assertResponseOk();
    }

    public function testStore()
    {
        $limit = f::create('Limit');
        $limitrepetition = f::create('LimitRepetition');
        $limit->limitrepetitions()->save($limitrepetition);

        $this->_limits->shouldReceive('store')->once()->andReturn($limit);
        $this->action('POST', 'LimitController@store');
        $this->assertRedirectedToRoute('budgets.index.budget');
        $this->assertResponseStatus(302);
    }

    public function testStoreFails()
    {
        $budget = f::create('Budget');
        $limit = f::create('Limit');
        $limit->budget()->associate($budget);
        $limit->save();
        $limitrepetition = f::create('LimitRepetition');
        $limit->limitrepetitions()->save($limitrepetition);
        unset($limit->startdate);
        unset($limit->component_id);


        $this->_limits->shouldReceive('store')->once()->andReturn($limit);
        $this->action('POST', 'LimitController@store', $budget->id);
        $this->assertResponseStatus(302);
    }

    public function testStoreRedirect()
    {
        $budget = f::create('Budget');
        $limit = f::create('Limit');
        $limit->budget()->associate($budget);
        $limit->save();
        $limitrepetition = f::create('LimitRepetition');
        $limit->limitrepetitions()->save($limitrepetition);

        $this->_limits->shouldReceive('store')->once()->andReturn($limit);
        $this->action('POST', 'LimitController@store', [$budget->id, 'from' => 'date']);
        $this->assertResponseStatus(302);
    }

    public function testUpdate()
    {
        $limit = f::create('Limit');
        $limitrepetition = f::create('LimitRepetition');
        $limit->limitrepetitions()->save($limitrepetition);

        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($limit->budget()->first()->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->_limits->shouldReceive('update')->once()->andReturn($limit);


        $this->action(
            'POST', 'LimitController@update',
            [$limit->id,
             'date'    => '02-02-2012',
             'period'  => 'monthly',
             'repeats' => 0,
             'amount'  => '0.01'

            ]
        );
        $this->assertResponseStatus(302);
    }

    public function testUpdateFails()
    {
        $limit = f::create('Limit');
        $limitrepetition = f::create('LimitRepetition');
        $limit->limitrepetitions()->save($limitrepetition);

        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($limit->budget()->first()->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        unset($limit->amount);
        $this->_limits->shouldReceive('update')->once()->andReturn($limit);


        $this->action(
            'POST', 'LimitController@update',
            $limit->id
        );
        $this->assertResponseStatus(302);
    }

    public function testUpdateRedirect()
    {
        $limit = f::create('Limit');
        $limitrepetition = f::create('LimitRepetition');
        $limit->limitrepetitions()->save($limitrepetition);

        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->andReturn($limit->budget()->first()->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->_limits->shouldReceive('update')->once()->andReturn($limit);


        $this->action(
            'POST', 'LimitController@update',
            [$limit->id,
             'date'    => '02-02-2012',
             'period'  => 'monthly',
             'repeats' => 0,
             'amount'  => '0.01',
             'from'    => 'date'

            ]
        );
        $this->assertResponseStatus(302);
    }


}
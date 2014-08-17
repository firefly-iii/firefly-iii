<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Mockery as m;
use Zizaco\FactoryMuff\Facade\FactoryMuff as f;

/**
 * Class BudgetControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class BudgetControllerTest extends TestCase
{
    protected $_repository;
    protected $_user;
    protected $_budgets;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_repository = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $this->_budgets = $this->mock('Firefly\Helper\Controllers\BudgetInterface');
        $this->_user = m::mock('User', 'Eloquent');

    }


    public function tearDown()
    {
        Mockery::close();
    }

    public function testCreate()
    {
        $this->action('GET', 'BudgetController@create');
        $this->assertResponseOk();

    }


    public function testDelete()
    {

        $budget = f::create('Budget');

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn('some@email');

        $this->action('GET', 'BudgetController@delete', $budget->id);
        $this->assertResponseOk();
    }

    public function testDestroy()
    {
        $budget = f::create('Budget');

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        Event::shouldReceive('fire')->once()->with('budgets.change');
        $this->_repository->shouldReceive('destroy')->once()->andReturn(true);

        $this->action('POST', 'BudgetController@destroy', $budget->id);
        $this->assertRedirectedToRoute('budgets.index.budget');
        $this->assertSessionHas('success');
    }

    public function testDestroyByDate()
    {
        $budget = f::create('Budget');

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        Event::shouldReceive('fire')->once()->with('budgets.change');
        $this->_repository->shouldReceive('destroy')->once()->andReturn(true);

        $this->action('POST', 'BudgetController@destroy', [$budget->id, 'from' => 'date']);
        $this->assertRedirectedToRoute('budgets.index');
        $this->assertSessionHas('success');
    }

    public function testDestroyFails()
    {
        $budget = f::create('Budget');

        // for successful binding:
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        Event::shouldReceive('fire')->once()->with('budgets.change');
        $this->_repository->shouldReceive('destroy')->once()->andReturn(false);


        $this->action('POST', 'BudgetController@destroy', $budget->id);
        $this->assertRedirectedToRoute('budgets.index');
        $this->assertSessionHas('error');
    }

    public function testEdit()
    {
        $budget = f::create('Budget');

        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn('some@email');

        $this->action('GET', 'BudgetController@edit', $budget->id);
        $this->assertResponseOk();
    }

    public function testIndexByBudget()
    {
        $this->_repository->shouldReceive('get')->once()->andReturn([]);
        $this->action('GET', 'BudgetController@indexByBudget');
        $this->assertResponseOk();
    }

    public function testIndexByDate()
    {
        $collection = new Collection();
        $this->_repository->shouldReceive('get')->once()->andReturn($collection);
        $this->_budgets->shouldReceive('organizeByDate')->with($collection)->andReturn([]);
        $this->action('GET', 'BudgetController@indexByDate');
        $this->assertResponseOk();
    }

    public function testShow()
    {
        $budget = f::create('Budget');

        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn($budget->email);


        $this->session(['start' => new Carbon, 'end' => new Carbon]);

        $this->_budgets->shouldReceive('organizeRepetitions')->once()->andReturn([]);
        $this->action('GET', 'BudgetController@show', $budget->id);
        $this->assertResponseOk();
    }

    public function testShowNoEnvelope()
    {
        $budget = f::create('Budget');

        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn($budget->email);


        $this->session(['start' => new Carbon, 'end' => new Carbon]);

        $this->_budgets->shouldReceive('outsideRepetitions')->once()->andReturn([]);
        $this->action('GET', 'BudgetController@show', [$budget->id, 'noenvelope' => 'true']);
        $this->assertResponseOk();
    }

    public function testShowWithRep()
    {
        $budget = f::create('Budget');

        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->once()->andReturn($budget->email);


        $this->session(['start' => new Carbon, 'end' => new Carbon]);

//        $this->_budgets->shouldReceive('show')->once()->andReturn([]);
        $arr = [0 => ['limitrepetition' => null, 'limit' => null, 'date' => '']];
        $this->_budgets->shouldReceive('organizeRepetition')->once()->andReturn($arr);
        $this->action('GET', 'BudgetController@show', [$budget->id, 'rep' => '1']);
        $this->assertResponseOk();
    }

    public function testStore()
    {
        $budget = f::create('Budget');
        $this->_repository->shouldReceive('store')->andReturn($budget);
        $this->action('POST', 'BudgetController@store');
        $this->assertRedirectedToRoute('budgets.index.budget');
    }

    public function testStoreFromDate()
    {
        $budget = f::create('Budget');
        $this->_repository->shouldReceive('store')->andReturn($budget);
        $this->action('POST', 'BudgetController@store', ['from' => 'date']);
        $this->assertRedirectedToRoute('budgets.index');
    }

    public function testStoreFails()
    {
        $budget = f::create('Budget');
        unset($budget->name);
        $this->_repository->shouldReceive('store')->andReturn($budget);
        $this->action('POST', 'BudgetController@store', ['from' => 'budget']);
        $this->assertRedirectedToRoute('budgets.create');
    }

    public function testStoreRecreate()
    {
        $budget = f::create('Budget');
        $this->_repository->shouldReceive('store')->andReturn($budget);
        $this->action('POST', 'BudgetController@store', ['from' => 'budget', 'create' => '1']);
        $this->assertRedirectedToRoute('budgets.create', ['from' => 'budget']);
    }

    public function testUpdate()
    {
        $budget = f::create('Budget');
        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_repository->shouldReceive('update')->andReturn($budget);
        Event::shouldReceive('fire')->with('budgets.change');

        $this->action('POST', 'BudgetController@update', $budget->id);
        $this->assertRedirectedToRoute('budgets.index.budget');

    }

    public function testUpdateFromDate()
    {
        $budget = f::create('Budget');
        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_repository->shouldReceive('update')->andReturn($budget);
        Event::shouldReceive('fire')->with('budgets.change');
        //$this->_user->shouldReceive('budgets')->andReturn([]); // trigger

        $this->action('POST', 'BudgetController@update', [$budget->id, 'from' => 'date']);
        $this->assertRedirectedToRoute('budgets.index');

    }

    public function testUpdateFails()
    {
        $budget = f::create('Budget');
        unset($budget->name);
        // for successful binding.
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_repository->shouldReceive('update')->andReturn($budget);

        $this->action('POST', 'BudgetController@update', $budget->id);
        $this->assertRedirectedToRoute('budgets.edit', $budget->id);

    }

} 
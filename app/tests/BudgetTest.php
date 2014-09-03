<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use League\FactoryMuffin\Facade as f;
use Mockery as m;

/**
 * Class BudgetTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class BudgetTest extends TestCase
{
    protected $_repository;
    protected $_user;
    protected $_budgets;

    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_repository = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $this->_budgets    = $this->mock('Firefly\Helper\Controllers\BudgetInterface');
        $this->_user       = m::mock('User', 'Eloquent');

    }

    /**
     * @covers \Budget
     *
     */
    public function testBudgetModel() {
        // create budget:
        $budget = f::create('Budget');

        // create some transaction journals:
        $t1 = f::create('TransactionJournal');
        $t2 = f::create('TransactionJournal');

        $budget->transactionjournals()->save($t1);
        $budget->transactionjournals()->save($t2);

        $this->assertCount(2,$budget->transactionjournals()->get());
        $this->assertEquals($budget->id,$t1->budgets()->first()->id);

    }


    public function tearDown()
    {
        Mockery::close();
    }

    public function testCreate()
    {
        // test config:
        $periods = [
            'weekly'    => 'A week',
            'monthly'   => 'A month',
            'quarterly' => 'A quarter',
            'half-year' => 'Six months',
            'yearly'    => 'A year',
        ];
        // test the view:
        View::shouldReceive('make')->with('budgets.create')->once()->andReturn(m::self())
            ->shouldReceive('with')->with('periods', $periods)->once()->andReturn(m::self())
            ->shouldReceive('with')->with('title', 'Create a new budget')->once();


        $this->action('GET', 'BudgetController@create');
        $this->assertResponseOk();

    }


    public function testDelete()
    {

        $budget = f::create('Budget');

        // test the view:
        View::shouldReceive('make')->with('budgets.delete')->once()->andReturn(m::self())
            ->shouldReceive('with')->with('budget', m::any())->once()->andReturn(m::self())
            ->shouldReceive('with')->with('title', 'Delete budget "' . $budget->name . '"')->once();

        // for successful binding with the budget to delete:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'BudgetController@delete', $budget->id);
        $this->assertResponseOk();
    }

    public function testDestroy()
    {
        $budget = f::create('Budget');

        // for successful binding with the budget to delete:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        // fire the event:
        Event::shouldReceive('fire')->once()->with('budgets.destroy', [$budget]);

        // fire the repository:
        $this->_repository->shouldReceive('destroy')->once()->andReturn(true);

        // fire and test:
        $this->action('POST', 'BudgetController@destroy', $budget->id);
        $this->assertRedirectedToRoute('budgets.index.budget');
        $this->assertSessionHas('success');
    }

    public function testDestroyFromDate()
    {
        $budget = f::create('Budget');

        // for successful binding with the budget to delete:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        // fire the event:
        Event::shouldReceive('fire')->once()->with('budgets.destroy', [$budget]);

        // fire the repository:
        $this->_repository->shouldReceive('destroy')->once()->andReturn(true);

        // fire and test:
        $this->action('POST', 'BudgetController@destroy', [$budget->id, 'from' => 'date']);
        $this->assertRedirectedToRoute('budgets.index');
        $this->assertSessionHas('success');
    }


    public function testEdit()
    {
        $budget = f::create('Budget');

        // for successful binding with the budget to delete:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email'); //

        // test the view:
        View::shouldReceive('make')->with('budgets.edit')->once()->andReturn(m::self())
            ->shouldReceive('with')->with('budget', m::any())->once()->andReturn(m::self())
            ->shouldReceive('with')->with('title', 'Edit budget "' . $budget->name . '"')->once();


        $this->action('GET', 'BudgetController@edit', $budget->id);
        $this->assertResponseOk();
    }

    public function testIndexByBudget()
    {
        $this->_repository->shouldReceive('get')->once()->andReturn([]);

        // test the view:
        View::shouldReceive('make')->with('budgets.indexByBudget')->once()->andReturn(m::self())
            ->shouldReceive('with')->with('budgets', [])->once()->andReturn(m::self())
            ->shouldReceive('with')->with('today', m::any())->once()->andReturn(m::self())
            ->shouldReceive('with')->with('title', 'All your budgets grouped by budget')->once();


        $this->action('GET', 'BudgetController@indexByBudget');
        $this->assertResponseOk();
    }

    public function testIndexByDate()
    {
        $collection = new Collection();

        // test the view:
        View::shouldReceive('make')->with('budgets.indexByDate')->once()->andReturn(m::self())
            ->shouldReceive('with')->with('budgets', [])->once()->andReturn(m::self())
            ->shouldReceive('with')->with('title', 'All your budgets grouped by date')->once();


        $this->_repository->shouldReceive('get')->once()->andReturn($collection);
        $this->_budgets->shouldReceive('organizeByDate')->with($collection)->andReturn([]);

        $this->action('GET', 'BudgetController@indexByDate');
        $this->assertResponseOk();
    }

    public function testShowDefault()
    {
        $budget = f::create('Budget');

        // for successful binding with the budget to show:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email'); //

        // test repository:
        $this->_budgets->shouldReceive('organizeRepetitions')->with(m::any(), false)->once()->andReturn([]);

        // test the view:
        View::shouldReceive('make')->with('budgets.show')->once()->andReturn(m::self())
            ->shouldReceive('with')->with('budget', m::any())->once()->andReturn(m::self())
            ->shouldReceive('with')->with('repetitions', [])->once()->andReturn(m::self())
            ->shouldReceive('with')->with('view', 4)->once()->andReturn(m::self())
            ->shouldReceive('with')->with('highlight', null)->once()->andReturn(m::self())
            ->shouldReceive('with')->with('useSessionDates', false)->once()->andReturn(m::self())
            ->shouldReceive('with')->with('title', $budget->name)->once();

        $this->action('GET', 'BudgetController@show', $budget->id);
        $this->assertResponseOk();
    }

    public function testShowOutsideEnvelope()
    {
        $budget = f::create('Budget');

        // for successful binding with the budget to show:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->between(0, 2)->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->session(['start' => new Carbon, 'end' => new Carbon]);

        // test repository:
        $this->_budgets->shouldReceive('outsideRepetitions')->with(m::any())->once()->andReturn([]);

        // test the view:
        View::shouldReceive('make')->with('budgets.show')->once()->andReturn(m::self())
            ->shouldReceive('with')->with('budget', m::any())->once()->andReturn(m::self())
            ->shouldReceive('with')->with('repetitions', [])->once()->andReturn(m::self())
            ->shouldReceive('with')->with('view', 2)->once()->andReturn(m::self())
            ->shouldReceive('with')->with('highlight', null)->once()->andReturn(m::self())
            ->shouldReceive('with')->with('useSessionDates', false)->once()->andReturn(m::self())
            ->shouldReceive('with')->with('title', $budget->name . ', transactions outside an envelope')->once();

        $this->action('GET', 'BudgetController@show', [$budget->id, null, 'noenvelope' => 'true']);
        $this->assertResponseOk();
    }

    public function testShowWithRepetition()
    {
        $budget     = f::create('Budget');
        $limit      = f::create('Limit');
        $repetition = f::create('LimitRepetition');
        $limit->limitrepetitions()->save($repetition);
        $budget->limits()->save($limit);

        // for successful binding with the budget to show:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->between(0, 2)->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->session(['start' => new Carbon, 'end' => new Carbon]);

        // test repository:
        $this->_budgets->shouldReceive('organizeRepetition')->with(m::any())->once()->andReturn([]);

        // test the view:
        View::shouldReceive('make')->with('budgets.show')->once()->andReturn(m::self())
            ->shouldReceive('with')->with('budget', m::any())->once()->andReturn(m::self())
            ->shouldReceive('with')->with('repetitions', [])->once()->andReturn(m::self())
            ->shouldReceive('with')->with('view', 1)->once()->andReturn(m::self())
            ->shouldReceive('with')->with('highlight', null)->once()->andReturn(m::self())
            ->shouldReceive('with')->with('useSessionDates', false)->once()->andReturn(m::self())
            ->shouldReceive('with')->with('title',
                                       $budget->name . ', ' . $repetition->periodShow() . ', ' .
                                       mf($limit->amount, false))->once();

        $this->action('GET', 'BudgetController@show', [$budget->id, $repetition->id]);
        $this->assertResponseOk();
    }


    public function testStore()
    {
        $budget = f::create('Budget');

        // test repository:
        $this->_repository->shouldReceive('store')->andReturn($budget);

        // test event:
        Event::shouldReceive('fire')->with('budgets.store', [$budget])->once();

        $this->action('POST', 'BudgetController@store');
        $this->assertRedirectedToRoute('budgets.index.budget');
        $this->assertSessionHas('success');
    }

    public function testStoreComingFromDate()
    {
        $budget = f::create('Budget');

        // test repository:
        $this->_repository->shouldReceive('store')->andReturn($budget);

        // test event:
        Event::shouldReceive('fire')->with('budgets.store', [$budget])->once();

        $this->action('POST', 'BudgetController@store', ['from' => 'date']);
        $this->assertRedirectedToRoute('budgets.index');
        $this->assertSessionHas('success');
    }

    public function testStoreFails()
    {
        $budget = f::create('Budget');
        unset($budget->name);

        // test repository:
        $this->_repository->shouldReceive('store')->once()->andReturn($budget);

        // test event:
        $this->action('POST', 'BudgetController@store', ['name' => null]);
        $this->assertRedirectedToRoute('budgets.create');
        $this->assertSessionHas('error');
    }

    public function testStoreWithRecreation()
    {
        $budget = f::create('Budget');

        // test repository:
        $this->_repository->shouldReceive('store')->once()->andReturn($budget);

        // test event:
        Event::shouldReceive('fire')->with('budgets.store', [$budget])->once();

        $this->action('POST', 'BudgetController@store', ['name' => $budget->name, 'create' => '1']);
        $this->assertRedirectedTo('http://localhost/budgets/create?');
        $this->assertSessionHas('success');
    }

    public function testUpdate()
    {
        $budget = f::create('Budget');

        // for successful binding with the budget to update:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->between(0, 2)->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        // update the budget
        $this->_repository->shouldReceive('update')->andReturn($budget);

        // fire in the hole!
        Event::shouldReceive('fire')->with('budgets.update', [$budget]);

        $this->action('POST', 'BudgetController@update', $budget->id);
        $this->assertRedirectedToRoute('budgets.index.budget');
        $this->assertSessionHas('success');

    }

    public function testUpdateFails()
    {
        $budget = f::create('Budget');
        unset($budget->name);

        // for successful binding with the budget to update:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->between(0, 2)->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        // update the budget
        $this->_repository->shouldReceive('update')->andReturn($budget);

        $this->action('POST', 'BudgetController@update', [$budget->id]);
        $this->assertRedirectedToRoute('budgets.edit',$budget->id);
        $this->assertSessionHas('error');

    }

    public function testUpdateFromDate()
    {
        $budget = f::create('Budget');

        // for successful binding with the budget to update:
        Auth::shouldReceive('user')->andReturn($this->_user)->between(1, 3);
        Auth::shouldReceive('check')->andReturn(true)->between(1, 2);
        $this->_user->shouldReceive('getAttribute')->with('id')->between(0, 2)->andReturn($budget->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        // update the budget
        $this->_repository->shouldReceive('update')->andReturn($budget);

        // fire in the hole!
        Event::shouldReceive('fire')->with('budgets.update', [$budget]);

        $this->action('POST', 'BudgetController@update', [$budget->id, 'from' => 'date']);
        $this->assertRedirectedToRoute('budgets.index');
        $this->assertSessionHas('success');

    }
} 
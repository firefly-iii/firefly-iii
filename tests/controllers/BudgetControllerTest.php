<?php
use Carbon\Carbon;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class BudgetControllerTest
 */
class BudgetControllerTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();

    }

    public function testAmount()
    {
        $repository      = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $limitRepetition = FactoryMuffin::create('FireflyIII\Models\LimitRepetition');
        $budget          = $limitRepetition->budgetlimit->budget;
        $this->be($budget->user);
        $today = new Carbon;

        $this->session(['start' => $today]);
        $repository->shouldReceive('updateLimitAmount')->once()->andReturn($limitRepetition);
        $this->call('POST', '/budgets/amount/' . $budget->id, ['amount' => 100, '_token' => 'replaceme']);
        $this->assertResponseOk();

    }

    public function testCreate()
    {
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);
        $this->call('GET', '/budgets/create');
        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Create a new budget');
    }

    public function testDelete()
    {
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);
        $this->call('GET', '/budgets/delete/' . $budget->id);

        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Delete budget' . e($budget->name) . '"');
        $this->assertViewHas('budget');
        $this->assertSessionHas('budgets.delete.url');
    }

    public function testDestroy()
    {
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);

        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $repository->shouldReceive('destroy')->andReturn(true);

        $this->call('POST', '/budgets/destroy/' . $budget->id, ['_token' => 'replaceme']);

        $this->assertSessionHas('success', 'The  budget "' . e($budget->name) . '" was deleted.');

    }

    public function testEdit()
    {
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);

        $this->call('GET', '/budgets/edit/' . $budget->id);

        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Edit budget "' . e($budget->name) . '"');
        $this->assertViewHas('budget');
        $this->assertSessionHas('budgets.edit.url');
    }

    public function testIndex()
    {
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);
        $collection = new Collection;
        $collection->push($budget);
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');

        $repository->shouldReceive('getActiveBudgets')->once()->andReturn($collection);
        $repository->shouldReceive('getInactiveBudgets')->once()->andReturn($collection);
        $repository->shouldReceive('cleanupBudgets')->once();
        $repository->shouldReceive('spentInMonth')->once();
        $repository->shouldReceive('getCurrentRepetition')->once();
        Amount::shouldReceive('getCurrencySymbol')->andReturn('x');
        Amount::shouldReceive('format')->andReturn('x');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');
        $this->call('GET', '/budgets');

        $this->assertResponseOk();
        $this->assertViewHas('budgets');
        $this->assertViewHas('inactive');
        $this->assertViewHas('inactive');

    }

    public function testNoBudget()
    {
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $repository->shouldReceive('getWithoutBudget')->andReturn(new Collection);

        $this->call('GET', '/budgets/list/noBudget');
        $this->assertResponseOk();
        $this->assertViewHas('list');
        $this->assertViewHas('subTitle');
    }

    public function testPostUpdateIncome()
    {
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);
        $date = Carbon::now()->startOfMonth()->format('FY');
        Preferences::shouldReceive('set')->once()->withArgs(['budgetIncomeTotal' . $date, 1001]);

        $this->call('POST', '/budgets/income', ['_token' => 'replaceme', 'amount' => 1001]);
        $this->assertResponseStatus(302);
        $this->assertRedirectedToRoute('budgets.index');
    }

    public function testShow()
    {
        $budget     = FactoryMuffin::create('FireflyIII\Models\Budget');
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $this->be($budget->user);

        Amount::shouldReceive('getCurrencyCode')->andReturn('x');
        Amount::shouldReceive('format')->andReturn('x');
        $repository->shouldReceive('getJournals')->andReturn(new Collection);
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection);


        $this->call('GET', '/budgets/show/' . $budget->id);
        $this->assertResponseOk();

    }

    public function testShowInvalidRepetition()
    {

        $repetition           = FactoryMuffin::create('FireflyIII\Models\LimitRepetition');
        $budget               = $repetition->budgetLimit->budget;
        $otherBudget          = FactoryMuffin::create('FireflyIII\Models\Budget');
        $otherBudget->user_id = $budget->user_id;
        $otherBudget->save();

        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $this->be($otherBudget->user);

        Amount::shouldReceive('getCurrencyCode')->andReturn('x');
        Amount::shouldReceive('format')->andReturn('x');
        $repository->shouldReceive('getJournals')->andReturn(new Collection);
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection);


        $this->call('GET', '/budgets/show/' . $otherBudget->id . '/' . $repetition->id);
        $this->assertResponseOk();
        $this->assertViewHas('message', 'Invalid selection.');

    }

    public function testStore()
    {
        // a budget:
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);

        $data = [
            'name'   => 'New test budget ' . rand(1, 1000),
            '_token' => 'replaceme'
        ];

        // fake validation routine:
        $request = $this->mock('FireflyIII\Http\Requests\BudgetFormRequest');
        $request->shouldReceive('input')->andReturn('');

        // fake store routine:
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $repository->shouldReceive('store')->andReturn($budget);

        $this->call('POST', '/budgets/store', $data);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');

    }

    public function testStoreAndRedirect()
    {
        // a budget:
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);

        $data = [
            'name'           => 'New test budget ' . rand(1, 1000),
            '_token'         => 'replaceme',
            'create_another' => 1,
        ];

        // fake validation routine:
        $request = $this->mock('FireflyIII\Http\Requests\BudgetFormRequest');
        $request->shouldReceive('input')->andReturn('');

        // fake store routine:
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $repository->shouldReceive('store')->andReturn($budget);

        $this->call('POST', '/budgets/store', $data);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');

    }

    public function testUpdate()
    {

        // a budget:
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);

        $data = [
            'name'   => 'Edited test account ' . rand(1, 1000),
            'active' => 1,
            '_token' => 'replaceme'
        ];

        // fake validation routine:
        $request = $this->mock('FireflyIII\Http\Requests\BudgetFormRequest');
        $request->shouldReceive('input')->andReturn('');

        // fake update routine:
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $repository->shouldReceive('update')->andReturn($budget);

        $this->call('POST', '/budgets/update/' . $budget->id, $data);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');
    }

    public function testUpdateAndRedirect()
    {

        // a budget:
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);

        $data = [
            'name'           => 'Edited test account ' . rand(1, 1000),
            'active'         => 1,
            '_token'         => 'replaceme',
            'return_to_edit' => 1,
        ];

        // fake validation routine:
        $request = $this->mock('FireflyIII\Http\Requests\BudgetFormRequest');
        $request->shouldReceive('input')->andReturn('');

        // fake update routine:
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $repository->shouldReceive('update')->andReturn($budget);

        $this->call('POST', '/budgets/update/' . $budget->id, $data);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');
    }

    public function testUpdateIncome()
    {

        // a budget:
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);
        $date = Carbon::now()->format('FY');
        $pref = FactoryMuffin::create('FireflyIII\Models\Preference');
        Preferences::shouldReceive('get')->withArgs(['budgetIncomeTotal' . $date, 1000])->andReturn($pref);
        Amount::shouldReceive('format')->andReturn('xx');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');
        Amount::shouldReceive('getCurrencySymbol')->andReturn('X');



        $this->call('GET', '/budgets/income');
        $this->assertResponseOk();
        $this->assertViewHas('amount');
    }
}
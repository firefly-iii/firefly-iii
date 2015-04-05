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

        $this->assertCount(1, DB::table('budgets')->where('id', $budget->id)->get());

        $this->call('POST', '/budgets/destroy/' . $budget->id, ['_token' => 'replaceme']);

        $this->assertSessionHas('success', 'The  budget "' . e($budget->name) . '" was deleted.');
        $this->assertCount(0, DB::table('budgets')->wherenull('deleted_at')->where('id', $budget->id)->get());
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
        $date = Carbon::now()->startOfMonth()->format('FY');
        Preferences::shouldReceive('set')->once()->withArgs(['budgetIncomeTotal' . $date, 1001]);

        $this->call('POST', '/budgets/income', ['_token' => 'replaceme', 'amount' => 1001]);
        $this->assertResponseStatus(302);
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

    public function testStore()
    {
        $this->markTestIncomplete();
    }

    public function testUpdate()
    {
        $this->markTestIncomplete();
    }

    public function testUpdateIncome()
    {
        $this->markTestIncomplete();
    }
}
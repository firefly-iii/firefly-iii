<?php
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
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

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::amount
     */
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

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::amount
     */
    public function testAmountZero()
    {
        $repository      = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $limitRepetition = FactoryMuffin::create('FireflyIII\Models\LimitRepetition');
        $budget          = $limitRepetition->budgetlimit->budget;
        $this->be($budget->user);
        $today = new Carbon;

        $this->session(['start' => $today]);
        $repository->shouldReceive('updateLimitAmount')->once()->andReturn($limitRepetition);
        $this->call('POST', '/budgets/amount/' . $budget->id, ['amount' => 0, '_token' => 'replaceme']);
        $this->assertResponseOk();

    }

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::create
     */
    public function testCreate()
    {
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);
        $this->call('GET', '/budgets/create');
        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Create a new budget');
    }

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::delete
     */
    public function testDelete()
    {
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);
        $this->call('GET', '/budgets/delete/' . $budget->id);

        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Delete budget "' . e($budget->name) . '"');
        $this->assertViewHas('budget');
        $this->assertSessionHas('budgets.delete.url');
    }

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::destroy
     */
    public function testDestroy()
    {
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);

        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $repository->shouldReceive('destroy')->andReturn(true);

        $this->call('POST', '/budgets/destroy/' . $budget->id, ['_token' => 'replaceme']);

        $this->assertSessionHas('success', 'The  budget "' . e($budget->name) . '" was deleted.');

    }

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::edit
     */
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

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::index
     */
    public function testIndex()
    {
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $budget   = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);
        $collection = new Collection;
        $collection->push($budget);
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $repetition = FactoryMuffin::create('FireflyIII\Models\LimitRepetition');

        $repository->shouldReceive('getActiveBudgets')->once()->andReturn($collection);
        $repository->shouldReceive('getInactiveBudgets')->once()->andReturn($collection);
        $repository->shouldReceive('cleanupBudgets')->once();
        $repository->shouldReceive('spentInPeriodCorrected')->once();
        $repository->shouldReceive('getCurrentRepetition')->once()->andReturn($repetition);
        Amount::shouldReceive('getCurrencySymbol')->andReturn('x');
        Amount::shouldReceive('format')->andReturn('x');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');
        Amount::shouldReceive('getDefaultCurrency')->andReturn($currency);
        $this->call('GET', '/budgets');

        $this->assertResponseOk();
        $this->assertViewHas('budgets');
        $this->assertViewHas('inactive');
        $this->assertViewHas('inactive');

    }

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::noBudget
     */
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

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::postUpdateIncome
     */
    public function testPostUpdateIncome()
    {


        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);
        $date = Carbon::now()->startOfMonth()->format('FY');
        Preferences::shouldReceive('set')->once()->withArgs(['budgetIncomeTotal' . $date, 1001]);
        Preferences::shouldReceive('mark')->once()->andReturn(true);
        $lastActivity       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $lastActivity->data = microtime();
        Preferences::shouldReceive('lastActivity')->andReturn($lastActivity);

        // language preference:
        $language       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $language->data = 'en';
        $language->save();
        Preferences::shouldReceive('get')->withAnyArgs()->andReturn($language);

        $this->call('POST', '/budgets/income', ['_token' => 'replaceme', 'amount' => 1001]);
        $this->assertResponseStatus(302);
        $this->assertRedirectedToRoute('budgets.index');
    }

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::show
     */
    public function testShow()
    {
        $budget     = FactoryMuffin::create('FireflyIII\Models\Budget');
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $this->be($budget->user);

        $paginator = new LengthAwarePaginator(new Collection, 0, 20, 1);

        Amount::shouldReceive('getCurrencyCode')->andReturn('x');
        Amount::shouldReceive('format')->andReturn('x');
        $repository->shouldReceive('getJournals')->andReturn($paginator);
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection);


        $this->call('GET', '/budgets/show/' . $budget->id);
        $this->assertResponseOk();

    }

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::show
     */
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

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::show
     */
    public function testShowRepetition()
    {
        $repetition = FactoryMuffin::create('FireflyIII\Models\LimitRepetition');
        $budget     = $repetition->budgetLimit->budget;
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $this->be($budget->user);

        $paginator = new LengthAwarePaginator(new Collection, 0, 20, 1);

        Amount::shouldReceive('getCurrencyCode')->andReturn('x');
        Amount::shouldReceive('format')->andReturn('x');
        $repository->shouldReceive('getJournals')->andReturn($paginator);
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection);


        $this->call('GET', '/budgets/show/' . $budget->id . '/' . $repetition->id);
        $this->assertResponseOk();

    }

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::store
     */
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

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::store
     */
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

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::update
     */
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

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::update
     */
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

    /**
     * @covers FireflyIII\Http\Controllers\BudgetController::updateIncome
     */
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
        $lastActivity       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $lastActivity->data = microtime();
        Preferences::shouldReceive('lastActivity')->andReturn($lastActivity);

        // language preference:
        $language       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $language->data = 'en';
        $language->save();
        Preferences::shouldReceive('get')->withAnyArgs()->andReturn($language);


        $this->call('GET', '/budgets/income');
        $this->assertResponseOk();
        $this->assertViewHas('amount');
    }
}

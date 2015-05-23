<?php
use Carbon\Carbon;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;


/**
 * Class ChartBudgetControllerTest
 */
class ChartBudgetControllerTest extends TestCase
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
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\BudgetController::budget
     */
    public function testBudget()
    {
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        $this->be($budget->user);

        $this->call('GET', '/chart/budget/' . $budget->id);
        $this->assertResponseOk();


    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\BudgetController::budgetLimit
     */
    public function testBudgetLimit()
    {
        $user   = FactoryMuffin::create('FireflyIII\User');
        $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
        /** @var \FireflyIII\Models\BudgetLimit $limit */
        $limit = FactoryMuffin::create('FireflyIII\Models\BudgetLimit');
        /** @var \FireflyIII\Models\LimitRepetition $repetition */
        $repetition = FactoryMuffin::create('FireflyIII\Models\LimitRepetition');

        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();

        $budget->user_id             = $user->id;
        $limit->budget_id            = $budget->id;
        $limit->startdate            = $start;
        $repetition->budget_limit_id = $limit->id;
        $repetition->startdate       = $start;
        $repetition->enddate         = $end;

        $budget->save();
        $limit->save();
        $repetition->save();


        $this->be($user);

        $this->call('GET', '/chart/budget/' . $budget->id . '/' . $repetition->id);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\BudgetController::frontpage
     */
    public function testFrontpage()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $start       = Carbon::now()->startOfMonth();
        $end         = Carbon::now()->endOfMonth();
        $budgets     = new Collection;
        $limits      = [];
        $repetitions = [];

        for ($i = 0; $i < 5; $i++) {
            /** @var \FireflyIII\Models\Budget $budget */
            $budget = FactoryMuffin::create('FireflyIII\Models\Budget');
            $budgets->push($budget);

            /** @var \FireflyIII\Models\BudgetLimit $limit */
            $limit            = FactoryMuffin::create('FireflyIII\Models\BudgetLimit');
            $limit->budget_id = $budget->id;
            $limit->startdate = $start;
            $limit->save();

            $set      = new Collection([$limit]);
            $limits[] = $set;

            /** @var \FireflyIII\Models\LimitRepetition $repetition */
            $repetition                  = FactoryMuffin::create('FireflyIII\Models\LimitRepetition');
            $repetition->budget_limit_id = $limit->id;
            $repetition->startdate       = $start;
            $repetition->enddate         = $end;
            $repetition->save();
            $set           = new Collection([$repetition]);
            $repetitions[] = $set;
        }

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');

        // fake!
        $repository->shouldReceive('getBudgets')->andReturn($budgets);
        $repository->shouldReceive('getBudgetLimitRepetitions')->andReturn($repetitions[0], $repetitions[1], new Collection);
        $repository->shouldReceive('spentInPeriodCorrected')->andReturn(10);
        $repository->shouldReceive('getWithoutBudgetSum')->andReturn(10);

        $this->call('GET', '/chart/budget/frontpage');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\BudgetController::year
     */
    public function testYear()
    {
        $user       = FactoryMuffin::create('FireflyIII\User');
        $budget     = FactoryMuffin::create('FireflyIII\Models\Budget');
        $collection = new Collection([$budget]);
        $this->be($user);


        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');

        // fake!
        $repository->shouldReceive('getBudgets')->andReturn($collection);
        $repository->shouldReceive('spentInPeriodCorrected')->andReturn(0);


        $this->call('GET', '/chart/budget/year/2015');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\BudgetController::year
     */
    public function testYearShared()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $this->call('GET', '/chart/budget/year/2015/shared');
        $this->assertResponseOk();
    }

}

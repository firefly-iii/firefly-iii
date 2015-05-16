<?php

use Carbon\Carbon;
use FireflyIII\Models\Preference;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class GoogleChartControllerTest
 */
class GoogleChartControllerTest extends TestCase
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

    public function testAccountBalanceChart()
    {
        $account = FactoryMuffin::create('FireflyIII\Models\Account');
        $this->be($account->user);

        // mock stuff:
        Steam::shouldReceive('balance')->andReturn(0);

        $this->call('GET', '/chart/account/' . $account->id);
        $this->assertResponseOk();
    }

    public function testAllAccountsBalanceChart()
    {
        $account = FactoryMuffin::create('FireflyIII\Models\Account');
        $this->be($account->user);
        $collection = new Collection;
        $collection->push($account);

        //mock stuff:
        Preferences::shouldReceive('get')->andReturn(new Preference);
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('getFrontpageAccounts')->andReturn($collection);

        $this->call('GET', '/chart/home/account');
        $this->assertResponseOk();


    }

    public function testAllBudgetsHomeChart()
    {

        $budget1 = FactoryMuffin::create('FireflyIII\Models\Budget');
        $budget2 = FactoryMuffin::create('FireflyIII\Models\Budget');
        $budget3 = FactoryMuffin::create('FireflyIII\Models\Budget');
        $budget4 = FactoryMuffin::create('FireflyIII\Models\Budget');
        $budgets = new Collection([$budget1, $budget2, $budget3, $budget4]);

        $rep1 = FactoryMuffin::create('FireflyIII\Models\LimitRepetition');
        $rep2 = FactoryMuffin::create('FireflyIII\Models\LimitRepetition');
        $rep3 = FactoryMuffin::create('FireflyIII\Models\LimitRepetition');

        $rep1->amount = 6;
        $rep1->save();
        $rep2->amount = 18;
        $rep2->save();
        $this->be($budget1->user);


        $coll1 = new Collection([$rep1]);
        $coll2 = new Collection([$rep2]);
        $coll3 = new Collection([$rep3]);
        $coll4 = new Collection;


        // mock stuff:
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $repository->shouldReceive('getBudgets')->andReturn($budgets);
        $repository->shouldReceive('getBudgetLimitRepetitions')->andReturn($coll1, $coll2, $coll3, $coll4);
        $repository->shouldReceive('sumBudgetExpensesInPeriod')->andReturn(12, 12, 12, -12);
        $repository->shouldReceive('getWithoutBudgetSum')->andReturn(0);

        $this->call('GET', '/chart/home/budgets');
        $this->assertResponseOk();
    }

    public function testAllCategoriesHomeChart()
    {
        $category = FactoryMuffin::create('FireflyIII\Models\Category');

        $this->be($category->user);
        $category->save();
        $category->sum = 100;
        $collection    = new Collection;
        $collection->push($category);

        // mock stuff:
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        $repository->shouldReceive('getCategoriesAndExpenses')->andReturn($collection);
        Crypt::shouldReceive('decrypt')->andReturn('Hello!');
        Crypt::shouldReceive('encrypt')->andReturn('Hello!');


        $this->call('GET', '/chart/home/categories');
        $this->assertResponseOk();
    }

    public function testBillOverview()
    {
        $bill       = FactoryMuffin::create('FireflyIII\Models\Bill');
        $journal    = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $collection = new Collection;
        $collection->push($journal);
        $this->be($bill->user);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $repository->shouldReceive('getJournals')->andReturn($collection);


        // call!
        $this->call('GET', '/chart/bills/' . $bill->id);
        $this->assertResponseOk();
    }

    public function testBillsOverview()
    {
        $bill1    = FactoryMuffin::create('FireflyIII\Models\Bill');
        $bill2    = FactoryMuffin::create('FireflyIII\Models\Bill');
        $journal1 = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $journal2 = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $card1    = FactoryMuffin::create('FireflyIII\Models\Account');
        $card2    = FactoryMuffin::create('FireflyIII\Models\Account');
        $fake     = FactoryMuffin::create('FireflyIII\Models\Bill');


        $bills           = new Collection([$bill1, $bill2]);
        $journals        = new Collection([$journal1, $journal2]);
        $cards           = new Collection([$card1, $card2]);
        $emptyCollection = new Collection;
        $ranges          = [['start' => new Carbon, 'end' => new Carbon]];
        $this->be($bill1->user);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $accounts   = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');

        // calls:
        $repository->shouldReceive('getActiveBills')->andReturn($bills);
        $repository->shouldReceive('getRanges')->andReturn($ranges);
        $repository->shouldReceive('getJournalsInRange')->andReturn($journals, $emptyCollection);
        $accounts->shouldReceive('getCreditCards')->andReturn($cards);
        $accounts->shouldReceive('getTransfersInRange')->andReturn($journals, $emptyCollection);
        $repository->shouldReceive('createFakeBill')->andReturn($fake);
        Steam::shouldReceive('balance')->andReturn(-1, 0);

        $this->call('GET', '/chart/home/bills');
        $this->assertResponseOk();
    }

    public function testBudgetLimitSpending()
    {
        $repetition            = FactoryMuffin::create('FireflyIII\Models\LimitRepetition');
        $repetition->startdate = Carbon::now()->startOfMonth();
        $repetition->enddate   = Carbon::now()->endOfMonth();
        $repetition->save();
        $budget = $repetition->budgetlimit->budget;
        $this->be($budget->user);
        ///chart/budget/{budget}/{limitrepetition}

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $repository->shouldReceive('expensesOnDay')->andReturn(rand(1, 1000));

        $this->call('GET', '/chart/budget/' . $budget->id . '/' . $repetition->id);
        $this->assertResponseOk();

    }

    public function testCategoryOverviewChart()
    {
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $pref     = FactoryMuffin::create('FireflyIII\Models\Preference');
        $this->be($category->user);
        $start = new Carbon();
        $start->subDay();
        $end = new Carbon;
        $end->addWeek();

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        $repository->shouldReceive('getFirstActivityDate')->andReturn($start);
        $repository->shouldReceive('spentInPeriod')->andReturn(rand(1, 100));
        Preferences::shouldReceive('get')->andReturn($pref);

        Navigation::shouldReceive('startOfPeriod')->andReturn($start);
        Navigation::shouldReceive('endOfPeriod')->andReturn($start);
        Navigation::shouldReceive('addPeriod')->andReturn($end);

        $this->call('GET', '/chart/category/' . $category->id . '/overview');
        $this->assertResponseOk();
    }

    public function testCategoryPeriodChart()
    {
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        $repository->shouldReceive('spentOnDaySum')->andReturn(rand(1, 100));

        $this->call('GET', '/chart/category/' . $category->id . '/period');
        $this->assertResponseOk();
    }

    public function testPiggyBankHistory()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);

        $obj        = new stdClass;
        $obj->sum   = 12;
        $obj->date  = new Carbon;
        $collection = new Collection([$obj]);

        $repository = $this->mock('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');
        $repository->shouldReceive('getEventSummarySet')->andReturn($collection);

        $this->call('GET', '/chart/piggy-history/' . $piggyBank->id);
        $this->assertResponseOk();
    }

}

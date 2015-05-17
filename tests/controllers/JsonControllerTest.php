<?php

use Carbon\Carbon;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class JsonControllerTest
 */
class JsonControllerTest extends TestCase
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

    public function testBoxBillsPaid()
    {
        $bill       = FactoryMuffin::create('FireflyIII\Models\Bill');
        $creditCard = FactoryMuffin::create('FireflyIII\Models\Account');
        $ccs        = new Collection([$creditCard]);
        $collection = new Collection([$bill]);
        $ranges     = [['start' => new Carbon, 'end' => new Carbon]];
        $this->be($bill->user);

        $bills    = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $accounts = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');

        // mock!
        $bills->shouldReceive('getActiveBills')->andReturn($collection);
        $bills->shouldReceive('getRanges')->andReturn($ranges);
        $bills->shouldReceive('getJournalsInRange')->andReturn(new Collection);
        $bills->shouldReceive('billPaymentsInRange')->andReturn(12);
        $accounts->shouldReceive('getCreditCards')->andReturn($ccs);
        $accounts->shouldReceive('getTransfersInRange')->andReturn(new Collection);
        Amount::shouldReceive('format')->andReturn('xx');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');
        Steam::shouldReceive('balance')->andReturn(0);


        $this->call('GET', '/json/box/bills-paid');
        $this->assertResponseOk();

    }

    public function testBoxBillsUnpaid()
    {
        $bill       = FactoryMuffin::create('FireflyIII\Models\Bill');
        $creditCard         = FactoryMuffin::create('FireflyIII\Models\Account');
        $ccs        = new Collection([$creditCard]);
        $collection = new Collection([$bill]);
        $ranges     = [['start' => new Carbon, 'end' => new Carbon]];
        $this->be($bill->user);

        $bills    = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $accounts = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');

        // mock!
        $bills->shouldReceive('getActiveBills')->andReturn($collection);
        $bills->shouldReceive('getRanges')->andReturn($ranges);
        $bills->shouldReceive('getJournalsInRange')->andReturn(new Collection);
        $bills->shouldReceive('createFakeBill')->andReturn($bill);
        $accounts->shouldReceive('getCreditCards')->andReturn($ccs);
        Amount::shouldReceive('format')->andReturn('xx');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');
        Steam::shouldReceive('balance')->andReturn(-1);

        $this->call('GET', '/json/box/bills-unpaid');
        $this->assertResponseOk();
    }

    public function testBoxIn()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $repository = $this->mock('FireflyIII\Helpers\Report\ReportQueryInterface');
        $repository->shouldReceive('incomeInPeriod')->andReturn(new Collection);
        Amount::shouldReceive('format')->andReturn('xx');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');

        $this->call('GET', '/json/box/in');
        $this->assertResponseOk();
    }

    public function testBoxOut()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $repository = $this->mock('FireflyIII\Helpers\Report\ReportQueryInterface');
        $repository->shouldReceive('expenseInPeriod')->andReturn(new Collection);
        Amount::shouldReceive('format')->andReturn('xx');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');

        $this->call('GET', '/json/box/out');
        $this->assertResponseOk();
    }

    public function testCategories()
    {
        $category = FactoryMuffin::create('FireflyIII\Models\Category');
        $this->be($category->user);
        $categories = new Collection([$category]);

        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');
        $repository->shouldReceive('getCategories')->andReturn($categories);

        $this->call('GET', '/json/categories');
        $this->assertResponseOk();
    }

    public function testExpenseAccounts()
    {
        $account = FactoryMuffin::create('FireflyIII\Models\Account');
        $this->be($account->user);
        $accounts = new Collection([$account]);

        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('getAccounts')->with(['Expense account', 'Beneficiary account'])->andReturn($accounts);

        $this->call('GET', '/json/expense-accounts');
        $this->assertResponseOk();
    }

    public function testRevenueAccounts()
    {
        $account = FactoryMuffin::create('FireflyIII\Models\Account');
        $this->be($account->user);
        $accounts = new Collection([$account]);

        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('getAccounts')->with(['Revenue account'])->andReturn($accounts);

        $this->call('GET', '/json/revenue-accounts');
        $this->assertResponseOk();
    }

    public function testTags()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $tag = FactoryMuffin::create('FireflyIII\Models\Tag');
        $tag->user()->associate($user);

        $tag->save();
        $this->be($tag->user);
        $tags = new Collection([$tag]);

        $repository = $this->mock('FireflyIII\Repositories\Tag\TagRepositoryInterface');
        $repository->shouldReceive('get')->andReturn($tags);

        $this->call('GET', '/json/tags');
        $this->assertResponseOk();
    }

    public function testTransactionJournals()
    {
        $journal    = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $type       = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $collection = new Collection([$journal]);
        $user       = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $repository = $this->mock('FireflyIII\Repositories\Journal\JournalRepositoryInterface');
        $repository->shouldReceive('getTransactionType')->with('withdrawal')->andReturn($type);
        $repository->shouldReceive('getJournalsOfType')->with($type)->andReturn($collection);


        $this->call('GET', '/json/transaction-journals/withdrawal');
        $this->assertResponseOk();
    }

}

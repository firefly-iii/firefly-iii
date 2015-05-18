<?php
use FireflyIII\Models\AccountMeta;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ReportControllerTest
 */
class ReportControllerTest extends TestCase
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

    public function testIndex()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // mock stuff
        $helper     = $this->mock('FireflyIII\Helpers\Report\ReportHelperInterface');
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $account    = FactoryMuffin::create('FireflyIII\Models\Account');

        // make shared:
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'accountRole',
                'data'       => 'sharedAsset'
            ]
        );

        $helper->shouldReceive('listOfMonths')->andReturn([]);
        $helper->shouldReceive('listOfYears')->andReturn([]);
        $repository->shouldReceive('getAccounts')->andReturn(new Collection([$account]));


        $this->call('GET', '/reports');
        $this->assertResponseOk();

    }

    public function testMonth()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        FactoryMuffin::create('FireflyIII\Models\Account');
        $budget1              = FactoryMuffin::create('FireflyIII\Models\Budget');
        $budget1->queryAmount = 12;
        $budget2              = FactoryMuffin::create('FireflyIII\Models\Budget');
        $budget2->queryAmount = 0;
        $this->be($user);

        // mock!
        $helper = $this->mock('FireflyIII\Helpers\Report\ReportHelperInterface');

        // fake!
        $helper->shouldReceive('getAccountReport')->andReturn(new Collection);
        $helper->shouldReceive('getIncomeReport')->andReturn(new Collection);
        $helper->shouldReceive('getExpenseReport')->andReturn(new Collection);
        $helper->shouldReceive('getBudgetReport')->andReturn(new Collection);
        $helper->shouldReceive('getCategoryReport')->andReturn(new Collection);
        $helper->shouldReceive('getBalanceReport')->andReturn(new Collection);
        $helper->shouldReceive('getBillReport')->andReturn(new Collection);


        $this->call('GET', '/reports/2015/1');
        $this->assertResponseOk();
    }

    public function testMonthShared()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        FactoryMuffin::create('FireflyIII\Models\Account');
        $budget1              = FactoryMuffin::create('FireflyIII\Models\Budget');
        $budget1->queryAmount = 12;
        $budget2              = FactoryMuffin::create('FireflyIII\Models\Budget');
        $budget2->queryAmount = 0;
        $this->be($user);

        // mock!
        $helper = $this->mock('FireflyIII\Helpers\Report\ReportHelperInterface');

        // fake!
        $helper->shouldReceive('getAccountReport')->andReturn(new Collection);
        $helper->shouldReceive('getIncomeReport')->andReturn(new Collection);
        $helper->shouldReceive('getExpenseReport')->andReturn(new Collection);
        $helper->shouldReceive('getBudgetReport')->andReturn(new Collection);
        $helper->shouldReceive('getCategoryReport')->andReturn(new Collection);
        $helper->shouldReceive('getBalanceReport')->andReturn(new Collection);
        $helper->shouldReceive('getBillReport')->andReturn(new Collection);

        $this->call('GET', '/reports/2015/1/shared');
        $this->assertResponseOk();
    }

    public function testYear()
    {
        $user     = FactoryMuffin::create('FireflyIII\User');
        $journal  = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $account  = FactoryMuffin::create('FireflyIII\Models\Account');

        // make shared:
        AccountMeta::create(
            [
                'account_id' => $account->id,
                'name'       => 'accountRole',
                'data'       => 'sharedAsset'
            ]
        );
        new Collection([$journal]);

        $this->be($user);

        $helper = $this->mock('FireflyIII\Helpers\Report\ReportHelperInterface');


        $helper->shouldReceive('getAccountReport')->once()->withAnyArgs()->andReturn([]);
        $helper->shouldReceive('getIncomeReport')->once()->withAnyArgs()->andReturn([]);
        $helper->shouldReceive('getExpenseReport')->once()->withAnyArgs()->andReturn([]);

        // mock stuff!
        Amount::shouldReceive('getDefaultCurrency')->once()->andReturn($currency);
        Amount::shouldReceive('getAllCurrencies')->once()->andReturn([$currency]);
        Amount::shouldReceive('getCurrencyCode')->once()->andReturn('X');
        Amount::shouldReceive('getCurrencySymbol')->once()->andReturn('X');
        Amount::shouldReceive('format')->andReturn('X');

        $this->call('GET', '/reports/2015/shared');
        $this->assertResponseOk();
    }


}

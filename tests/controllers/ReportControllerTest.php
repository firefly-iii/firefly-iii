<?php
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
        $helper = $this->mock('FireflyIII\Helpers\Report\ReportHelperInterface');

        $helper->shouldReceive('listOfMonths')->andReturn([]);
        $helper->shouldReceive('listOfYears')->andReturn([]);


        $this->call('GET', '/reports');
        $this->assertResponseOk();

    }

    public function testModalBalancedTransfers()
    {
        $account  = FactoryMuffin::create('FireflyIII\Models\Account');
        $journal  = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $journals = new Collection([$journal]);
        $this->be($account->user);

        $query = $this->mock('FireflyIII\Helpers\Report\ReportQueryInterface');
        $query->shouldReceive('balancedTransactionsList')->withAnyArgs()->andReturn($journals);


        $this->call('GET', '/reports/modal/' . $account->id . '/2015/1/balanced-transfers');
        $this->assertResponseOk();
    }

    public function testModalLeftUnbalanced()
    {
        $account       = FactoryMuffin::create('FireflyIII\Models\Account');
        $journal       = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $secondJournal = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $group         = FactoryMuffin::create('FireflyIII\Models\TransactionGroup');
        $group->transactionjournals()->save($secondJournal);
        $journals = new Collection([$journal, $secondJournal]);
        $this->be($account->user);

        $query = $this->mock('FireflyIII\Helpers\Report\ReportQueryInterface');
        $query->shouldReceive('getTransactionsWithoutBudget')->withAnyArgs()->andReturn($journals);

        $this->call('GET', '/reports/modal/' . $account->id . '/2015/1/left-unbalanced');
        $this->assertResponseOk();

    }

    public function testModalNoBudget()
    {
        $account  = FactoryMuffin::create('FireflyIII\Models\Account');
        $journal  = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $journals = new Collection([$journal]);
        $this->be($account->user);


        $query = $this->mock('FireflyIII\Helpers\Report\ReportQueryInterface');
        $query->shouldReceive('getTransactionsWithoutBudget')->withAnyArgs()->andReturn($journals);

        $this->call('GET', '/reports/modal/' . $account->id . '/2015/1/no-budget');
        $this->assertResponseOk();

    }

    public function testYear()
    {
        $user     = FactoryMuffin::create('FireflyIII\User');
        $journal  = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $journals = new Collection([$journal]);

        $this->be($user);

        $helper = $this->mock('FireflyIII\Helpers\Report\ReportHelperInterface');
        $query  = $this->mock('FireflyIII\Helpers\Report\ReportQueryInterface');

        $helper->shouldReceive('yearBalanceReport')->withAnyArgs()->andReturn([]);
        $query->shouldReceive('getAllAccounts')->withAnyArgs()->andReturn([]);
        $query->shouldReceive('incomeInPeriod')->withAnyArgs()->andReturn([]);
        $query->shouldReceive('journalsByRevenueAccount')->withAnyArgs()->andReturn($journals);
        $query->shouldReceive('journalsByExpenseAccount')->withAnyArgs()->andReturn($journals);

        // mock stuff!
        Amount::shouldReceive('getDefaultCurrency')->once()->andReturn($currency);
        Amount::shouldReceive('getAllCurrencies')->once()->andReturn([$currency]);
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');
        Amount::shouldReceive('getCurrencySymbol')->andReturn('X');
        Amount::shouldReceive('format')->andReturn('X');

        $this->call('GET', '/reports/2015');
        $this->assertResponseOk();
    }


}

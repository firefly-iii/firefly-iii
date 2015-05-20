<?php

use FireflyIII\Helpers\Report\ReportQuery;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Transaction;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * Class ReportQueryTest
 */
class ReportQueryTest extends TestCase
{
    /**
     * @var ReportQuery
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        FactoryMuffin::create('FireflyIII\User');
        $this->object = new ReportQuery;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    public function testBalancedTransactionsList()
    {
        $this->markTestIncomplete();
    }

    public function testBalancedTransactionsSum()
    {
        $this->markTestIncomplete();
    }

    public function testGetAllAccounts()
    {
        $this->markTestIncomplete();
    }

    public function testGetBudgetSummary()
    {
        $this->markTestIncomplete();
    }

    public function testGetTransactionsWithoutBudget()
    {
        $this->markTestIncomplete();
    }

    public function testIncomeInPeriod()
    {
        $this->markTestIncomplete();
    }

    public function testJournalsByBudget()
    {
        $this->markTestIncomplete();
    }

    public function testJournalsByCategory()
    {
        $this->markTestIncomplete();
    }

    public function testJournalsByExpenseAccount()
    {
        $this->markTestIncomplete();
    }

    public function testJournalsByRevenueAccount()
    {
        $this->markTestIncomplete();
    }

    public function testSharedExpenses()
    {
        $this->markTestIncomplete();
    }

    public function testSharedExpensesByCategory()
    {
        $this->markTestIncomplete();
    }

}

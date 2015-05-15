<?php

use FireflyIII\Helpers\Report\ReportQuery;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Transaction;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
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


    public function testAccountList()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // account types:
        FactoryMuffin::create('FireflyIII\Models\AccountType');
        FactoryMuffin::create('FireflyIII\Models\AccountType');
        $type = FactoryMuffin::create('FireflyIII\Models\AccountType');

        // create four accounts:
        for ($i = 0; $i < 4; $i++) {
            $account          = FactoryMuffin::create('FireflyIII\Models\Account');
            $account->user_id = $user->id;
            $account->active  = 1;
            $account->account_type_id = $type->id;
            $account->save();
        }

        $set = $this->object->accountList();
        $this->assertCount(4, $set);

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

    public function testIncomeByPeriod()
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
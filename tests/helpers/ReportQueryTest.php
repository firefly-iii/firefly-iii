<?php

use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportQuery;
use FireflyIII\Models\AccountMeta;
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


    /**
     * @covers FireflyIII\Helpers\Report\ReportQuery::expenseInPeriodCorrected
     * @covers FireflyIII\Helpers\Report\ReportQuery::queryJournalsWithTransactions
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExpenseInPeriodCorrected()
    {
        $start = new Carbon('2015-01-01');
        $end   = new Carbon('2015-02-01');

        $user = FactoryMuffin::create('FireflyIII\User');
        $type = FactoryMuffin::create('FireflyIII\Models\TransactionType');

        $expense = FactoryMuffin::create('FireflyIII\Models\AccountType');
        $asset   = FactoryMuffin::create('FireflyIII\Models\AccountType');

        $date = new Carbon('2015-01-12');

        for ($i = 0; $i < 10; $i++) {
            $journal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
            $journal->date                = $date;
            $journal->user_id             = $user->id;
            $journal->transaction_type_id = $type->id;
            $journal->save();

            // two transactions:
            $account1                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account2                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account1->account_type_id = $asset->id;
            $account1->user_id         = $user->id;
            $account2->account_type_id = $expense->id;
            $account2->user_id         = $user->id;
            $account1->save();
            $account2->save();

            AccountMeta::create(
                [
                    'account_id' => $account1->id,
                    'name'       => 'accountRole',
                    'data'       => 'defaultAsset'
                ]
            );

            // update both transactions
            $journal->transactions[0]->account_id = $account1->id;
            $journal->transactions[0]->amount = -100;
            $journal->transactions[0]->save();

            $journal->transactions[1]->account_id = $account2->id;
            $journal->transactions[1]->amount = 100;
            $journal->transactions[1]->save();


        }
        $this->be($user);


        $set = $this->object->expenseInPeriodCorrected($start, $end, false);


        $this->assertCount(10, $set);
    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportQuery::expenseInPeriodCorrected
     * @covers FireflyIII\Helpers\Report\ReportQuery::queryJournalsWithTransactions
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExpenseInPeriodCorrectedShared()
    {
        $start = new Carbon('2015-01-01');
        $end   = new Carbon('2015-02-01');

        $user = FactoryMuffin::create('FireflyIII\User');
        $type = FactoryMuffin::create('FireflyIII\Models\TransactionType');

        $expense = FactoryMuffin::create('FireflyIII\Models\AccountType');
        $asset   = FactoryMuffin::create('FireflyIII\Models\AccountType');

        $date = new Carbon('2015-01-12');

        for ($i = 0; $i < 10; $i++) {
            $journal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
            $journal->date                = $date;
            $journal->user_id             = $user->id;
            $journal->transaction_type_id = $type->id;
            $journal->save();

            // two transactions:
            $account1                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account2                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account1->account_type_id = $asset->id;
            $account1->user_id         = $user->id;
            $account2->account_type_id = $expense->id;
            $account2->user_id         = $user->id;
            $account1->save();
            $account2->save();

            AccountMeta::create(
                [
                    'account_id' => $account1->id,
                    'name'       => 'accountRole',
                    'data'       => 'defaultAsset'
                ]
            );

            // update both transactions
            $journal->transactions[0]->account_id = $account1->id;
            $journal->transactions[0]->amount = -100;
            $journal->transactions[0]->save();

            $journal->transactions[1]->account_id = $account2->id;
            $journal->transactions[1]->amount = 100;
            $journal->transactions[1]->save();

        }
        $this->be($user);

        $set = $this->object->expenseInPeriodCorrected($start, $end, true);

        $this->assertCount(10, $set);
    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportQuery::getAllAccounts
     */
    public function testGetAllAccounts()
    {
        $start = new Carbon('2015-01-01');
        $end   = new Carbon('2015-02-01');
        $user  = FactoryMuffin::create('FireflyIII\User');
        FactoryMuffin::create('FireflyIII\Models\Account');
        FactoryMuffin::create('FireflyIII\Models\Account');
        $asset = FactoryMuffin::create('FireflyIII\Models\Account');

        for ($i = 0; $i < 10; $i++) {
            $account                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account->account_type_id = $asset->id;
            $account->user_id         = $user->id;
            $account->save();
        }

        Steam::shouldReceive('balance')->andReturn(0);

        $this->be($user);

        $set = $this->object->getAllAccounts($start, $end, false);
        $this->assertCount(10, $set);
    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportQuery::getAllAccounts
     */
    public function testGetAllAccountsShared()
    {
        $start = new Carbon('2015-01-01');
        $end   = new Carbon('2015-02-01');
        $user  = FactoryMuffin::create('FireflyIII\User');
        FactoryMuffin::create('FireflyIII\Models\Account');
        FactoryMuffin::create('FireflyIII\Models\Account');
        $asset = FactoryMuffin::create('FireflyIII\Models\Account');

        for ($i = 0; $i < 10; $i++) {
            $account                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account->account_type_id = $asset->id;
            $account->user_id         = $user->id;
            $account->save();
        }

        Steam::shouldReceive('balance')->andReturn(0);

        $this->be($user);

        $set = $this->object->getAllAccounts($start, $end, true);
        $this->assertCount(10, $set);
    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportQuery::incomeInPeriodCorrected
     * @covers FireflyIII\Helpers\Report\ReportQuery::queryJournalsWithTransactions
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testIncomeInPeriodCorrected()
    {
        $start = new Carbon('2015-01-01');
        $end   = new Carbon('2015-02-01');

        $user = FactoryMuffin::create('FireflyIII\User');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $type = FactoryMuffin::create('FireflyIII\Models\TransactionType');

        $expense = FactoryMuffin::create('FireflyIII\Models\AccountType');
        $asset   = FactoryMuffin::create('FireflyIII\Models\AccountType');

        $date = new Carbon('2015-01-12');

        for ($i = 0; $i < 10; $i++) {
            $journal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
            $journal->date                = $date;
            $journal->user_id             = $user->id;
            $journal->transaction_type_id = $type->id;
            $journal->save();

            // two transactions:
            $account1                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account2                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account1->account_type_id = $asset->id;
            $account1->user_id         = $user->id;
            $account2->account_type_id = $expense->id;
            $account2->user_id         = $user->id;
            $account1->save();
            $account2->save();

            AccountMeta::create(
                [
                    'account_id' => $account1->id,
                    'name'       => 'accountRole',
                    'data'       => 'defaultAsset'
                ]
            );

            // update both transactions
            $journal->transactions[0]->account_id = $account1->id;
            $journal->transactions[0]->amount = 100;
            $journal->transactions[0]->save();

            $journal->transactions[1]->account_id = $account2->id;
            $journal->transactions[1]->amount = -100;
            $journal->transactions[1]->save();

        }
        $this->be($user);

        $set = $this->object->incomeInPeriodCorrected($start, $end, false);

        $this->assertCount(10, $set);
    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportQuery::incomeInPeriodCorrected
     * @covers FireflyIII\Helpers\Report\ReportQuery::queryJournalsWithTransactions
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testIncomeInPeriodCorrectedShared()
    {
        $start = new Carbon('2015-01-01');
        $end   = new Carbon('2015-02-01');

        $user = FactoryMuffin::create('FireflyIII\User');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $type = FactoryMuffin::create('FireflyIII\Models\TransactionType');

        $expense = FactoryMuffin::create('FireflyIII\Models\AccountType');
        $asset   = FactoryMuffin::create('FireflyIII\Models\AccountType');

        $date = new Carbon('2015-01-12');

        for ($i = 0; $i < 10; $i++) {
            $journal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
            $journal->date                = $date;
            $journal->user_id             = $user->id;
            $journal->transaction_type_id = $type->id;
            $journal->save();

            // two transactions:
            $account1                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account2                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account1->account_type_id = $asset->id;
            $account1->user_id         = $user->id;
            $account2->account_type_id = $expense->id;
            $account2->user_id         = $user->id;
            $account1->save();
            $account2->save();

            AccountMeta::create(
                [
                    'account_id' => $account1->id,
                    'name'       => 'accountRole',
                    'data'       => 'defaultAsset'
                ]
            );

            // update both transactions
            $journal->transactions[0]->account_id = $account1->id;
            $journal->transactions[0]->amount = -100;
            $journal->transactions[0]->save();

            $journal->transactions[1]->account_id = $account2->id;
            $journal->transactions[1]->amount = 100;
            $journal->transactions[1]->save();

        }
        $this->be($user);

        $set = $this->object->incomeInPeriodCorrected($start, $end, true);

        $this->assertCount(10, $set);
    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportQuery::spentInBudgetCorrected
     */
    public function testSpentInBudgetCorrected()
    {
        $user             = FactoryMuffin::create('FireflyIII\User');
        $account          = FactoryMuffin::create('FireflyIII\Models\Account');
        $account->user_id = $user->id;
        $budget           = FactoryMuffin::create('FireflyIII\Models\Budget');
        $budget->user_id  = $user->id;

        $account->save();
        $budget->save();

        $this->be($user);

        $result = $this->object->spentInBudgetCorrected($account, $budget, new Carbon, new Carbon);
        $this->assertEquals(0, $result);

    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportQuery::spentNoBudget
     */
    public function testSpentNoBudget()
    {

        $user             = FactoryMuffin::create('FireflyIII\User');
        $account          = FactoryMuffin::create('FireflyIII\Models\Account');
        $account->user_id = $user->id;

        $account->save();

        $this->be($user);

        $result = $this->object->spentNoBudget($account, new Carbon, new Carbon);
        $this->assertEquals(0, $result);
    }

}

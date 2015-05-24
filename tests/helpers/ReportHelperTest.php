<?php

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Account as AccountCollection;
use FireflyIII\Helpers\Report\ReportHelper;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Transaction;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ReportHelperTest
 */
class ReportHelperTest extends TestCase
{
    /**
     * @var ReportHelper
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
        $query        = new \FireflyIII\Helpers\Report\ReportQuery();
        $this->object = new ReportHelper($query);
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
     * @covers FireflyIII\Helpers\Report\ReportHelper::getAccountReport
     */
    public function testGetAccountReport()
    {
        FactoryMuffin::create('FireflyIII\Models\AccountType');
        FactoryMuffin::create('FireflyIII\Models\AccountType');
        $asset = FactoryMuffin::create('FireflyIII\Models\AccountType');
        $user  = FactoryMuffin::create('FireflyIII\User');
        for ($i = 0; $i < 5; $i++) {
            $account                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account->user_id         = $user->id;
            $account->account_type_id = $asset->id;
            $account->save();
        }
        $this->be($user);
        /** @var AccountCollection $object */
        $object = $this->object->getAccountReport(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), false);

        $this->assertCount(5, $object->getAccounts());
        $this->assertEquals(0, $object->getDifference());
        $this->assertEquals(0, $object->getEnd());
        $this->assertEquals(0, $object->getStart());
    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportHelper::getBalanceReport
     */
    public function testGetBalanceReport()
    {
        // factory!
        FactoryMuffin::create('FireflyIII\Models\AccountType');
        FactoryMuffin::create('FireflyIII\Models\AccountType');
        $asset = FactoryMuffin::create('FireflyIII\Models\AccountType');
        $user  = FactoryMuffin::create('FireflyIII\User');
        $rep   = FactoryMuffin::create('FireflyIII\Models\LimitRepetition');
        for ($i = 0; $i < 5; $i++) {
            $account                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account->user_id         = $user->id;
            $account->account_type_id = $asset->id;
            $account->save();
        }

        $set = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $set->push(FactoryMuffin::create('FireflyIII\Models\Budget'));
        }

        $this->be($user);

        // mock!
        $budgetRepos = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $tagRepos    = $this->mock('FireflyIII\Repositories\Tag\TagRepositoryInterface');

        // fake!
        $budgetRepos->shouldReceive('getBudgets')->andReturn($set);
        $budgetRepos->shouldReceive('getCurrentRepetition')->andReturn($rep);
        $tagRepos->shouldReceive('coveredByBalancingActs')->andReturn(0);

        // test!
        $object = $this->object->getBalanceReport(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), false);
        $this->assertCount(8, $object->getBalanceLines());
        $this->assertCount(5, $object->getBalanceHeader()->getAccounts());
    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportHelper::getBillReport
     */
    public function testGetBillReport()
    {
        // factory!
        $set      = new Collection;
        $journals = new Collection;
        $left     = FactoryMuffin::create('FireflyIII\Models\Account');
        $right    = FactoryMuffin::create('FireflyIII\Models\Account');
        for ($i = 0; $i < 5; $i++) {
            $set->push(FactoryMuffin::create('FireflyIII\Models\Bill'));
        }

        for ($i = 0; $i < 5; $i++) {
            $journal = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
            Transaction::create(
                [
                    'account_id'             => $left->id,
                    'transaction_journal_id' => $journal->id,
                    'amount'                 => rand(-100, 100)
                ]
            );
            Transaction::create(
                [
                    'account_id'             => $right->id,
                    'transaction_journal_id' => $journal->id,
                    'amount'                 => rand(-100, 100)
                ]
            );
            $journals->push($journal);
        }


        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');

        // fake!
        $repository->shouldReceive('getBills')->andReturn($set);
        $repository->shouldReceive('getJournalsInRange')->withAnyArgs()->andReturn(new Collection, $journals);

        // test!
        $object = $this->object->getBillReport(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), false);
        $this->assertCount(5, $object->getBills());
    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportHelper::getBudgetReport
     */
    public function testGetBudgetReport()
    {
        // factory!
        $user = FactoryMuffin::create('FireflyIII\User');
        $set  = new Collection;
        $rep1 = new Collection;
        $rep2 = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $set->push(FactoryMuffin::create('FireflyIII\Models\Budget'));
        }
        for ($i = 0; $i < 5; $i++) {
            $rep1->push(FactoryMuffin::create('FireflyIII\Models\LimitRepetition'));
        }

        $this->be($user);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');

        // fake!
        $repository->shouldReceive('getBudgets')->andReturn($set);
        $repository->shouldReceive('getBudgetLimitRepetitions')->andReturn($rep1, $rep2);
        $repository->shouldReceive('spentInPeriodCorrected')->andReturn(rand(0, 100));
        $repository->shouldReceive('getWithoutBudgetSum')->andReturn(rand(0, 100));

        // test!
        $object = $this->object->getBudgetReport(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), false);

        $this->assertCount(10, $object->getBudgetLines());
    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportHelper::getCategoryReport
     */
    public function testGetCategoryReport()
    {
        // factory!
        $user = FactoryMuffin::create('FireflyIII\User');
        $set  = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $set->push(FactoryMuffin::create('FireflyIII\Models\Category'));
        }

        $this->be($user);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Category\CategoryRepositoryInterface');

        // fake!
        $repository->shouldReceive('getCategories')->andReturn($set);
        $repository->shouldReceive('spentInPeriodCorrected')->andReturn(rand(0, 100));

        // test!
        $object = $this->object->getCategoryReport(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), false);
        $this->assertCount(5, $object->getCategories());
    }


    /**
     * @covers FireflyIII\Helpers\Report\ReportHelper::getExpenseReport
     */
    public function testGetExpenseReport()
    {
        // factory!
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $type = FactoryMuffin::create('FireflyIII\Models\TransactionType');

        // create five journals in this month for the report:
        $date                   = Carbon::now()->startOfMonth()->addDay();
        $asset                  = FactoryMuffin::create('FireflyIII\Models\AccountType');
        $left                   = FactoryMuffin::create('FireflyIII\Models\Account');
        $right                  = FactoryMuffin::create('FireflyIII\Models\Account');
        $left->account_type_id  = $asset->id;
        $right->account_type_id = $asset->id;
        $right->save();
        $left->save();

        // save meta for account:
        AccountMeta::create([
                                'account_id' => $left->id,
                                'name' => 'accountRole',
                                'data' => 'defaultAsset'
                            ]);
        AccountMeta::create([
                                'account_id' => $right->id,
                                'name' => 'accountRole',
                                'data' => 'defaultAsset'
                            ]);


        for ($i = 0; $i < 5; $i++) {
            $journal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
            $journal->date                = $date;
            $journal->transaction_type_id = $type->id;
            $journal->user_id             = $user->id;
            $journal->save();
            Transaction::create(
                [
                    'account_id'             => $left->id,
                    'transaction_journal_id' => $journal->id,
                    'amount'                 => 100
                ]
            );
            Transaction::create(
                [
                    'account_id'             => $right->id,
                    'transaction_journal_id' => $journal->id,
                    'amount'                 => -100
                ]
            );
        }

        // test!
        $object = $this->object->getExpenseReport(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), true);
        $this->assertCount(1, $object->getExpenses());
    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportHelper::getIncomeReport
     */
    public function testGetIncomeReport()
    {
        // factory!
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $type = FactoryMuffin::create('FireflyIII\Models\TransactionType');

        // create five journals in this month for the report:
        $date                   = Carbon::now()->startOfMonth()->addDay();
        $left                   = FactoryMuffin::create('FireflyIII\Models\Account');
        $right                  = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset                  = FactoryMuffin::create('FireflyIII\Models\AccountType');
        $left->account_type_id  = $asset->id;
        $right->account_type_id = $asset->id;

        // save meta for account:
        AccountMeta::create([
            'account_id' => $left->id,
            'name' => 'accountRole',
            'data' => 'defaultAsset'
                            ]);
        AccountMeta::create([
                                'account_id' => $right->id,
                                'name' => 'accountRole',
                                'data' => 'defaultAsset'
                            ]);

        $right->save();
        $left->save();
        for ($i = 0; $i < 5; $i++) {
            $journal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
            $journal->date                = $date;
            $journal->transaction_type_id = $type->id;
            $journal->user_id             = $user->id;
            $journal->save();
            Transaction::create(
                [
                    'account_id'             => $left->id,
                    'transaction_journal_id' => $journal->id,
                    'amount'                 => 100
                ]
            );
            Transaction::create(
                [
                    'account_id'             => $right->id,
                    'transaction_journal_id' => $journal->id,
                    'amount'                 => -100
                ]
            );
        }

        // test!
        $object = $this->object->getIncomeReport(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), true);
        $this->assertCount(1, $object->getIncomes());

    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportHelper::listOfMonths
     */
    public function testListOfMonths()
    {
        // start of year up until now
        $date   = new Carbon('2015-01-01');
        $now    = new Carbon;
        $diff   = $now->diffInMonths($date) + 1; // the month itself.
        $result = $this->object->listOfMonths($date);

        $this->assertCount($diff, $result[2015]);

    }

}

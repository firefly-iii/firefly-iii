<?php

use Carbon\Carbon;
use FireflyIII\Helpers\Report\ReportHelper;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Transaction;
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
        $this->object = new ReportHelper;
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
     * @covers FireflyIII\Helpers\Report\ReportHelper::getBudgetsForMonth
     * @covers FireflyIII\Helpers\Report\ReportQuery::journalsByBudget
     * @covers FireflyIII\Helpers\Report\ReportQuery::sharedExpenses
     */
    public function testGetBudgetsForMonthWithShared()
    {
        $date    = new Carbon('2015-01-01');
        $user    = FactoryMuffin::create('FireflyIII\User');
        $budgets = [];

        // three budget limits starting on the $date:
        for ($i = 0; $i < 3; $i++) {
            $budget                 = FactoryMuffin::create('FireflyIII\Models\Budget');
            $budgetLimit            = FactoryMuffin::create('FireflyIII\Models\BudgetLimit');
            $budgetLimit->startdate = $date;
            $budget->user_id        = $user->id;
            $budget->save();
            $budgetLimit->save();
            $budgets[] = $budget;
        }

        $this->be($user);

        $result = $this->object->getBudgetsForMonth($date, true);

        // assert each budget is in the array:
        foreach ($budgets as $budget) {
            $id = $budget->id;
            $this->assertEquals($budget->name, $result[$id]['name']);
        }
        $this->assertEquals(0, $result[0]['queryAmount']);
        $this->assertEquals('No budget', $result[0]['name']);
    }

    /**
     * @covers FireflyIII\Helpers\Report\ReportHelper::getBudgetsForMonth
     * @covers FireflyIII\Helpers\Report\ReportQuery::journalsByBudget
     */
    public function testGetBudgetsForMonthWithoutShared()
    {
        $date    = new Carbon('2015-01-01');
        $user    = FactoryMuffin::create('FireflyIII\User');
        $budgets = [];

        // three budget limits starting on the $date:
        for ($i = 0; $i < 3; $i++) {
            $budget                 = FactoryMuffin::create('FireflyIII\Models\Budget');
            $budgetLimit            = FactoryMuffin::create('FireflyIII\Models\BudgetLimit');
            $budgetLimit->startdate = $date;
            $budget->user_id        = $user->id;
            $budget->save();
            $budgetLimit->save();
            $budgets[] = $budget;
        }

        $this->be($user);

        $result = $this->object->getBudgetsForMonth($date, false);

        // assert each budget is in the array:
        foreach ($budgets as $budget) {
            $id = $budget->id;
            $this->assertEquals($budget->name, $result[$id]['name']);
        }
        $this->assertEquals(0, $result[0]['queryAmount']);
        $this->assertEquals('No budget', $result[0]['name']);
    }

    public function testListOfMonths()
    {
        // start of year up until now
        $date   = new Carbon('2015-01-01');
        $now    = new Carbon;
        $diff   = $now->diffInMonths($date) + 1; // the month itself.
        $result = $this->object->listOfMonths($date);

        $this->assertCount($diff, $result[2015]);

    }

    public function testListOfYears()
    {

        $date   = new Carbon('2015-01-01');
        $now    = new Carbon;
        $diff   = $now->diffInYears($date) + 1; // the year itself.
        $result = $this->object->listOfYears($date);
        $this->assertCount($diff, $result);
    }

    public function testYearBalanceReport()
    {
        $date      = new Carbon('2015-01-01');
        $user      = FactoryMuffin::create('FireflyIII\User');
        $setShared = [];
        $setNormal = [];

        FactoryMuffin::create('FireflyIII\Models\AccountType');
        FactoryMuffin::create('FireflyIII\Models\AccountType');
        $assetType = FactoryMuffin::create('FireflyIII\Models\AccountType');

        // need some shared accounts:
        for ($i = 0; $i < 3; $i++) {
            $shared                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $shared->user_id         = $user->id;
            $shared->account_type_id = $assetType->id;
            $shared->save();
            // meta for shared:
            AccountMeta::create(
                [
                    'account_id' => $shared->id,
                    'name'       => 'accountRole',
                    'data'       => 'sharedAsset',
                ]
            );
            $setShared[] = $shared;
        }

        // need some normal accounts:
        for ($i = 0; $i < 3; $i++) {
            $account                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account->user_id         = $user->id;
            $account->account_type_id = $assetType->id;
            $account->save();
            $setNormal[] = $account;
        }

        // mock stuff:
        Steam::shouldReceive('balance')->withAnyArgs()->andReturn(0);

        $this->be($user);

        $result = $this->object->yearBalanceReport($date, false);
        foreach ($result as $entry) {
            // everything is hidden:
            $this->assertTrue($entry['hide']);
            // nothing is shared:
            $this->assertFalse($entry['shared']);
        }

    }

    public function testYearBalanceReportWithShared()
    {
        $date      = new Carbon('2015-01-01');
        $user      = FactoryMuffin::create('FireflyIII\User');
        $setShared = [];
        $setNormal = [];

        FactoryMuffin::create('FireflyIII\Models\AccountType');
        FactoryMuffin::create('FireflyIII\Models\AccountType');
        $assetType = FactoryMuffin::create('FireflyIII\Models\AccountType');

        // need some shared accounts:
        for ($i = 0; $i < 3; $i++) {
            $shared                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $shared->user_id         = $user->id;
            $shared->account_type_id = $assetType->id;
            $shared->save();
            // meta for shared:
            AccountMeta::create(
                [
                    'account_id' => $shared->id,
                    'name'       => 'accountRole',
                    'data'       => 'sharedAsset',
                ]
            );
            $setShared[] = $shared;
        }

        // need some normal accounts:
        for ($i = 0; $i < 3; $i++) {
            $account                  = FactoryMuffin::create('FireflyIII\Models\Account');
            $account->user_id         = $user->id;
            $account->account_type_id = $assetType->id;
            $account->save();
            $setNormal[] = $account;
        }

        // mock stuff:
        Steam::shouldReceive('balance')->withAnyArgs()->andReturn(0);

        $this->be($user);

        $result = $this->object->yearBalanceReport($date, true);
        foreach ($result as $entry) {
            // everything is hidden:
            $this->assertTrue($entry['hide']);
            // nothing is shared:
            $this->assertFalse($entry['shared']);
        }

    }
}
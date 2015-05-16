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

}
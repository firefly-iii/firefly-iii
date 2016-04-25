<?php
declare(strict_types = 1);
/**
 * TestDataSeeder.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Carbon\Carbon;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Support\Migration\TestData;
use Illuminate\Database\Seeder;

/**
 * Class TestDataSeeder
 */
class TestDataSeeder extends Seeder
{
    /** @var  Carbon */
    public $end;
    /** @var  Carbon */
    public $start;

    /**
     * TestDataSeeder constructor.
     */
    public function __construct()
    {
        $this->start = Carbon::create()->subYears(2)->startOfYear();
        $this->end   = Carbon::now();

    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // start by creating all users:
        // method will return the first user.
        $user = TestData::createUsers();

        // create all kinds of static data:
        TestData::createAssetAccounts($user);
        TestData::createBills($user);
        TestData::createBudgets($user);
        TestData::createCategories($user);
        TestData::createPiggybanks($user);
        TestData::createExpenseAccounts($user);
        TestData::createRevenueAccounts($user);
        TestData::createAttachments($user, $this->start);
        TestData::openingBalanceSavings($user, $this->start);
        TestData::createRules($user);

        // loop from start to end, create dynamic info.
        $current = clone $this->start;
        while ($current < $this->end) {
            $month = $current->format('F Y');
            // create salaries:
            TestData::createIncome($user, 'Salary ' . $month, $current, strval(rand(2000, 2100)));

            // pay bills:
            TestData::createRent($user, 'Rent for ' . $month, $current, '800');
            TestData::createWater($user, 'Water bill for ' . $month, $current, '15');
            TestData::createTV($user, 'TV bill for ' . $month, $current, '60');
            TestData::createPower($user, 'Power bill for ' . $month, $current, '120');

            // pay daily groceries:
            TestData::createGroceries($user, $current);

            // create tag (each type of tag, for date):
            TestData::createTags($user, $current);

            // go out for drinks:
            TestData::createDrinksAndOthers($user, $current);

            // save money every month:
            TestData::createSavings($user, $current);

            // buy gas for the car every month:
            TestData::createCar($user, $current);

            // create budget limits.
            TestData::createBudgetLimit($user, $current, 'Groceries', '400');
            TestData::createBudgetLimit($user, $current, 'Bills', '1000');
            TestData::createBudgetLimit($user, $current, 'Car', '100');

            $current->addMonth();
        }

        // create some special budget limits to test stuff with multiple budget limits
        // for a range of dates:
        $this->end->startOfMonth();
        $budget = TestData::findBudget($user, 'Bills');
        $ranges = ['daily','weekly','monthly','quarterly','half-year','yearly'];
        foreach($ranges as $range) {
            BudgetLimit::create(
                [
                    'budget_id'   => $budget->id,
                    'startdate'   => $this->end->format('Y-m-d'),
                    'amount'      => rand(100,200),
                    'repeats'     => 0,
                    'repeat_freq' => $range,
                ]
            );
            $this->end->addDay();
        }

        // b
    }
}

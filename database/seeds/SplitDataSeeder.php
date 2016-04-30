<?php
/**
 * SplitDataSeeder.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
/**
 * SplitDataSeeder.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use FireflyIII\Support\Migration\TestData;
use Illuminate\Database\Seeder;

/**
 * Class SplitDataSeeder
 */
class SplitDataSeeder extends Seeder
{
    /**
     * TestDataSeeder constructor.
     */
    public function __construct()
    {


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
        TestData::createBudgets($user);
        TestData::createCategories($user);
        TestData::createExpenseAccounts($user);
        TestData::createRevenueAccounts($user);
    }
}

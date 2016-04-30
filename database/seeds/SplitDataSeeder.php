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

use Carbon\Carbon;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
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
        TestData::createPiggybanks($user);

        /*
         * Create splitted expense of 66,-
         */
        $today = new Carbon;
        $today->subDays(6);

        $journal = TransactionJournal::create(
            [
                'user_id'                 => $user->id,
                'transaction_type_id'     => 1, // withdrawal
                'transaction_currency_id' => 1,
                'description'             => 'Split Expense (journal)',
                'completed'               => 1,
                'date'                    => $today->format('Y-m-d'),
            ]
        );

        // split in 6 transactions (multiple destinations). 22,- each
        // source is TestData Checking Account.
        // also attach some budgets and stuff.
        $destinations = ['Albert Heijn', 'PLUS', 'Apple'];
        $budgets      = ['Groceries', 'Groceries', 'Car'];
        $categories   = ['Bills', 'Bills', 'Car'];
        $source       = TestData::findAccount($user, 'TestData Checking Account');
        foreach ($destinations as $index => $dest) {
            $bud         = $budgets[$index];
            $cat         = $categories[$index];
            $destination = TestData::findAccount($user, $dest);

            $one = Transaction::create(
                [
                    'account_id'             => $source->id,
                    'transaction_journal_id' => $journal->id,
                    'amount'                 => '-22',

                ]
            );

            $two = Transaction::create(
                [
                    'account_id'             => $destination->id,
                    'transaction_journal_id' => $journal->id,
                    'amount'                 => '22',

                ]
            );

            $one->budgets()->save(TestData::findBudget($user, $bud));
            $two->budgets()->save(TestData::findBudget($user, $bud));

            $one->categories()->save(TestData::findCategory($user, $cat));
            $two->categories()->save(TestData::findCategory($user, $cat));
        }

        // create splitted income of 99,-
        $today->addDay();

        $journal = TransactionJournal::create(
            [
                'user_id'                 => $user->id,
                'transaction_type_id'     => 2, // expense
                'transaction_currency_id' => 1,
                'description'             => 'Split Income (journal)',
                'completed'               => 1,
                'date'                    => $today->format('Y-m-d'),
            ]
        );

        // split in 6 transactions (multiple destinations). 22,- each
        // source is TestData Checking Account.
        // also attach some budgets and stuff.
        $destinations = ['TestData Checking Account', 'TestData Savings', 'TestData Shared'];
        $source       = TestData::findAccount($user, 'Belastingdienst');
        $budgets      = ['Groceries', 'Groceries', 'Car'];
        $categories   = ['Bills', 'Bills', 'Car'];
        foreach ($destinations as $index => $dest) {
            $bud         = $budgets[$index];
            $cat         = $categories[$index];
            $destination = TestData::findAccount($user, $dest);

            $one = Transaction::create(
                [
                    'account_id'             => $source->id,
                    'transaction_journal_id' => $journal->id,
                    'amount'                 => '-33',

                ]
            );

            $two = Transaction::create(
                [
                    'account_id'             => $destination->id,
                    'transaction_journal_id' => $journal->id,
                    'amount'                 => '33',

                ]
            );

            $one->budgets()->save(TestData::findBudget($user, $bud));
            $two->budgets()->save(TestData::findBudget($user, $bud));

            $one->categories()->save(TestData::findCategory($user, $cat));
            $two->categories()->save(TestData::findCategory($user, $cat));
        }

        // create a splitted transfer of 57,- (19)
        $today->addDay();

        $journal = TransactionJournal::create(
            [
                'user_id'                 => $user->id,
                'transaction_type_id'     => 3, // transfer
                'transaction_currency_id' => 1,
                'description'             => 'Split Transfer (journal)',
                'completed'               => 1,
                'date'                    => $today->format('Y-m-d'),
            ]
        );


        $source       = TestData::findAccount($user, 'Emergencies');
        $destinations = ['TestData Checking Account', 'TestData Savings', 'TestData Shared'];
        $budgets      = ['Groceries', 'Groceries', 'Car'];
        $categories   = ['Bills', 'Bills', 'Car'];
        foreach ($destinations as $index => $dest) {
            $bud         = $budgets[$index];
            $cat         = $categories[$index];
            $destination = TestData::findAccount($user, $dest);

            $one = Transaction::create(
                [
                    'account_id'             => $source->id,
                    'transaction_journal_id' => $journal->id,
                    'amount'                 => '-19',

                ]
            );

            $two = Transaction::create(
                [
                    'account_id'             => $destination->id,
                    'transaction_journal_id' => $journal->id,
                    'amount'                 => '19',

                ]
            );

            $one->budgets()->save(TestData::findBudget($user, $bud));
            $two->budgets()->save(TestData::findBudget($user, $bud));

            $one->categories()->save(TestData::findCategory($user, $cat));
            $two->categories()->save(TestData::findCategory($user, $cat));
        }


    }
}

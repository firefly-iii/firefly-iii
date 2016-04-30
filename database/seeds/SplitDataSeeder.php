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
        $skipWithdrawal = false;
        $skipDeposit    = true;
        $skipTransfer   = true;
        // start by creating all users:
        // method will return the first user.
        $user = TestData::createUsers();

        // create all kinds of static data:
        $assets = [
            [
                'name' => 'Checking Account',
                'iban' => 'NL11XOLA6707795988',
                'meta' => [
                    'accountRole' => 'defaultAsset',
                ],
            ],
            [
                'name' => 'Alternate Checking Account',
                'iban' => 'NL40UKBK3619908726',
                'meta' => [
                    'accountRole' => 'defaultAsset',
                ],
            ],
            [
                'name' => 'Savings Account',
                'iban' => 'NL96DZCO4665940223',
                'meta' => [
                    'accountRole' => 'savingAsset',
                ],
            ],
            [
                'name' => 'Shared Checking Account',
                'iban' => 'NL81RCQZ7160379858',
                'meta' => [
                    'accountRole' => 'sharedAsset',
                ],
            ],
        ];
        TestData::createAssetAccounts($user, $assets);
        TestData::createBudgets($user);
        TestData::createCategories($user);
        TestData::createExpenseAccounts($user);
        TestData::createRevenueAccounts($user);
        TestData::createPiggybanks($user, 'Savings Account');

        /*
         * Create splitted expense of 66,-
         */
        $today = new Carbon;
        $today->subDays(6);

        if (!$skipWithdrawal) {
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
            $source       = TestData::findAccount($user, 'Checking Account');
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
        }
        // create splitted income of 99,-
        $today->addDay();

        if (!$skipDeposit) {
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
            $destinations = ['Checking Account', 'Savings Account', 'Shared Checking Account'];
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
        }
        // create a splitted transfer of 57,- (19)
        $today->addDay();

        if (!$skipTransfer) {
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


            $source       = TestData::findAccount($user, 'Alternate Checking Account');
            $destinations = ['Checking Account', 'Savings Account', 'Shared Checking Account'];
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
}

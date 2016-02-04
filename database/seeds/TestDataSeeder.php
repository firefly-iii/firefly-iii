<?php

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Category;
use FireflyIII\Models\Role;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Migration\TestData;
use FireflyIII\User;
use Illuminate\Database\Seeder;

/**
 * Class TestDataSeeder
 */
class TestDataSeeder extends Seeder
{
    /** @var  Carbon */
    public $start;

    /**
     * TestDataSeeder constructor.
     */
    public function __construct()
    {
        $this->start = Carbon::create()->subYear()->startOfYear();

    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create(['email' => 'thegrumpydictator@gmail.com', 'password' => bcrypt('james'), 'reset' => null, 'remember_token' => null]);
        User::create(['email' => 'thegrumpydictator+empty@gmail.com', 'password' => bcrypt('james'), 'reset' => null, 'remember_token' => null]);
        User::create(['email' => 'thegrumpydictator+deleteme@gmail.com', 'password' => bcrypt('james'), 'reset' => null, 'remember_token' => null]);


        $admin = Role::where('name', 'owner')->first();
        $user->attachRole($admin);


        // create asset accounts for user #1.
        TestData::createAssetAccounts($user);

        // create bills for user #1
        TestData::createBills($user);

        // create some budgets for user #1
        TestData::createBudgets($user);

        // create budget limits for these budgets
        TestData::createBudgetLimit($user, new Carbon, 'Groceries', 400);
        TestData::createBudgetLimit($user, new Carbon, 'Bills', 1000);
        TestData::createBudgetLimit($user, new Carbon, 'Car', 100);

        // create some categories for user #1
        $this->createCategories($user);

        // create some piggy banks for user #1
        TestData::createPiggybanks($user);

        // create some expense accounts for user #1
        $this->createExpenseAccounts($user);

        // create some revenue accounts for user #1
        $this->createRevenueAccounts($user);

        // create journal + attachment:
        $this->createAttachments($user);

        // create opening balance for savings account:
        $this->openingBalanceSavings($user);

        // need at least one rule group and one rule:
        TestData::createRules($user);

        // create a tag:
        TestData::createTags($user);
    }

    /**
     * @param User $user
     */
    private function createAttachments(User $user)
    {

        $toAccount   = TestData::findAccount($user, 'TestData Checking Account');
        $fromAccount = TestData::findAccount($user, 'Job');

        $journal = TransactionJournal::create(
            [
                'user_id'                 => $user->id,
                'transaction_type_id'     => 2,
                'transaction_currency_id' => 1,
                'description'             => 'Some journal for attachment',
                'completed'               => 1,
                'date'                    => $this->start->format('Y-m-d'),
            ]
        );
        Transaction::create(
            [
                'account_id'             => $fromAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => -100,

            ]
        );
        Transaction::create(
            [
                'account_id'             => $toAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => 100,

            ]
        );

        // and now attachments
        $encrypted = Crypt::encrypt('I are secret');
        Attachment::create(
            [
                'attachable_id'   => $journal->id,
                'attachable_type' => 'FireflyIII\Models\TransactionJournal',
                'user_id'         => $user->id,
                'md5'             => md5('Hallo'),
                'filename'        => 'empty-file.txt',
                'title'           => 'Empty file',
                'description'     => 'This file is empty',
                'notes'           => 'What notes',
                'mime'            => 'text/plain',
                'size'            => strlen($encrypted),
                'uploaded'        => 1,
            ]
        );


        // and now attachment.
        Attachment::create(
            [
                'attachable_id'   => $journal->id,
                'attachable_type' => 'FireflyIII\Models\TransactionJournal',
                'user_id'         => $user->id,
                'md5'             => md5('Ook hallo'),
                'filename'        => 'empty-file-2.txt',
                'title'           => 'Empty file 2',
                'description'     => 'This file is empty too',
                'notes'           => 'What notes do',
                'mime'            => 'text/plain',
                'size'            => strlen($encrypted),
                'uploaded'        => 1,
            ]
        );
        // echo crypted data to the file.
        file_put_contents(storage_path('upload/at-1.data'), $encrypted);
        file_put_contents(storage_path('upload/at-2.data'), $encrypted);

    }

    /**
     * @param User $user
     */
    private function createCategories(User $user)
    {
        Category::firstOrCreateEncrypted(['name' => 'Groceries', 'user_id' => $user->id]);
        Category::firstOrCreateEncrypted(['name' => 'Car', 'user_id' => $user->id]);
    }

    /**
     * @param User $user
     */
    private function createExpenseAccounts(User $user)
    {
        $expenses = ['Adobe', 'Google', 'Vitens', 'Albert Heijn', 'PLUS', 'Apple', 'Bakker', 'Belastingdienst', 'bol.com', 'Cafe Central', 'conrad.nl',
                     'coolblue', 'Shell',
                     'DUO', 'Etos', 'FEBO', 'Greenchoice', 'Halfords', 'XS4All', 'iCentre', 'Jumper', 'Land lord'];
        foreach ($expenses as $name) {
            // create account:
            Account::create(
                [
                    'user_id'         => $user->id,
                    'account_type_id' => 4,
                    'name'            => $name,
                    'active'          => 1,
                    'encrypted'       => 1,
                ]
            );
        }

    }

    /**
     * @param User $user
     */
    private function createRevenueAccounts(User $user)
    {
        $revenues = ['Job', 'Belastingdienst', 'Bank', 'KPN', 'Google'];
        foreach ($revenues as $name) {
            // create account:
            Account::create(
                [
                    'user_id'         => $user->id,
                    'account_type_id' => 5,
                    'name'            => $name,
                    'active'          => 1,
                    'encrypted'       => 1,
                ]
            );
        }
    }

    /**
     * @param User $user
     */
    private function openingBalanceSavings(User $user)
    {
        // opposing account for opening balance:
        $opposing = Account::create(
            [
                'user_id'         => $user->id,
                'account_type_id' => 6,
                'name'            => 'Opposing for savings',
                'active'          => 1,
                'encrypted'       => 1,
            ]
        );

        // savings
        $savings = TestData::findAccount($user, 'TestData Savings');

        $journal = TransactionJournal::create(
            [
                'user_id'                 => $user->id,
                'transaction_type_id'     => 4,
                'transaction_currency_id' => 1,
                'description'             => 'Opening balance for savings account',
                'completed'               => 1,
                'date'                    => $this->start->format('Y-m-d'),
            ]
        );

        // transactions
        Transaction::create(
            [
                'account_id'             => $opposing->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => -10000,
            ]
        );

        Transaction::create(
            [
                'account_id'             => $savings->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => 10000,
            ]
        );


    }
}

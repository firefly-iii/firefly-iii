<?php

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Role;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
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
        $user      = User::create(['email' => 'thegrumpydictator@gmail.com', 'password' => bcrypt('james'), 'reset' => null, 'remember_token' => null]);
        $emptyUser = User::create(['email' => 'thegrumpydictator+empty@gmail.com', 'password' => bcrypt('james'), 'reset' => null, 'remember_token' => null]);


        $admin = Role::where('name', 'owner')->first();
        $user->attachRole($admin);


        // create asset accounts for user #1.
        $this->createAssetAccounts($user);

        // create bills for user #1
        $this->createBills($user);

        // create some budgets for user #1
        $this->createBudgets($user);

        // create some categories for user #1
        $this->createCategories($user);

        // create some piggy banks for user #1
        $this->createPiggybanks($user);

        // create some expense accounts for user #1
        $this->createExpenseAccounts($user);

        // create some revenue accounts for user #1
        $this->createRevenueAccounts($user);

        // create journal + attachment:
        $this->createAttachments($user);

        // create opening balance for savings account:
        $this->openingBalanceSavings($user);
    }

    /**
     * @param User $user
     */
    private function createAssetAccounts(User $user)
    {
        $assets = ['TestData Checking Account', 'TestData Savings', 'TestData Shared', 'TestData Creditcard', 'Emergencies', 'STE'];
        // first two ibans match test-upload.csv
        $ibans     = ['NL11XOLA6707795988', 'NL96DZCO4665940223', 'NL81RCQZ7160379858', 'NL19NRAP2367994221', 'NL40UKBK3619908726', 'NL38SRMN4325934708'];
        $assetMeta = [
            ['accountRole' => 'defaultAsset'],
            ['accountRole' => 'savingAsset',],
            ['accountRole' => 'sharedAsset',],
            ['accountRole' => 'ccAsset', 'ccMonthlyPaymentDate' => '2015-05-27', 'ccType' => 'monthlyFull',],
            ['accountRole' => 'savingAsset',],
            ['accountRole' => 'savingAsset',],
        ];

        foreach ($assets as $index => $name) {
            // create account:
            $account = Account::create(
                [
                    'user_id'         => $user->id,
                    'account_type_id' => 3,
                    'name'            => $name,
                    'active'          => 1,
                    'encrypted'       => 1,
                    'iban'            => $ibans[$index],
                ]
            );
            foreach ($assetMeta[$index] as $name => $value) {
                AccountMeta::create(['account_id' => $account->id, 'name' => $name, 'data' => $value,]);
            }
        }

    }

    /**
     * @param User $user
     */
    private function createAttachments(User $user)
    {

        $toAccount   = $this->findAccount($user, 'TestData Checking Account');
        $fromAccount = $this->findAccount($user, 'Job');

        $journal = TransactionJournal::create(
            [
                'user_id'                 => $user->id,
                'transaction_type_id'     => 2,
                'transaction_currency_id' => 1,
                'description'             => 'Some journal for attachment',
                'completed'               => 1,
                'date'                    => new Carbon,
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
    private function createBills(User $user)
    {
        Bill::create(
            [
                'name'        => 'Rent',
                'match'       => 'rent,land,lord',
                'amount_min'  => 795,
                'amount_max'  => 805,
                'user_id'     => $user->id,
                'date'        => '2015-01-01',
                'active'      => 1,
                'automatch'   => 1,
                'repeat_freq' => 'monthly',
                'skip'        => 0,
            ]
        );
        Bill::create(
            [
                'name'        => 'Health insurance',
                'match'       => 'zilveren,kruis,health',
                'amount_min'  => 120,
                'amount_max'  => 140,
                'user_id'     => $user->id,
                'date'        => '2015-01-01',
                'active'      => 1,
                'automatch'   => 1,
                'repeat_freq' => 'monthly',
                'skip'        => 0,
            ]
        );
    }

    /**
     * @param $user
     */
    private function createBudgets($user)
    {
        $set     = [
            Budget::firstOrCreateEncrypted(['name' => 'Groceries', 'user_id' => $user->id]),
            Budget::firstOrCreateEncrypted(['name' => 'Bills', 'user_id' => $user->id]),
        ];
        $current = new Carbon;
        /** @var Budget $budget */
        foreach ($set as $budget) {

            // some budget limits:
            $start = clone $current;
            $end   = clone $current;
            $start->startOfMonth();
            $end->endOfMonth();

            BudgetLimit::create(
                [
                    'budget_id'   => $budget->id,
                    'startdate'   => $start->format('Y-m-d'),
                    'amount'      => 500,
                    'repeats'     => 0,
                    'repeat_freq' => 'monthly',
                ]
            );
        }
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @param User $user
     */
    private function createPiggybanks(User $user)
    {
        $account = $this->findAccount($user, 'TestData Savings');

        $camera                    = PiggyBank::create(
            [
                'account_id'    => $account->id,
                'name'          => 'New camera',
                'targetamount'  => 1000,
                'startdate'     => '2015-04-01',
                'reminder_skip' => 0,
                'remind_me'     => 0,
                'order'         => 1,
            ]
        );
        $repetition                = $camera->piggyBankRepetitions()->first();
        $repetition->currentamount = 735;
        $repetition->save();

        // events:
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $camera->id,
                'date'          => '2015-05-01',
                'amount'        => '245',
            ]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $camera->id,
                'date'          => '2015-06-01',
                'amount'        => '245',
            ]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $camera->id,
                'date'          => '2015-07-01',
                'amount'        => '245',
            ]
        );


        $phone                     = PiggyBank::create(
            [
                'account_id'    => $account->id,
                'name'          => 'New phone',
                'targetamount'  => 600,
                'startdate'     => '2015-04-01',
                'reminder_skip' => 0,
                'remind_me'     => 0,
                'order'         => 2,
            ]
        );
        $repetition                = $phone->piggyBankRepetitions()->first();
        $repetition->currentamount = 333;
        $repetition->save();

        // events:
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $phone->id,
                'date'          => '2015-05-01',
                'amount'        => '111',
            ]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $phone->id,
                'date'          => '2015-06-01',
                'amount'        => '111',
            ]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $phone->id,
                'date'          => '2015-07-01',
                'amount'        => '111',
            ]
        );

        $couch                     = PiggyBank::create(
            [
                'account_id'    => $account->id,
                'name'          => 'New couch',
                'targetamount'  => 500,
                'startdate'     => '2015-04-01',
                'reminder_skip' => 0,
                'remind_me'     => 0,
                'order'         => 3,
            ]
        );
        $repetition                = $couch->piggyBankRepetitions()->first();
        $repetition->currentamount = 120;
        $repetition->save();

        // events:
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $couch->id,
                'date'          => '2015-05-01',
                'amount'        => '40',
            ]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $couch->id,
                'date'          => '2015-06-01',
                'amount'        => '40',
            ]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $couch->id,
                'date'          => '2015-07-01',
                'amount'        => '40',
            ]
        );

        // empty one.
        PiggyBank::create(
            [
                'account_id'    => $account->id,
                'name'          => 'New head set',
                'targetamount'  => 500,
                'startdate'     => '2015-04-01',
                'reminder_skip' => 0,
                'remind_me'     => 0,
                'order'         => 4,
            ]
        );

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
     * @param      $name
     *
     * @return Account|null
     */
    private function findAccount(User $user, $name)
    {
        /** @var Account $account */
        foreach ($user->accounts()->get() as $account) {
            if ($account->name == $name) {
                return $account;
                break;
            }
        }

        return null;
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
        $savings = $this->findAccount($user, 'TestData Savings');

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
                'account_id'              => $opposing->id,
                'transaction_journal_id' => $journal->id,
                'amount'                  => -10000,
            ]
        );

        Transaction::create(
            [
                'account_id'              => $savings->id,
                'transaction_journal_id' => $journal->id,
                'amount'                  => 10000,
            ]
        );


    }
}

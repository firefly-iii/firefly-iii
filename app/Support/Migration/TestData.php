<?php
declare(strict_types = 1);
namespace FireflyIII\Support\Migration;

/**
 * TestData.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Carbon\Carbon;
use Crypt;
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
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Log;

/**
 * Class TestData
 *
 * @package FireflyIII\Support\Migration
 */
class TestData
{
    /**
     * @param User $user
     */
    public static function createAssetAccounts(User $user)
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
     * @param User   $user
     * @param Carbon $start
     */
    public static function createAttachments(User $user, Carbon $start)
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
                'date'                    => $start->format('Y-m-d'),
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
        $one       = Attachment::create(
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
        $two = Attachment::create(
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
        file_put_contents(storage_path('upload/at-' . $one->id . '.data'), $encrypted);
        file_put_contents(storage_path('upload/at-' . $two->id . '.data'), $encrypted);

    }

    /**
     * @param User $user
     */
    public static function createBills(User $user)
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
     * @param User   $user
     * @param Carbon $current
     * @param        $name
     * @param        $amount
     */
    public static function createBudgetLimit(User $user, Carbon $current, string $name, string $amount)
    {
        $start  = clone $current;
        $end    = clone $current;
        $budget = self::findBudget($user, $name);
        $start->startOfMonth();
        $end->endOfMonth();

        BudgetLimit::create(
            [
                'budget_id'   => $budget->id,
                'startdate'   => $start->format('Y-m-d'),
                'amount'      => $amount,
                'repeats'     => 0,
                'repeat_freq' => 'monthly',
            ]
        );
    }

    /**
     * @param User $user
     */
    public static function createBudgets(User $user)
    {
        Budget::firstOrCreateEncrypted(['name' => 'Groceries', 'user_id' => $user->id]);
        Budget::firstOrCreateEncrypted(['name' => 'Bills', 'user_id' => $user->id]);
        Budget::firstOrCreateEncrypted(['name' => 'Car', 'user_id' => $user->id]);

        // some empty budgets.
        foreach (['A', 'B', 'C', 'D', "E"] as $letter) {
            Budget::firstOrCreateEncrypted(['name' => 'Empty budget ' . $letter, 'user_id' => $user->id]);
        }
    }

    /**
     * @param User $user
     */
    public static function createCategories(User $user)
    {
        Category::firstOrCreateEncrypted(['name' => 'Groceries', 'user_id' => $user->id]);
        Category::firstOrCreateEncrypted(['name' => 'Car', 'user_id' => $user->id]);
    }

    /**
     * @param User $user
     */
    public static function createExpenseAccounts(User $user)
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
     * @param User   $user
     * @param string $description
     * @param Carbon $date
     * @param string $amount
     *
     * @return TransactionJournal
     */
    public static function createRent(User $user, string $description, Carbon $date, string $amount): TransactionJournal
    {
        $fromAccount = TestData::findAccount($user, 'TestData Checking Account');
        $toAccount   = TestData::findAccount($user, 'Land lord');
        $category    = Category::firstOrCreateEncrypted(['name' => 'Rent', 'user_id' => $user->id]);
        $budget      = Budget::firstOrCreateEncrypted(['name' => 'Bills', 'user_id' => $user->id]);
        $journal     = TransactionJournal::create(
            [
                'user_id'                 => $user->id,
                'transaction_type_id'     => 1,
                'transaction_currency_id' => 1,
                'bill_id'                 => 1,
                'description'             => $description,
                'completed'               => 1,
                'date'                    => $date,
            ]
        );
        Transaction::create(
            [
                'account_id'             => $fromAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $amount * -1,

            ]
        );
        Transaction::create(
            [
                'account_id'             => $toAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $amount,

            ]
        );
        $journal->categories()->save($category);
        $journal->budgets()->save($budget);

        return $journal;

    }


    /**
     * @param User   $user
     * @param string $description
     * @param Carbon $date
     * @param string $amount
     *
     * @return TransactionJournal
     */
    public static function createIncome(User $user, string $description, Carbon $date, string $amount): TransactionJournal
    {
        $date  = new Carbon($date->format('Y-m') . '-23'); // paid on 23rd.
        $today = new Carbon;
        if ($date >= $today) {
            return new TransactionJournal;
        }
        $toAccount   = TestData::findAccount($user, 'TestData Checking Account');
        $fromAccount = TestData::findAccount($user, 'Job');
        $category    = Category::firstOrCreateEncrypted(['name' => 'Salary', 'user_id' => $user->id]);
        // create journal:

        $journal = TransactionJournal::create(
            [
                'user_id'                 => $user->id,
                'transaction_type_id'     => 2,
                'transaction_currency_id' => 1,
                'description'             => $description,
                'completed'               => 1,
                'date'                    => $date,
            ]
        );
        Transaction::create(
            [
                'account_id'             => $fromAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $amount * -1,

            ]
        );
        Transaction::create(
            [
                'account_id'             => $toAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $amount,

            ]
        );
        $journal->categories()->save($category);

        return $journal;

    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @param User $user
     */
    public static function createPiggybanks(User $user)
    {
        $account = self::findAccount($user, 'TestData Savings');

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
    public static function createRevenueAccounts(User $user)
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
    public static function createRules(User $user)
    {
        $group = RuleGroup::create(
            [
                'user_id'     => $user->id,
                'order'       => 1,
                'title'       => trans('firefly.default_rule_group_name'),
                'description' => trans('firefly.default_rule_group_description'),
                'active'      => 1,
            ]
        );
        $rule  = Rule::create(
            [
                'user_id'         => $user->id,
                'rule_group_id'   => $group->id,
                'order'           => 1,
                'active'          => 1,
                'stop_processing' => 0,
                'title'           => trans('firefly.default_rule_name'),
                'description'     => trans('firefly.default_rule_description'),
            ]
        );

        // three triggers:
        RuleTrigger::create(
            [
                'rule_id'         => $rule->id,
                'order'           => 1,
                'active'          => 1,
                'stop_processing' => 0,
                'trigger_type'    => 'user_action',
                'trigger_value'   => 'store-journal',
            ]
        );
        RuleTrigger::create(
            [
                'rule_id'         => $rule->id,
                'order'           => 2,
                'active'          => 1,
                'stop_processing' => 0,
                'trigger_type'    => 'description_is',
                'trigger_value'   => trans('firefly.default_rule_trigger_description'),
            ]
        );
        RuleTrigger::create(
            [
                'rule_id'         => $rule->id,
                'order'           => 3,
                'active'          => 1,
                'stop_processing' => 0,
                'trigger_type'    => 'from_account_is',
                'trigger_value'   => trans('firefly.default_rule_trigger_from_account'),
            ]
        );

        // two actions:
        RuleAction::create(
            [
                'rule_id'      => $rule->id,
                'order'        => 1,
                'active'       => 1,
                'action_type'  => 'prepend_description',
                'action_value' => trans('firefly.default_rule_action_prepend'),
            ]
        );
        RuleAction::create(
            [
                'rule_id'      => $rule->id,
                'order'        => 1,
                'active'       => 1,
                'action_type'  => 'set_category',
                'action_value' => trans('firefly.default_rule_action_set_category'),
            ]
        );
    }

    /**
     * @param User        $user
     * @param Carbon|null $date
     */
    public static function createTags(User $user, Carbon $date = null)
    {
        $title = 'SomeTag nr. ' . rand(1, 1234);
        if (!is_null($date)) {
            $title = 'SomeTag' . $date->month . '.' . $date->year . '.nothing';
        }

        Tag::create(
            [
                'user_id' => $user->id,
                'tag'     => $title,
                'tagMode' => 'nothing',
                'date'    => is_null($date) ? null : $date->format('Y-m-d'),


            ]
        );
    }

    /**
     * @return User
     */
    public static function createUsers(): User
    {
        $user = User::create(['email' => 'thegrumpydictator@gmail.com', 'password' => bcrypt('james'), 'reset' => null, 'remember_token' => null]);
        User::create(['email' => 'thegrumpydictator+empty@gmail.com', 'password' => bcrypt('james'), 'reset' => null, 'remember_token' => null]);
        User::create(['email' => 'thegrumpydictator+deleteme@gmail.com', 'password' => bcrypt('james'), 'reset' => null, 'remember_token' => null]);


        $admin = Role::where('name', 'owner')->first();
        $user->attachRole($admin);

        return $user;
    }

    /**
     * @param User $user
     * @param      $name
     *
     * @return Account|null
     */
    public static function findAccount(User $user, string $name)
    {
        /** @var Account $account */
        foreach ($user->accounts()->get() as $account) {
            if ($account->name == $name) {
                Log::debug('Trying to find "' . $name . '" in "' . $account->name . '", and found it!');

                return $account;
            }
            Log::debug('Trying to find "' . $name . '" in "' . $account->name . '".');
        }

        return null;
    }

    /**
     * @param User $user
     * @param      $name
     *
     * @return Budget|null
     */
    public static function findBudget(User $user, string $name)
    {
        /** @var Budget $budget */
        foreach (Budget::get() as $budget) {
            if ($budget->name == $name && $user->id == $budget->user_id) {
                return $budget;
                break;
            }
        }

        return null;
    }

    /**
     * @param User   $user
     * @param Carbon $date
     */
    public static function openingBalanceSavings(User $user, Carbon $date)
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
                'date'                    => $date->format('Y-m-d'),
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

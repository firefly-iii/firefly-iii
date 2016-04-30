<?php
declare(strict_types = 1);
namespace FireflyIII\Support\Migration;

/**
 * TestData.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Carbon\Carbon;
use Crypt;
use FireflyIII\Events\BudgetLimitStored;
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
use Navigation;
use Storage;

/**
 * Class TestData
 *
 * @package FireflyIII\Support\Migration
 */
class TestData
{


    /**
     * @param User  $user
     * @param array $assets
     *
     * @return bool
     */
    public static function createAssetAccounts(User $user, array $assets): bool
    {
        if (count($assets) == 0) {
            $assets = [
                [
                    'name' => 'TestData Checking Account',
                    'iban' => 'NL11XOLA6707795988',
                    'meta' => [
                        'accountRole' => 'defaultAsset',
                    ],
                ],
                [
                    'name' => 'TestData Savings',
                    'iban' => 'NL96DZCO4665940223',
                    'meta' => [
                        'accountRole' => 'savingAsset',
                    ],
                ],
                [
                    'name' => 'TestData Shared',
                    'iban' => 'NL81RCQZ7160379858',
                    'meta' => [
                        'accountRole' => 'sharedAsset',
                    ],
                ],
                [
                    'name' => 'TestData Creditcard',
                    'iban' => 'NL19NRAP2367994221',
                    'meta' => [
                        'accountRole'          => 'ccAsset',
                        'ccMonthlyPaymentDate' => '2015-05-27',
                        'ccType'               => 'monthlyFull',
                    ],
                ],
                [
                    'name' => 'Emergencies',
                    'iban' => 'NL40UKBK3619908726',
                    'meta' => [
                        'accountRole' => 'savingAsset',
                    ],
                ],
                [
                    'name' => 'STE',
                    'iban' => 'NL38SRMN4325934708',
                    'meta' => [
                        'accountRole' => 'savingAsset',
                    ],
                ],
            ];
        }

        foreach ($assets as $index => $entry) {
            // create account:
            $account = Account::create(
                [
                    'user_id'         => $user->id,
                    'account_type_id' => 3,
                    'name'            => $entry['name'],
                    'active'          => 1,
                    'encrypted'       => 1,
                    'iban'            => $entry['iban'],
                ]
            );
            foreach ($entry['meta'] as $name => $value) {
                AccountMeta::create(['account_id' => $account->id, 'name' => $name, 'data' => $value]);
            }
        }

        return true;

    }

    /**
     * @param User   $user
     * @param Carbon $start
     *
     * @return TransactionJournal
     */
    public static function createAttachments(User $user, Carbon $start): TransactionJournal
    {

        $args    = [
            'user'             => $user,
            'description'      => 'Some journal for attachment',
            'date'             => $start,
            'from'             => 'Job',
            'to'               => 'TestData Checking Account',
            'amount'           => '100',
            'transaction_type' => 2,
        ];
        $journal = self::createJournal($args);

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
        $disk = Storage::disk('upload');
        $disk->put('at-' . $one->id . '.data', $encrypted);
        $disk->put('at-' . $two->id . '.data', $encrypted);

        return $journal;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public static function createBills(User $user): bool
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

        return true;

    }

    /**
     * @param User   $user
     * @param Carbon $current
     * @param string $name
     * @param string $amount
     *
     * @return BudgetLimit
     */
    public static function createBudgetLimit(User $user, Carbon $current, string $name, string $amount): BudgetLimit
    {
        $start  = clone $current;
        $end    = clone $current;
        $budget = self::findBudget($user, $name);
        $start->startOfMonth();
        $end->endOfMonth();

        $limit = BudgetLimit::create(
            [
                'budget_id'   => $budget->id,
                'startdate'   => $start->format('Y-m-d'),
                'amount'      => $amount,
                'repeats'     => 0,
                'repeat_freq' => 'monthly',
            ]
        );
        // also trigger event.
        $thisEnd = Navigation::addPeriod($start, 'monthly', 0);
        $thisEnd->subDay();
        event(new BudgetLimitStored($limit, $thisEnd));

        return $limit;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public static function createBudgets(User $user): bool
    {
        Budget::firstOrCreateEncrypted(['name' => 'Groceries', 'user_id' => $user->id]);
        Budget::firstOrCreateEncrypted(['name' => 'Bills', 'user_id' => $user->id]);
        Budget::firstOrCreateEncrypted(['name' => 'Car', 'user_id' => $user->id]);

        // some empty budgets.
        foreach (['A'] as $letter) {
            Budget::firstOrCreateEncrypted(['name' => 'Empty budget ' . $letter, 'user_id' => $user->id]);
        }

        return true;
    }

    /**
     * @param User   $user
     * @param Carbon $date
     *
     * @return TransactionJournal
     */
    public static function createCar(User $user, Carbon $date): TransactionJournal
    {
        // twice:
        $amount = strval(rand(4000, 5000) / 100);
        $args   = [
            'user'        => $user,
            'description' => 'Bought gas',
            'date'        => new Carbon($date->format('Y-m') . '-10'),// paid on 10th
            'from'        => 'TestData Checking Account',
            'to'          => 'Shell',
            'amount'      => $amount,
            'category'    => 'Car',
            'budget'      => 'Car',
        ];
        self::createJournal($args);

        // again!
        $args['date']        = new Carbon($date->format('Y-m') . '-20'); // paid on 20th
        $args['amount']      = strval(rand(4000, 5000) / 100);
        $args['description'] = 'Gas for car';
        $journal             = self::createJournal($args);

        return $journal;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public static function createCategories(User $user): bool
    {
        Category::firstOrCreateEncrypted(['name' => 'Groceries', 'user_id' => $user->id]);
        Category::firstOrCreateEncrypted(['name' => 'Car', 'user_id' => $user->id]);

        return true;
    }

    /**
     * @param User   $user
     * @param Carbon $date
     *
     * @return bool
     *
     */
    public static function createDrinksAndOthers(User $user, Carbon $date): bool
    {
        $start = clone $date;
        $end   = clone $date;
        $today = new Carbon;
        $start->startOfMonth();
        $end->endOfMonth();
        $current = clone $start;
        while ($current < $end && $current < $today) {

            // weekly drink:
            $thisDate = clone $current;
            $thisDate->addDay();
            $amount = strval(rand(1500, 3600) / 100);
            $args   = [
                'user'        => $user,
                'description' => 'Going out for drinks',
                'date'        => $thisDate,
                'from'        => 'TestData Checking Account',
                'to'          => 'Cafe Central',
                'amount'      => $amount,
                'category'    => 'Drinks',
                'budget'      => 'Going out',
            ];
            self::createJournal($args);

            $current->addWeek();
        }

        return true;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public static function createExpenseAccounts(User $user): bool
    {
        $expenses = ['Adobe', 'Google', 'Vitens', 'Albert Heijn', 'PLUS', 'Apple', 'Bakker', 'Belastingdienst', 'bol.com', 'Cafe Central', 'conrad.nl',
                     'Coolblue', 'Shell',
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

        return true;

    }

    /**
     * @param User   $user
     * @param Carbon $date
     *
     * @return bool
     *
     */
    public static function createGroceries(User $user, Carbon $date): bool
    {
        $start = clone $date;
        $end   = clone $date;
        $today = new Carbon;
        $start->startOfMonth();
        $end->endOfMonth();

        $stores       = ['Albert Heijn', 'PLUS', 'Bakker'];
        $descriptions = ['Groceries', 'Bought some groceries', 'Got groceries'];

        $current = clone $start;
        while ($current < $end && $current < $today) {
            // daily groceries:
            $amount = (string)round((rand(1500, 2500) / 100), 2);

            $args = [
                'user'        => $user,
                'description' => $descriptions[rand(0, count($descriptions) - 1)],
                'date'        => $current,
                'from'        => 'TestData Checking Account',
                'to'          => $stores[rand(0, count($stores) - 1)],
                'amount'      => $amount,
                'category'    => 'Daily groceries',
                'budget'      => 'Groceries',
            ];
            self::createJournal($args);
            $current->addDay();
        }

        return true;
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

        // create journal:
        $args    = [
            'user'             => $user,
            'description'      => $description,
            'date'             => $date,
            'from'             => 'Job',
            'to'               => 'TestData Checking Account',
            'amount'           => $amount,
            'category'         => 'Salary',
            'transaction_type' => 2,
        ];
        $journal = self::createJournal($args);

        return $journal;

    }

    /**
     * @param array $opt
     *
     * @return TransactionJournal
     */
    public static function createJournal(array $opt): TransactionJournal
    {
        $type = $opt['transaction_type'] ?? 1;

        $journal = TransactionJournal::create(
            [
                'user_id'                 => $opt['user']->id,
                'transaction_type_id'     => $type,
                'transaction_currency_id' => 1,
                'description'             => $opt['description'],
                'completed'               => 1,
                'date'                    => $opt['date'],
            ]
        );
        self::createTransactions($journal, self::findAccount($opt['user'], $opt['from']), self::findAccount($opt['user'], $opt['to']), $opt['amount']);
        if (isset($opt['category'])) {
            $journal->categories()->save(self::findCategory($opt['user'], $opt['category']));
        }
        if (isset($opt['budget'])) {
            $journal->budgets()->save(self::findBudget($opt['user'], $opt['budget']));
        }

        return $journal;
    }

    /**
     * @param User   $user
     * @param string $accountName
     *
     * @return bool
     *
     */
    public static function createPiggybanks(User $user, string $accountName): bool
    {

        $account                   = self::findAccount($user, $accountName);
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

        return true;
    }

    /**
     * @param User   $user
     * @param string $description
     * @param Carbon $date
     * @param string $amount
     *
     * @return TransactionJournal
     */
    public static function createPower(User $user, string $description, Carbon $date, string $amount): TransactionJournal
    {
        $args    = [
            'user'        => $user,
            'description' => $description,
            'date'        => new Carbon($date->format('Y-m') . '-06'),// paid on 10th
            'from'        => 'TestData Checking Account',
            'to'          => 'Greenchoice',
            'amount'      => $amount,
            'category'    => 'House',
            'budget'      => 'Bills',
        ];
        $journal = self::createJournal($args);

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
    public static function createRent(User $user, string $description, Carbon $date, string $amount): TransactionJournal
    {
        $args    = [
            'user'        => $user,
            'description' => $description,
            'date'        => $date,
            'from'        => 'TestData Checking Account',
            'to'          => 'Land lord',
            'amount'      => $amount,
            'category'    => 'Rent',
            'budget'      => 'Bills',
        ];
        $journal = self::createJournal($args);

        return $journal;

    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public static function createRevenueAccounts(User $user): bool
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

        return true;
    }

    /**
     * @param User $user
     *
     * @return RuleGroup
     */
    public static function createRules(User $user): RuleGroup
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

        return $group;
    }

    /**
     * @param User   $user
     * @param Carbon $date
     *
     * @return TransactionJournal
     */
    public static function createSavings(User $user, Carbon $date): TransactionJournal
    {
        $args    = [
            'user'             => $user,
            'description'      => 'Save money',
            'date'             => new Carbon($date->format('Y-m') . '-24'),// paid on 24th.
            'from'             => 'TestData Checking Account',
            'to'               => 'TestData Savings',
            'amount'           => '150',
            'category'         => 'Money management',
            'transaction_type' => 3,
        ];
        $journal = self::createJournal($args);

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
    public static function createTV(User $user, string $description, Carbon $date, string $amount): TransactionJournal
    {
        $args    = [
            'user'        => $user,
            'description' => $description,
            'date'        => new Carbon($date->format('Y-m') . '-15'),
            'from'        => 'TestData Checking Account',
            'to'          => 'XS4All',
            'amount'      => $amount,
            'category'    => 'House',
            'budget'      => 'Bills',
        ];
        $journal = self::createJournal($args);

        return $journal;

    }

    /**
     * @param User   $user
     * @param Carbon $date
     *
     * @return Tag
     */
    public static function createTags(User $user, Carbon $date): Tag
    {
        $title = 'SomeTag' . $date->month . '.' . $date->year . '.nothing';

        $tag = Tag::create(
            [
                'user_id' => $user->id,
                'tag'     => $title,
                'tagMode' => 'nothing',
                'date'    => $date->format('Y-m-d'),


            ]
        );

        return $tag;
    }

    /**
     * @param TransactionJournal $journal
     * @param Account            $from
     * @param Account            $to
     * @param string             $amount
     *
     * @return bool
     */
    public static function createTransactions(TransactionJournal $journal, Account $from, Account $to, string $amount): bool
    {
        Transaction::create(
            [
                'account_id'             => $from->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => bcmul($amount, '-1'),

            ]
        );
        Transaction::create(
            [
                'account_id'             => $to->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $amount,

            ]
        );

        return true;
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
     * @param User   $user
     * @param string $description
     * @param Carbon $date
     * @param string $amount
     *
     * @return TransactionJournal
     */
    public static function createWater(User $user, string $description, Carbon $date, string $amount): TransactionJournal
    {
        $args    = [
            'user'        => $user,
            'description' => $description,
            'date'        => new Carbon($date->format('Y-m') . '-10'), // paid on 10th
            'from'        => 'TestData Checking Account',
            'to'          => 'Vitens',
            'amount'      => $amount,
            'category'    => 'House',
            'budget'      => 'Bills',
        ];
        $journal = self::createJournal($args);

        return $journal;

    }

    /**
     * @param User $user
     * @param      $name
     *
     * @return Account
     */
    public static function findAccount(User $user, string $name): Account
    {
        /** @var Account $account */
        foreach ($user->accounts()->get() as $account) {
            if ($account->name == $name) {
                return $account;
            }
        }

        return new Account;
    }

    /**
     * @param User $user
     * @param      $name
     *
     * @return Budget
     */
    public static function findBudget(User $user, string $name): Budget
    {
        /** @var Budget $budget */
        foreach (Budget::get() as $budget) {
            if ($budget->name == $name && $user->id == $budget->user_id) {
                return $budget;
            }
        }

        return Budget::firstOrCreateEncrypted(['name' => $name, 'user_id' => $user->id]);
    }

    /**
     * @param User $user
     * @param      $name
     *
     * @return Category
     */
    public static function findCategory(User $user, string $name): Category
    {
        /** @var Category $category */
        foreach (Category::get() as $category) {
            if ($category->name == $name && $user->id == $category->user_id) {
                return $category;
            }
        }

        return Category::firstOrCreateEncrypted(['name' => $name, 'user_id' => $user->id]);
    }

    /**
     * @param User   $user
     * @param Carbon $date
     *
     * @return TransactionJournal
     */
    public static function openingBalanceSavings(User $user, Carbon $date): TransactionJournal
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
        self::createTransactions($journal, $opposing, $savings, '10000');

        return $journal;

    }


}

<?php
use League\FactoryMuffin\Facade as FactoryMuffin;


if (!class_exists('RandomString')) {
    /**
     * Class RandomString
     */
    class RandomString
    {
        /**
         * @param int $length
         *
         * @return string
         */
        public static function generateRandomString($length = 10)
        {
            $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString     = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }

            return $randomString;
        }

    }
}

FactoryMuffin::define(
    'FireflyIII\Models\Bill',
    [
        'user_id'         => 'factory|FireflyIII\User',
        'name'            => 'sentence',
        'match'           => function () {
            $words = [];
            for ($i = 0; $i < 3; $i++) {
                $words[] = RandomString::generateRandomString(5);
            }

            return join(',', $words);
        },
        'amount_min'      => 10,
        'amount_max'      => 20,
        'date'            => 'date',
        'active'          => 1,
        'automatch'       => 1,
        'repeat_freq'     => 'monthly',
        'skip'            => 0,
        'name_encrypted'  => 1,
        'match_encrypted' => 1,

    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\Account',
    [
        'user_id'         => 'factory|FireflyIII\User',
        'account_type_id' => 'factory|FireflyIII\Models\AccountType',
        'name'            => 'word',
        'active'          => 'boolean',
        'encrypted'       => 'boolean',
        'virtual_balance' => 0
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\Tag',
    [
        'description' => 'sentence',
        'user_id'     => 'factory|FireflyIII\User',
        'tag'         => function () {
            return RandomString::generateRandomString(20);
        },
        'tagMode'     => 'nothing',
        'date'        => 'date',
        'latitude'    => 12,
        'longitude'   => 13,
        'zoomLevel'   => 3,
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\Budget',
    [
        'user_id'   => 'factory|FireflyIII\User',
        'name'      => 'sentence',
        'active'    => 'boolean',
        'encrypted' => 1,
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\TransactionGroup',
    [
        'user_id'  => 'factory|FireflyIII\User',
        'relation' => 'balance',
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\Reminder',
    [
        'user_id'            => 'factory|FireflyIII\User',
        'startdate'          => 'date',
        'enddate'            => 'date',
        'active'             => 'boolean',
        'notnow'             => 'boolean',
        'remindersable_id'   => 'factory|FireflyIII\Models\Piggybank',
        'remindersable_type' => 'FireflyIII\Models\Piggybank',
        'metadata'           => function () {
            return [
                'perReminder' => 100,
                'rangesCount' => 0,
                'ranges'      => [],
                'leftToSave'  => 100,
            ];
        },
        'encrypted'          => 1,
    ]
);


FactoryMuffin::define(
    'FireflyIII\Models\Category',
    [
        'user_id'   => 'factory|FireflyIII\User',
        'name'      => 'sentence',
        'encrypted' => 1,
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\LimitRepetition',
    [
        'budget_limit_id' => 'factory|FireflyIII\Models\BudgetLimit',
        'startdate'       => 'date',
        'enddate'         => 'date',
        'amount'          => 'integer',
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\BudgetLimit',
    [
        'budget_id'   => 'factory|FireflyIII\Models\Budget',
        'startdate'   => 'date',
        'amount'      => 'integer',
        'repeats'     => 'false',
        'repeat_freq' => 'monthly',

    ]
);


FactoryMuffin::define(
    'FireflyIII\Models\Preference',
    [
        'name'    => 'word',
        'data'    => 'sentence',
        'user_id' => 'factory|FireflyIII\User',
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\AccountType',
    [
        'type'     => function () {
            $types = ['Expense account', 'Revenue account', 'Asset account'];
            $count = DB::table('account_types')->count();

            return $types[$count];
        },
        'editable' => 1,
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\TransactionCurrency',
    [
        'code'   => function () {
            return RandomString::generateRandomString(3);
        },
        'symbol' => function () {
            return RandomString::generateRandomString(1);
        },
        'name'   => 'word'
    ]
);

FactoryMuffin::define(
    'FireflyIII\User',
    [
        'email'    => 'email',
        'password' => bcrypt('james'),
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\Transaction',
    [
        'transaction_journal_id' => 'factory|FireflyIII\Models\TransactionJournal',
        'amount'                 => function () {
            return rand(1, 100);
        },
        'description'            => 'sentence',
        'account_id'             => 'factory|FireflyIII\Models\Account'
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\PiggyBank',
    [
        'account_id'    => 'factory|FireflyIII\Models\Account',
        'name'          => 'sentence',
        'targetamount'  => 'integer',
        'startdate'     => 'date',
        'targetdate'    => 'date',
        'reminder_skip' => 0,
        'remind_me'     => 0,
        'order'         => 0,
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\TransactionType',
    [
        'type' => function () {
            $types = ['Withdrawal', 'Deposit', 'Transfer'];
            $count = DB::table('transaction_types')->count();
            if ($count < 3) {
                return $types[$count];
            } else {
                return RandomString::generateRandomString(10);
            }
        }
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\TransactionJournal',
    [
        'user_id'                 => 'factory|FireflyIII\User',
        'transaction_type_id'     => 'factory|FireflyIII\Models\TransactionType',
        'transaction_currency_id' => 'factory|FireflyIII\Models\TransactionCurrency',
        'description'             => 'sentence',
        'completed'               => '1',
        'date'                    => 'date',
        'encrypted'               => '1',
        'order'                   => '0',
    ]
);
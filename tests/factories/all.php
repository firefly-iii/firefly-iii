<?php
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use League\FactoryMuffin\Facade as FactoryMuffin;

if (!class_exists('RandomString')) {
    /**
     * Class RandomString
     */
    class RandomString
    {
        public static $count = 0;
        public static $set   = [];

        /**
         * @param int $length
         *
         * @return string
         */
        public static function generateRandomString($length = 10)
        {
            $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);


            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }

            while (in_array($randomString, self::$set)) {
                // create another if its in the current $set:
                $randomString = '';
                for ($i = 0; $i < $length; $i++) {
                    $randomString .= $characters[rand(0, $charactersLength - 1)];
                }
            }
            self::$set[] = $randomString;

            return $randomString;
        }

    }
}

FactoryMuffin::define(
    'FireflyIII\Models\Role',
    [
        'name' => 'word',
    ]
);

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
        'encrypted'       => function () {
            return true;
        },
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
        'amount'          => function () {
            return rand(1, 100);
        },
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\BudgetLimit',
    [
        'budget_id'   => 'factory|FireflyIII\Models\Budget',
        'startdate'   => 'date',
        'amount'      => function () {
            return rand(1, 100);
        },
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
            $types = ['Expense account', 'Revenue account', 'Asset account', 'Cash account'];
            $count = DB::table('account_types')->count();
            if ($count < 4) {
                return $types[$count];
            } else {
                return RandomString::generateRandomString(10);
            }
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
            return RandomString::generateRandomString(8);
        },
        'name'   => 'word'
    ]
);


FactoryMuffin::define(
    'FireflyIII\User',
    [
        'email'    => function () {
            $first  = RandomString::generateRandomString(20);
            $second = RandomString::generateRandomString(20);
            $domain = RandomString::generateRandomString(30);
            $email  = $first . '.' . $second . '@' . $domain . '.com';

            return $email;
        },
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
        'targetamount'  => function () {
            return rand(1, 100);
        },
        'startdate'     => 'date',
        'targetdate'    => 'date',
        'order'         => 0,
    ]
);

FactoryMuffin::define(
    'FireflyIII\Models\PiggyBankRepetition',
    [
        'piggy_bank_id' => 'factory|FireflyIII\Models\PiggyBank',
        'startdate'     => 'date',
        'targetdate'    => 'date',
        'currentamount' => function () {
            return rand(1, 100);
        },
    ]
);


FactoryMuffin::define(
    'FireflyIII\Models\PiggyBankEvent',
    [
        'piggy_bank_id'          => 'factory|FireflyIII\Models\PiggyBank',
        'transaction_journal_id' => 'factory|FireflyIII\Models\TransactionJournal',
        'date'                   => 'date',
        'amount'                 => function () {
            return rand(1, 100);
        },
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
    ], function (TransactionJournal $object, $saved) {
    if ($saved) {
        $one = FactoryMuffin::create('FireflyIII\Models\Account');
        $two = FactoryMuffin::create('FireflyIII\Models\Account');

        Transaction::create(
            [
                'account_id'             => $one->id,
                'transaction_journal_id' => $object->id,
                'amount'                 => 100
            ]
        );
        Transaction::create(
            [
                'account_id'             => $two->id,
                'transaction_journal_id' => $object->id,
                'amount'                 => -100
            ]
        );

    }

}
);

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
    'FireflyIII\Models\Account', [
                                   'user_id'         => 'factory|FireflyIII\User',
                                   'account_type_id' => 'factory|FireflyIII\Models\AccountType',
                                   'name'            => 'word',
                                   'active'          => 'boolean',
                                   'encrypted'       => 'boolean',
                                   'virtual_balance' => 0
                               ]
);

FactoryMuffin::define(
    'FireflyIII\Models\Preference', [
                                      'name'    => 'word',
                                      'data'    => 'sentence',
                                      'user_id' => 'factory|FireflyIII\User',
                                  ]
);

FactoryMuffin::define(
    'FireflyIII\Models\AccountType', [
                                       'type'     => function () {
                                           $types = ['Expense account', 'Revenue account', 'Asset account'];
                                           $count = DB::table('account_types')->count();
                                           return $types[$count];
                                       },
                                       'editable' => 1,
                                   ]
);

FactoryMuffin::define(
    'FireflyIII\Models\TransactionCurrency', [
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
    'FireflyIII\User', [
                         'email'    => 'email',
                         'password' => bcrypt('james'),
                     ]
);

FactoryMuffin::define(
    'FireflyIII\Models\Transaction', [
                                       'transaction_journal_id' => 'factory|FireflyIII\Models\TransactionJournal',
                                       'amount'                 => 'integer',
                                       'account_id'             => 'factory|FireflyIII\Models\Account'
                                   ]
);

FactoryMuffin::define(
    'FireflyIII\Models\TransactionType', [
                                           'type' => 'word',
                                       ]
);

FactoryMuffin::define(
    'FireflyIII\Models\TransactionJournal', [
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
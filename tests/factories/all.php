<?php
use League\FactoryMuffin\Facade as FactoryMuffin;

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
                                       'type'     => 'word',
                                       'editable' => 1,
                                   ]
);

FactoryMuffin::define(
    'FireflyIII\Models\TransactionCurrency', [
    'code'   => 'EUR',
    'symbol' => 'x',
    'name'   => 'word'
]
);

FactoryMuffin::define(
    'FireflyIII\User', [
                         'email'    => 'email',
                         'password' => bcrypt('james'),
                     ]
);
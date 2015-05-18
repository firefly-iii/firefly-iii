<?php

return [
    'index_periods'            => ['1D', '1W', '1M', '3M', '6M', '1Y', 'custom'],
    'budget_periods'           => ['daily', 'weekly', 'monthly', 'quarterly', 'half-year', 'yearly'],
    'piggy_bank_periods'       => [
        'week'    => 'Week',
        'month'   => 'Month',
        'quarter' => 'Quarter',
        'year'    => 'Year'
    ],
    'periods_to_text'          => [
        'weekly'    => 'A week',
        'monthly'   => 'A month',
        'quarterly' => 'A quarter',
        'half-year' => 'Six months',
        'yearly'    => 'A year',
    ],

    'accountRoles'             => [
        'defaultAsset' => 'Default asset account',
        'sharedAsset'  => 'Shared asset account',
        'savingAsset'  => 'Savings account',
        'ccAsset'      => 'Credit card',
    ],

    'range_to_text'            => [
        '1D'     => 'day',
        '1W'     => 'week',
        '1M'     => 'month',
        '3M'     => 'three months',
        '6M'     => 'half year',
        'custom' => '(custom)'
    ],
    'ccTypes'                  => [
        'monthlyFull' => 'Full payment every month'
    ],
    'range_to_name'            => [
        '1D' => 'one day',
        '1W' => 'one week',
        '1M' => 'one month',
        '3M' => 'three months',
        '6M' => 'six months',
        '1Y' => 'one year',
    ],
    'range_to_repeat_freq'     => [
        '1D'     => 'weekly',
        '1W'     => 'weekly',
        '1M'     => 'monthly',
        '3M'     => 'quarterly',
        '6M'     => 'half-year',
        'custom' => 'monthly'
    ],
    'subTitlesByIdentifier'    =>
        [
            'asset'   => 'Asset accounts',
            'expense' => 'Expense accounts',
            'revenue' => 'Revenue accounts',
        ],
    'subIconsByIdentifier'     =>
        [
            'asset'               => 'fa-money',
            'Asset account'       => 'fa-money',
            'Default account'     => 'fa-money',
            'Cash account'        => 'fa-money',
            'expense'             => 'fa-shopping-cart',
            'Expense account'     => 'fa-shopping-cart',
            'Beneficiary account' => 'fa-shopping-cart',
            'revenue'             => 'fa-download',
            'Revenue account'     => 'fa-download',
        ],
    'accountTypesByIdentifier' =>
        [
            'asset'   => ['Default account', 'Asset account'],
            'expense' => ['Expense account', 'Beneficiary account'],
            'revenue' => ['Revenue account'],
        ],
    'accountTypeByIdentifier'  =>
        [
            'asset'   => 'Asset account',
            'expense' => 'Expense account',
            'revenue' => 'Revenue account'
        ],
    'shortNamesByFullName'     =>
        [
            'Default account'     => 'asset',
            'Asset account'       => 'asset',
            'Expense account'     => 'expense',
            'Beneficiary account' => 'expense',
            'Revenue account'     => 'revenue',
            'Cash account'        => 'cash',
        ],
    'lang'                     => [
        'en' => 'English',
        'nl' => 'Nederlands'
    ],
    'locales'                  => [
        'en' => ['en', 'English', 'en_US', 'en_US.utf8'],
        'nl' => ['nl', 'Dutch', 'nl_NL', 'nl_NL.utf8'],
    ],
    'month'                    => [
        'en' => '%B %Y',
        'nl' => '%B %Y',
    ],
    'monthAndDay'              => [
        'en' => '%B %e, %Y',
        'nl' => '%e %B %Y',
    ],

];

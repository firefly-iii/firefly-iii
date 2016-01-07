<?php

return [
    'chart'              => 'chartjs',
    'version'            => '3.6.0',
    'index_periods'      => ['1D', '1W', '1M', '3M', '6M', '1Y', 'custom'],
    'budget_periods'     => ['daily', 'weekly', 'monthly', 'quarterly', 'half-year', 'yearly'],
    'csv_import_enabled' => true,
    'maxUploadSize'      => 5242880,
    'allowedMimes'       => ['image/png', 'image/jpeg', 'application/pdf'],
    'piggy_bank_periods' => [
        'week'    => 'Week',
        'month'   => 'Month',
        'quarter' => 'Quarter',
        'year'    => 'Year'
    ],
    'periods_to_text'    => [
        'weekly'    => 'A week',
        'monthly'   => 'A month',
        'quarterly' => 'A quarter',
        'half-year' => 'Six months',
        'yearly'    => 'A year',
    ],

    'accountRoles' => [
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
            'cash'    => 'Cash accounts',
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
            'revenue' => 'Revenue account',
            'opening' => 'Initial balance account',
            'initial' => 'Initial balance account',
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
    'languages' => [
        'en_US' => ['name_locale' => 'English', 'name_english' => 'English', 'complete' => true],
        'nl_NL' => ['name_locale' => 'Nederlands', 'name_english' => 'Dutch', 'complete' => true],
        'pt_BR' => ['name_locale' => 'Português do Brasil', 'name_english' => 'Portugese (Brazil)', 'complete' => false],
        'fr_FR' => ['name_locale' => 'Français', 'name_english' => 'French', 'complete' => false],
    ],
    'lang'                     => [
        'en_US' => 'English',
        'nl_NL' => 'Nederlands',
        'fr_FR' => 'Français',
        'pt_BR' => 'Português do Brasil',
    ],
    'locales'                  => [
        'en_US' => ['en', 'English', 'en_US', 'en_US.utf8'],
        'nl_NL' => ['nl', 'Dutch', 'nl_NL', 'nl_NL.utf8'],
        'pt_BR' => ['pt_BR', 'pt_BR.utf8'],
        'fr_FR' => ['fr_FR', 'fr_FR.utf8'],
    ],
    'transactionTypesByWhat'   => [
        'expenses'   => ['Withdrawal'],
        'withdrawal' => ['Withdrawal'],
        'revenue'    => ['Deposit'],
        'deposit'    => ['Deposit'],
        'transfer'   => ['Transfer'],
        'transfers'  => ['Transfer'],
    ],
    'transactionIconsByWhat'   => [
        'expenses'   => 'fa-long-arrow-left',
        'withdrawal' => 'fa-long-arrow-left',
        'revenue'    => 'fa-long-arrow-right',
        'deposit'    => 'fa-long-arrow-right',
        'transfer'   => 'fa-exchange',
        'transfers'  => 'fa-exchange',

    ],

    'month'       => [
        'en_US' => '%B %Y',
        'nl_NL' => '%B %Y',
        'fr_FR' => '%B %Y',
        'pt_BR' => '%B %Y',
    ],
    'monthAndDay' => [
        'en_US' => '%B %e, %Y',
        'nl_NL' => '%e %B %Y',
        'fr_FR' => '%B %e, %Y',
        'pt_BR' => '%B %e, %Y',
    ],

];

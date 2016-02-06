<?php

return [
    'chart'              => 'chartjs',
    'version'            => '3.7.2.3',
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
    'languages'                => [
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
    'bindables'   => [
        // models
        'account'         => 'FireflyIII\Models\Account',
        'attachment'      => 'FireflyIII\Models\Attachment',
        'bill'            => 'FireflyIII\Models\Bill',
        'budget'          => 'FireflyIII\Models\Budget',
        'category'        => 'FireflyIII\Models\Category',
        'currency'        => 'FireflyIII\Models\TransactionCurrency',
        'limitrepetition' => 'FireflyIII\Models\LimitRepetition',
        'piggyBank'       => 'FireflyIII\Models\PiggyBank',
        'tj'              => 'FireflyIII\Models\TransactionJournal',
        'tag'             => 'FireflyIII\Models\Tag',
        'rule'            => 'FireflyIII\Models\Rule',
        'ruleGroup'       => 'FireflyIII\Models\RuleGroup',
        // lists
        'accountList'     => 'FireflyIII\Support\Binder\AccountList',
        'budgetList'      => 'FireflyIII\Support\Binder\BudgetList',
        'categoryList'    => 'FireflyIII\Support\Binder\CategoryList',

        // others
        'start_date'      => 'FireflyIII\Support\Binder\Date',
        'end_date'        => 'FireflyIII\Support\Binder\Date'
    ],

    'rule-triggers'     => [
        'user_action'           => 'FireflyIII\Rules\Triggers\UserAction',
        'from_account_starts'   => 'FireflyIII\Rules\Triggers\FromAccountStarts',
        'from_account_ends'     => 'FireflyIII\Rules\Triggers\FromAccountEnds',
        'from_account_is'       => 'FireflyIII\Rules\Triggers\FromAccountIs',
        'from_account_contains' => 'FireflyIII\Rules\Triggers\FromAccountContains',
        'to_account_starts'     => 'FireflyIII\Rules\Triggers\ToAccountStarts',
        'to_account_ends'       => 'FireflyIII\Rules\Triggers\ToAccountEnds',
        'to_account_is'         => 'FireflyIII\Rules\Triggers\ToAccountIs',
        'to_account_contains'   => 'FireflyIII\Rules\Triggers\ToAccountContains',
        'transaction_type'      => 'FireflyIII\Rules\Triggers\TransactionType',
        'amount_less'           => 'FireflyIII\Rules\Triggers\AmountLess',
        'amount_exactly'        => 'FireflyIII\Rules\Triggers\AmountExactly',
        'amount_more'           => 'FireflyIII\Rules\Triggers\AmountMore',
        'description_starts'    => 'FireflyIII\Rules\Triggers\DescriptionStarts',
        'description_ends'      => 'FireflyIII\Rules\Triggers\DescriptionEnds',
        'description_contains'  => 'FireflyIII\Rules\Triggers\DescriptionContains',
        'description_is'        => 'FireflyIII\Rules\Triggers\DescriptionIs',
    ],
    'rule-actions'      => [
        'set_category'        => 'FireflyIII\Rules\Actions\SetCategory',
        'clear_category'      => 'FireflyIII\Rules\Actions\ClearCategory',
        'set_budget'          => 'FireflyIII\Rules\Actions\SetBudget',
        'clear_budget'        => 'FireflyIII\Rules\Actions\ClearBudget',
        'add_tag'             => 'FireflyIII\Rules\Actions\AddTag',
        'remove_tag'          => 'FireflyIII\Rules\Actions\RemoveTag',
        'remove_all_tags'     => 'FireflyIII\Rules\Actions\RemoveAllTags',
        'set_description'     => 'FireflyIII\Rules\Actions\SetDescription',
        'append_description'  => 'FireflyIII\Rules\Actions\AppendDescription',
        'prepend_description' => 'FireflyIII\Rules\Actions\PrependDescription',
    ],
    // all rule actions that require text input:
    'rule-actions-text' => [
        'set_category',
        'set_budget',
        'add_tag',
        'remove_tag',
        'set_description',
        'append_description',
        'prepend_description',
    ]

];

<?php
declare(strict_types = 1);


return [
    'chart'               => 'chartjs',
    'version'             => '3.9.1',
    'csv_import_enabled'  => true,
    'maxUploadSize'       => 5242880,
    'allowedMimes'        => ['image/png', 'image/jpeg', 'application/pdf'],
    'resend_confirmation' => 3600,
    'confirmation_age'    => 14400, // four hours

    'export_formats' => [
        'csv' => 'FireflyIII\Export\Exporter\CsvExporter',
        // mt940 FireflyIII Export Exporter MtExporter
    ],
    'import_formats' => [
        'csv' => 'FireflyIII\Import\Importer\CsvImporter',
        // mt940 FireflyIII Import Importer MtImporter
    ],


    'default_export_format' => 'csv',
    'default_import_format' => 'csv',
    'bill_periods'          => ['weekly', 'monthly', 'quarterly', 'half-year', 'yearly'],

    'accountRoles' => [
        'defaultAsset' => 'Default asset account',
        'sharedAsset'  => 'Shared asset account',
        'savingAsset'  => 'Savings account',
        'ccAsset'      => 'Credit card',
    ],

    'ccTypes'                  => [
        'monthlyFull' => 'Full payment every month',
    ],
    'range_to_repeat_freq'     => [
        '1D'     => 'weekly',
        '1W'     => 'weekly',
        '1M'     => 'monthly',
        '3M'     => 'quarterly',
        '6M'     => 'half-year',
        '1Y'     => 'yearly',
        'custom' => 'custom',
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
        'pt_BR' => ['name_locale' => 'Português do Brasil', 'name_english' => 'Portuguese (Brazil)', 'complete' => true],
        'fr_FR' => ['name_locale' => 'Français', 'name_english' => 'French', 'complete' => false],
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

    'bindables' => [
        // models
        'account'           => 'FireflyIII\Models\Account',
        'attachment'        => 'FireflyIII\Models\Attachment',
        'bill'              => 'FireflyIII\Models\Bill',
        'budget'            => 'FireflyIII\Models\Budget',
        'category'          => 'FireflyIII\Models\Category',
        'currency'          => 'FireflyIII\Models\TransactionCurrency',
        'limitrepetition'   => 'FireflyIII\Models\LimitRepetition',
        'piggyBank'         => 'FireflyIII\Models\PiggyBank',
        'tj'                => 'FireflyIII\Models\TransactionJournal',
        'unfinishedJournal' => 'FireflyIII\Support\Binder\UnfinishedJournal',
        'tag'               => 'FireflyIII\Models\Tag',
        'rule'              => 'FireflyIII\Models\Rule',
        'ruleGroup'         => 'FireflyIII\Models\RuleGroup',
        'jobKey'            => 'FireflyIII\Models\ExportJob',
        // lists
        'accountList'       => 'FireflyIII\Support\Binder\AccountList',
        'budgetList'        => 'FireflyIII\Support\Binder\BudgetList',
        'journalList'       => 'FireflyIII\Support\Binder\JournalList',
        'categoryList'      => 'FireflyIII\Support\Binder\CategoryList',

        // others
        'start_date'        => 'FireflyIII\Support\Binder\Date',
        'end_date'          => 'FireflyIII\Support\Binder\Date',
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
    ],
    'test-triggers'     => [
        // The maximum number of transactions shown when testing a list of triggers
        'limit' => 10,

        // The maximum number of transactions to analyse, when testing a list of triggers
        'range' => 200,
    ],
];

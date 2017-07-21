<?php
/**
 * intro.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

/*
 * Always make sure intro is the first element (if any) and outro is the last one.
 */

return [
    // index
    'index'                   => [
        'intro'          => [],
        'accounts-chart' => ['element' => '#accounts-chart'],
        'box_out_holder' => ['element' => '#box_out_holder'],
        'help'           => ['element' => '#help', 'position' => 'bottom'],
        'sidebar-toggle' => ['element' => '#sidebar-toggle', 'position' => 'bottom'],
        'outro'          => [],
    ],
    // accounts: create
    'accounts_create'         => [
        'intro' => [],
        'iban'  => ['element' => '#ffInput_iban'],
    ],
    // extra text for asset account creation.
    'accounts_create_asset'   => [
        'opening_balance' => ['element' => '#ffInput_openingBalance'],
        'currency'        => ['element' => '#ffInput_currency_id'],
        'virtual'         => ['element' => '#ffInput_virtualBalance'],
    ],

    // budgets: index
    'budgets_index'           => [
        'intro'            => [],
        'set_budget'       => ['element' => '#availableBar',],
        'see_expenses_bar' => ['element' => '#spentBar'],
        'navigate_periods' => ['element' => '#periodNavigator'],
        'new_budget'       => ['element' => '#createBudgetBox'],
        'list_of_budgets'  => ['element' => '#budgetList'],

    ],
    // tags: wait for upgrade
    // reports: index, default report, audit, budget, cat, tag
    'reports_index'           => [
        'intro' => [],
    ],
    'reports_report_default'  => [
        'intro' => [],
    ],
    'reports_report_audit'    => [
        'intro' => [],
    ],
    'reports_report_category' => [
        'intro' => [],
    ],
    'reports_report_report'   => [
        'intro' => [],
    ],
    'reports_report_budget'   => [
        'intro' => [],
    ],

    // transactions: create
    'transactions_create'     => [
        'intro'            => [],
        'switch_box'       => ['element' => '#switch-box'],
        'ffInput_category' => ['element' => '#ffInput_category'],
    ],
    // piggies: index, create, show
    'piggy-banks_index'       => [
        'intro' => [],
    ],
    'piggy-banks_create'      => [
        'intro' => [],
    ],
    'piggy-banks_show'        => [
        'intro' => [],
    ],

    // bills: index, create, show
    'bills_index'             => [
        'intro' => [],
    ],
    'bills_create'            => [
        'intro' => [],
    ],
    'bills_show'              => [
        'intro' => [],
    ],
    // rules: index, create-rule, edit-rule
    'rules_index'             => [
        'intro'          => [],
        'new_rule_group' => ['element' => '#new_rule_group'],
        'new_rule'       => ['element' => '.new_rule'],
        'prio_buttons'   => ['element' => '.prio_buttons'],
        'test_buttons'   => ['element' => '.test_buttons'],
        'rule-triggers'  => ['element' => '.rule-triggers'],
        'outro'          => [],
    ],
    'rules_create'            => [
        'intro' => [],
    ],
    'rules_edit'              => [
        'intro' => [],
    ],
    // import: index, config-steps
    'import_index'            => [
        'intro' => [],
    ],
    'import_configure'        => [
        'intro' => [],
    ],
    // export: index
    'export_index'            => [
        'intro' => [],
    ],
    // preferences: index
    'preferences_index'       => [
        'intro' => [],
    ],
    // currencies: index, create
    'currencies_index'        => [
        'intro' => [],
    ],
    'currencies_create'       => [
        'intro' => [],
    ],
    // admin: index
    'admin_index'             => [
        'intro' => [],
    ],

];
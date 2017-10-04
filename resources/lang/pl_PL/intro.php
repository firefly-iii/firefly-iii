<?php
declare(strict_types=1);

/**
 * intro.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

return [
    // index
    'index_intro'                           => 'Welcome to the index page of Firefly III. Please take the time to walk through this intro to get a feeling of how Firefly III works.',
    'index_accounts-chart'                  => 'This chart shows the current balance of your asset accounts. You can select the accounts visible here in your preferences.',
    'index_box_out_holder'                  => 'This little box and the boxes next to this one will give you a quick overview of your financial situation.',
    'index_help'                            => 'If you ever need help with a page or a form, press this button.',
    'index_outro'                           => 'Most pages of Firefly III will start with a little tour like this one. Please contact me when you have questions or comments. Enjoy!',
    'index_sidebar-toggle'                  => 'To create new transactions, accounts or other things, use the menu under this icon.',

    // create account:
    'accounts_create_iban'                  => 'Give your accounts a valid IBAN. This could make a data import very easy in the future.',
    'accounts_create_asset_opening_balance' => 'Assets accounts may have an "opening balance", indicating the start of this account\'s history in Firefly.',
    'accounts_create_asset_currency'        => 'Firefly III supports multiple currencies. Asset accounts have one main currency, which you must set here.',
    'accounts_create_asset_virtual'         => 'It can sometimes help to give your account a virtual balance: an extra amount always added to or removed from the actual balance.',

    // budgets index
    'budgets_index_intro'                   => 'Budgets are used to manage your finances and form one of the core functions of Firefly III.',
    'budgets_index_set_budget'              => 'Set your total budget for every period so Firefly can tell you if you have budgeted all available money.',
    'budgets_index_see_expenses_bar'        => 'Spending money will slowly fill this bar.',
    'budgets_index_navigate_periods'        => 'Navigate through periods to easily set budgets ahead of time.',
    'budgets_index_new_budget'              => 'Create new budgets as you see fit.',
    'budgets_index_list_of_budgets'         => 'Use this table to set the amounts for each budget and see how you are doing.',
    'budgets_index_outro'                   => 'Aby dowiedzieć się więcej o budżetowaniu, użyj ikonki pomocy w prawym górnym rogu.',

    // reports (index)
    'reports_index_intro'                   => 'Use these reports to get detailed insights in your finances.',
    'reports_index_inputReportType'         => 'Pick a report type. Check out the help pages to see what each report shows you.',
    'reports_index_inputAccountsSelect'     => 'You can exclude or include asset accounts as you see fit.',
    'reports_index_inputDateRange'          => 'The selected date range is entirely up to you: from one day to 10 years.',
    'reports_index_extra-options-box'       => 'Depending on the report you have selected, you can select extra filters and options here. Watch this box when you change report types.',

    // reports (reports)
    'reports_report_default_intro'          => 'This report will give you a quick and comprehensive overview of your finances. If you wish to see anything else, please don\'t hestitate to contact me!',
    'reports_report_audit_intro'            => 'This report will give you detailed insights in your asset accounts.',
    'reports_report_audit_optionsBox'       => 'Use these check boxes to show or hide the columns you are interested in.',

    'reports_report_category_intro'                  => 'This report will give you insight in one or multiple categories.',
    'reports_report_category_pieCharts'              => 'These charts will give you insight in expenses and income per category or per account.',
    'reports_report_category_incomeAndExpensesChart' => 'This chart shows your expenses and income per category.',

    'reports_report_tag_intro'                  => 'This report will give you insight in one or multiple tags.',
    'reports_report_tag_pieCharts'              => 'These charts will give you insight in expenses and income per tag, account, category or budget.',
    'reports_report_tag_incomeAndExpensesChart' => 'This chart shows your expenses and income per tag.',

    'reports_report_budget_intro'                             => 'This report will give you insight in one or multiple budgets.',
    'reports_report_budget_pieCharts'                         => 'These charts will give you insight in expenses per budget or per account.',
    'reports_report_budget_incomeAndExpensesChart'            => 'This chart shows your expenses per budget.',

    // create transaction
    'transactions_create_switch_box'                          => 'Use these buttons to quickly switch the type of transaction you wish to save.',
    'transactions_create_ffInput_category'                    => 'You can freely type in this field. Previously created categories will be suggested.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Link your withdrawal to a budget for better financial control.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Use this dropdown when your withdrawal is in another currency.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Use this dropdown when your deposit is in another currency.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Select a piggy bank and link this transfer to your savings.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'This field shows you how much you\'ve saved in each piggy bank.',
    'piggy-banks_index_button'                                => 'Next to this progress bar are two buttons (+ and -) to add or remove money from each piggy bank.',
    'piggy-banks_index_accountStatus'                         => 'For each asset account with at least one piggy bank the status is listed in this table.',

    // create piggy
    'piggy-banks_create_name'                                 => 'What is your goal? A new couch, a camera, money for emergencies?',
    'piggy-banks_create_date'                                 => 'You can set a target date or a deadline for your piggy bank.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'This chart will show the history of this piggy bank.',
    'piggy-banks_show_piggyDetails'                           => 'Some details about your piggy bank',
    'piggy-banks_show_piggyEvents'                            => 'Any additions or removals are also listed here.',

    // bill index
    'bills_index_paid_in_period'                              => 'This field indicates when the bill was last paid.',
    'bills_index_expected_in_period'                          => 'This field indicates for each bill if and when the next bill is expected to hit.',

    // show bill
    'bills_show_billInfo'                                     => 'This table shows some general information about this bill.',
    'bills_show_billButtons'                                  => 'Use this button to re-scan old transactions so they will be matched to this bill.',
    'bills_show_billChart'                                    => 'This chart shows the transactions linked to this bill.',

    // create bill
    'bills_create_name'                                       => 'Use a descriptive name such as "Rent" or "Health insurance".',
    'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Select a minimum and maximum amount for this bill.',
    'bills_create_repeat_freq_holder'                         => 'Most bills repeat monthly, but you can set another frequency here.',
    'bills_create_skip_holder'                                => 'If a bill repeats every 2 weeks for example, the "skip"-field should be set to "1" to skip every other week.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III allows you to manage rules, that will automagically be applied to any transaction you create or edit.',
    'rules_index_new_rule_group'                              => 'You can combine rules in groups for easier management.',
    'rules_index_new_rule'                                    => 'Create as many rules as you like.',
    'rules_index_prio_buttons'                                => 'Order them any way you see fit.',
    'rules_index_test_buttons'                                => 'You can test your rules or apply them to existing transactions.',
    'rules_index_rule-triggers'                               => 'Rules have "triggers" and "actions" that you can order by drag-and-drop.',
    'rules_index_outro'                                       => 'Be sure to check out the help pages using the (?) icon in the top right!',

    // create rule:
    'rules_create_mandatory'                                  => 'Choose a descriptive title, and set when the rule should be fired.',
    'rules_create_ruletriggerholder'                          => 'Add as many triggers as you like, but remember that ALL triggers must match before any actions are fired.',
    'rules_create_test_rule_triggers'                         => 'Use this button to see which transactions would match your rule.',
    'rules_create_actions'                                    => 'Set as many actions as you like.',

    // preferences
    'preferences_index_tabs'                                  => 'More options are available behind these tabs.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III supports multiple currencies, which you can change on this page.',
    'currencies_index_default'                                => 'Firefly III has one default currency. You can always switch of course using these buttons.',

    // create currency
    'currencies_create_code'                                  => 'This code should be ISO compliant (Google it for your new currency).',
];

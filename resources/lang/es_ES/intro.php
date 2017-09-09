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
    'index_intro'                           => 'Bienvenido a la página de índice de Firefly III. Por favor tómate tu tiempo para revisar esta guía y que puedas hacerte una idea de cómo funciona Firefly III.',
    'index_accounts-chart'                  => 'Este gráfico muestra el saldo actual de tus cuentas. Puedes seleccionar las cuentas que se muestran en él desde tus preferencias.',
    'index_box_out_holder'                  => 'Esta pequeña caja y las cajas a continuación te darán una visión rápida de tu situación financiera.',
    'index_help'                            => 'Si alguna vez necesitas ayuda en una página o formulario, pulsa este botón.',
    'index_outro'                           => 'La mayoría de las páginas de Firefly III comenzarán con una pequeña introducción como ésta. Por favor, ponte en contacto conmigo si tienes preguntas o comentarios. ¡Disfruta!',
    'index_sidebar-toggle'                  => 'Para crear nuevas transacciones, cuentas u otros elementos, utiliza el menú bajo este icono.',

    // create account:
    'accounts_create_iban'                  => 'Indica un IBAN válido en tus cuentas. Esto facilitará la importación de datos en el futuro.',
    'accounts_create_asset_opening_balance' => 'Las cuentas pueden tener un "saldo de apertura", que indica el saldo inicial de esta cuenta en Firefly.',
    'accounts_create_asset_currency'        => 'Firefly III admite múltiples divisas. Las cuentas tienen una divisa principal, que debes indicar aquí.',
    'accounts_create_asset_virtual'         => 'A veces puede ayudar el darle a tu cuenta un balance virtual: una cantidad extra que se añade o resta siempre del balance real.',

    // budgets index
    'budgets_index_intro'                   => 'Los presupuestos se utilizan para administrar sus finanzas y son una de las funciones básicas de Firefly III.',
    'budgets_index_set_budget'              => 'Fija tu presupuesto total para cada período, de tal forma que Firefly pueda indicarte si has presupuestado todo el dinero disponible.',
    'budgets_index_see_expenses_bar'        => 'Gastar dinero irá llenando poco a poco esta barra.',
    'budgets_index_navigate_periods'        => 'Navega a través de períodos para configurar fácilmente presupuestos con anticipación.',
    'budgets_index_new_budget'              => 'Crea nuevos presupuestos como mejor te parezca.',
    'budgets_index_list_of_budgets'         => 'Use this table to set the amounts for each budget and see how you are doing.',

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

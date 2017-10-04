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
    'index_intro'                           => 'Bienvenue sur la page index de Firefly III. Veuillez prendre le temps de parcourir l\'introduction pour comprendre comment Firefly III fonctionne.',
    'index_accounts-chart'                  => 'Ce tableau montre le solde actuel de vos comptes d\'actifs. Vous pouvez sélectionner les comptes visibles ici dans vos préférences.',
    'index_box_out_holder'                  => 'Cette petite boîte et les cases à côté de celle-ci vous donneront un rapide aperçu de votre situation financière.',
    'index_help'                            => 'Si vous avez besoin d’aide avec une page ou un formulaire, appuyez sur ce bouton.',
    'index_outro'                           => 'La plupart des pages de Firefly III vont commencer avec un petit tour comme celui-ci. Merci de me contacter si vous avez des questions ou des commentaires. Profitez-en !',
    'index_sidebar-toggle'                  => 'Pour créer de nouvelles transactions, comptes ou autres choses, utilisez le menu sous cette icône.',

    // create account:
    'accounts_create_iban'                  => 'Donnez à vos comptes un IBAN valide. Cela pourrait rendre une importation de données très facile à l\'avenir.',
    'accounts_create_asset_opening_balance' => 'Les comptes d\'actifs peuvent avoir un «solde d\'ouverture», indiquant le début de l\'historique de ce compte dans Firefly.',
    'accounts_create_asset_currency'        => 'Firefly III prend en charge plusieurs devises. Les comptes d\'actifs ont une devise principale, que vous devez définir ici.',
    'accounts_create_asset_virtual'         => 'Il peut parfois être utile de donner à votre compte un solde virtuel : un montant supplémentaire toujours ajouté ou soustrait du solde réel.',

    // budgets index
    'budgets_index_intro'                   => 'Les budgets sont utilisés pour gérer vos finances et forment l\'une des principales fonctions de Firefly III.',
    'budgets_index_set_budget'              => 'Définissez votre budget total pour chaque période, de sorte que Firefly puisse vous dire si vous avez budgétisé tout l\'argent disponible.',
    'budgets_index_see_expenses_bar'        => 'Dépenser de l\'argent va lentement remplir cette barre.',
    'budgets_index_navigate_periods'        => 'Parcourez des périodes pour régler facilement les budgets à l\'avance.',
    'budgets_index_new_budget'              => 'Créez de nouveaux budgets comme bon vous semble.',
    'budgets_index_list_of_budgets'         => 'Utilisez ce tableau pour définir les montants pour chaque budget et voir comment vous vous en sortez.',
    'budgets_index_outro'                   => 'Pour en savoir plus sur la budgétisation, utilisez l\'icône d\'aide en haut à droite.',

    // reports (index)
    'reports_index_intro'                   => 'Utilisez ces rapports pour obtenir des informations détaillées sur vos finances.',
    'reports_index_inputReportType'         => 'Choisissez un type de rapport. Consultez les pages d\'aide pour voir ce que vous présente chaque rapport.',
    'reports_index_inputAccountsSelect'     => 'Vous pouvez exclure ou inclure les comptes d\'actifs comme bon vous semble.',
    'reports_index_inputDateRange'          => 'La plage de dates sélectionnée est entièrement libre : de un jour à 10 ans.',
    'reports_index_extra-options-box'       => 'Selon le rapport que vous avez sélectionné, vous pouvez sélectionner des filtres et options supplémentaires ici. Regardez cette case lorsque vous modifiez les types de rapport.',

    // reports (reports)
    'reports_report_default_intro'          => 'Ce rapport vous donnera un aperçu complet et rapide de vos finances. Si vous souhaitez y voir autre chose, n\'hésitez pas à me contacter!',
    'reports_report_audit_intro'            => 'Ce rapport vous donnera des informations détaillées sur vos comptes d\'actifs.',
    'reports_report_audit_optionsBox'       => 'Utilisez ces cases à cocher pour afficher ou masquer les colonnes qui vous intéressent.',

    'reports_report_category_intro'                  => 'Ce rapport vous donnera un aperçu d\'une ou de plusieurs catégories.',
    'reports_report_category_pieCharts'              => 'Ces tableaux vous donneront un aperçu des dépenses et du revenu par catégorie ou par compte.',
    'reports_report_category_incomeAndExpensesChart' => 'Ce tableau montre vos dépenses et votre revenu par catégorie.',

    'reports_report_tag_intro'                  => 'Ce rapport vous donnera un aperçu d\'une ou de plusieurs étiquettes.',
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
    'piggy-banks_create_name'                                 => 'Quel est votre objectif ? Un nouveau divan, une caméra, de l\'argent pour les urgences ?',
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

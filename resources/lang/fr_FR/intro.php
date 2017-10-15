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
    'reports_report_tag_pieCharts'              => 'Ces tableaux vous donneront un aperçu des dépenses et du revenu par étiquette, catégorie, ou budget.',
    'reports_report_tag_incomeAndExpensesChart' => 'Ce tableau montre vos dépenses et votre revenu par étiquette.',

    'reports_report_budget_intro'                             => 'Ce rapport vous donnera un aperçu d\'un ou plusieurs budgets.',
    'reports_report_budget_pieCharts'                         => 'Ces tableaux vous donneront un aperçu des dépenses par budget ou par compte.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Ce graphique montre vos dépenses par budget.',

    // create transaction
    'transactions_create_switch_box'                          => 'Utilisez ces boutons pour changer rapidement le type de transaction que vous souhaitez enregistrer.',
    'transactions_create_ffInput_category'                    => 'Vous pouvez saisir librement ce champ. Les catégories créées précédemment seront suggérées.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Reliez votre retrait à un budget pour un meilleur contrôle financier.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Utilisez ce menu déroulant lorsque votre retrait est dans une autre devise.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Utilisez ce menu déroulant lorsque votre dépôt est dans une autre devise.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Sélectionnez une tirelire et reliez ce transfert à vos économies.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Ce champ vous montre combien vous avez mis de côté dans chaque tirelire.',
    'piggy-banks_index_button'                                => 'À côté de cette barre de progression sont deux boutons (+ et -) pour ajouter ou retirer de l’argent de chaque tirelire.',
    'piggy-banks_index_accountStatus'                         => 'Pour chaque compte d\'actif avec au moins une tirelire, le statut est indiqué dans ce tableau.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Quel est votre objectif ? Un nouveau divan, une caméra, de l\'argent pour les urgences ?',
    'piggy-banks_create_date'                                 => 'Vous pouvez définir une date butoir ou une date limite pour votre tirelire.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Ce tableau montrera l\'historique de cette tirelire.',
    'piggy-banks_show_piggyDetails'                           => 'Quelques détails sur votre tirelire',
    'piggy-banks_show_piggyEvents'                            => 'Des ajouts ou suppressions sont également répertoriées ici.',

    // bill index
    'bills_index_paid_in_period'                              => 'Ce champ indique quand la facture a été payée pour la dernière fois.',
    'bills_index_expected_in_period'                          => 'Ce champ indique pour chaque facture si et quand la prochaine facture devrait frapper.',

    // show bill
    'bills_show_billInfo'                                     => 'Ce tableau présente des informations générales sur cette facture.',
    'bills_show_billButtons'                                  => 'Utilisez ce bouton pour réexaminer les anciennes transactions afin qu\'elles correspondent à cette facture.',
    'bills_show_billChart'                                    => 'Ce tableau montre les transactions liées à cette facture.',

    // create bill
    'bills_create_name'                                       => 'Utilisez un nom équivoque tel que "Loyer" ou "Assurance maladie".',
    'bills_create_match'                                      => 'Pour faire correspondre les transactions, utilisez les termes de ces transactions ou le compte de dépenses impliqué. Tous les mots doivent correspondre.',
    'bills_create_amount_min_holder'                          => 'Sélectionnez un montant minimum et maximum pour cette facture.',
    'bills_create_repeat_freq_holder'                         => 'La plupart des factures sont mensuelles, mais vous pouvez définir une autre fréquence ici.',
    'bills_create_skip_holder'                                => 'Si une facture se répète toutes les 2 semaines par exemple, le champ "sauter" doit être réglé sur "1" pour sauter une semaine sur deux.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III vous permet de gérer les règles, qui seront automagiquement appliquées à toute transaction que vous créez ou modifiez.',
    'rules_index_new_rule_group'                              => 'Vous pouvez rassembler les règles en groupes pour une gestion plus facile.',
    'rules_index_new_rule'                                    => 'Créez autant de règles que vous le souhaitez.',
    'rules_index_prio_buttons'                                => 'Mettez-les dans l\'ordre que vous jugez convenable.',
    'rules_index_test_buttons'                                => 'Vous pouvez tester vos règles ou les appliquer aux transactions existantes.',
    'rules_index_rule-triggers'                               => 'Les règles ont des "déclencheurs" et des "actions" que vous pouvez ordonner en glisser-déposer.',
    'rules_index_outro'                                       => 'Pensez à consulter les pages d\'aide en utilisant l\'icône (?) En haut à droite!',

    // create rule:
    'rules_create_mandatory'                                  => 'Choisissez un titre parlant et définissez quand est-ce que la règle doit être déclenchée.',
    'rules_create_ruletriggerholder'                          => 'Ajoutez autant de déclencheurs que vous le souhaitez, mais n\'oubliez pas que TOUS les déclencheurs doivent correspondre avant que les actions ne soient déclenchées.',
    'rules_create_test_rule_triggers'                         => 'Utilisez ce bouton pour voir quelles transactions correspondent à votre règle.',
    'rules_create_actions'                                    => 'Définissez autant d\'actions que vous le souhaitez.',

    // preferences
    'preferences_index_tabs'                                  => 'Plus d\'options sont disponibles derrière ces onglets.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III prend en charge plusieurs devises, que vous pouvez modifier sur cette page.',
    'currencies_index_default'                                => 'Firefly III a une devise par défaut. Vous pouvez toujours en changer de l\'aide à ces boutons, bien entendu.',

    // create currency
    'currencies_create_code'                                  => 'Ce code devrait être conforme à l\'ISO (recherchez-le sur google votre nouvelle devise).',
];

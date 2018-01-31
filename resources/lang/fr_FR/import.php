<?php
/**
 * import.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

return [
    // status of import:
    'status_wait_title'                    => 'Veuillez patienter...',
    'status_wait_text'                     => 'Cette boîte disparaîtra dans un instant.',
    'status_fatal_title'                   => 'Une erreur fatale est survenue',
    'status_fatal_text'                    => 'Une erreur fatale est survenue que le traitement d\'importation ne peut pas récupérer. Voir l\'explication en rouge ci-dessous.',
    'status_fatal_more'                    => 'Si l\'erreur est un time-out, l\'importation sera arrêtée pendant son traitement. Pour certaines configurations de serveur, ce n\'est que le serveur qui s\'est arrêté alors que l\'importation continue de fonctionner en arrière-plan. Pour vérifier cela, consultez les fichiers journaux. Si le problème persiste, envisagez d\'importer plutôt par ligne de commande.',
    'status_ready_title'                   => 'L\'importation est prête à démarrer',
    'status_ready_text'                    => 'L\'importation est prête à démarrer. Toute la configuration requise été effectuée. Vous pouvez téléchargez le fichier de configuration. Cela vous permettra de recommencer rapidement l\'importation si tout ne fonctionnait pas comme prévu. Pour exécuter l\'importation, vous pouvez soit exécuter la commande suivante dans la console du serveur, soit exécuter l\'importation depuis cette page web. Selon votre configuration générale, l\'importation via la console vous donnera plus de détails.',
    'status_ready_noconfig_text'           => 'L\'importation est prête à démarrer. Toute la configuration requise été effectuée. Pour exécuter l\'importation, vous pouvez soit exécuter la commande suivante dans la console du serveur, soit exécuter l\'importation depuis cette page web. Selon votre configuration générale, l\'importation via la console vous donnera plus de détails.',
    'status_ready_config'                  => 'Télécharger la configuration',
    'status_ready_start'                   => 'Démarrer l\'importation',
    'status_ready_share'                   => 'Vous pouvez télécharger votre configuration et de la partager dans le <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">centre de configuration d\'import</a></strong>. Cela permettra à d\'autres utilisateurs de Firefly III d\'importer leurs fichiers plus facilement.',
    'status_job_new'                       => 'Le travail est tout récent.',
    'status_job_configuring'               => 'L\'importation est en cours de configuration.',
    'status_job_configured'                => 'L\'importation est configurée.',
    'status_job_running'                   => 'L\'importation est en cours... Veuillez patienter...',
    'status_job_error'                     => 'Le travail a généré une erreur.',
    'status_job_finished'                  => 'L\'importation est terminée !',
    'status_running_title'                 => 'L\'importation est en cours d\'exécution',
    'status_running_placeholder'           => 'Attendez pour une mise à jour...',
    'status_finished_title'                => 'Le traitement d\'importation est terminé',
    'status_finished_text'                 => 'Le traitement d\'importation a importé vos données.',
    'status_errors_title'                  => 'Erreurs lors de l\'importation',
    'status_errors_single'                 => 'Une erreur est survenue lors de l\'importation. Cela ne semble pas être fatal.',
    'status_errors_multi'                  => 'Certaines erreurs sont survenues lors de l\'importation. Celles-ci ne semblent pas être fatales.',
    'status_bread_crumb'                   => 'Statut d\'importation',
    'status_sub_title'                     => 'Statut d\'importation',
    'config_sub_title'                     => 'Configurez votre importation',
    'status_finished_job'                  => 'The :count transactions imported can be found in tag <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    'status_finished_no_tag'               => 'Firefly III has not collected any journals from your import file.',
    'import_with_key'                      => 'Importer avec la clé \':key\'',

    // file, upload something
    'file_upload_title'                    => 'Configuration de l\'importation (1/4) - Téléchargez votre fichier',
    'file_upload_text'                     => 'Ce traitement vous aidera à importer des fichiers de votre banque dans Firefly III. Consultez les pages d\'aide en haut à droite.',
    'file_upload_fields'                   => 'Champs',
    'file_upload_help'                     => 'Sélectionnez votre fichier',
    'file_upload_config_help'              => 'Si vous avez précédemment importé des données dans Firefly III, vous avez peut-être téléchargé un fichier de configuration qui définit les relations entre les différents champs. Pour certaines banques, des utilisateurs ont bien voulu partager leur fichier ici : <a href="https://github.com/firefly-iii/import-configurations/wiki">fichiers de configuration</a>.',
    'file_upload_type_help'                => 'Sélectionnez le type de fichier que vous allez télécharger',
    'file_upload_submit'                   => 'Envoyer des fichiers',

    // file, upload types
    'import_file_type_csv'                 => 'CSV (valeurs séparées par des virgules)',

    // file, initial config for CSV
    'csv_initial_title'                    => 'Configuration d\'importation (2/4) - Configuration d\'importation CSV',
    'csv_initial_text'                     => 'Pour pouvoir importer votre fichier correctement, veuillez valider les options ci-dessous.',
    'csv_initial_box'                      => 'Configuration d\'importation CSV de base',
    'csv_initial_box_title'                => 'Options de configuration de l\'importation CSV de base',
    'csv_initial_header_help'              => 'Cochez cette case si la première ligne de votre fichier CSV contient les entêtes des colonnes.',
    'csv_initial_date_help'                => 'Date time format in your CSV. Follow the format like <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'csv_initial_delimiter_help'           => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'csv_initial_import_account_help'      => 'If your CSV file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the CSV belong to.',
    'csv_initial_submit'                   => 'Passez à l’étape 3/4',

    // file, new options:
    'file_apply_rules_title'               => 'Appliquer les règles',
    'file_apply_rules_description'         => 'Apply your rules. Note that this slows the import significantly.',
    'file_match_bills_title'               => 'Faire correspondre les factures',
    'file_match_bills_description'         => 'Match your bills to newly created withdrawals. Note that this slows the import significantly.',

    // file, roles config
    'csv_roles_title'                      => 'Import setup (3/4) - Define each column\'s role',
    'csv_roles_text'                       => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'csv_roles_table'                      => 'Tableau',
    'csv_roles_column_name'                => 'Nom de colonne',
    'csv_roles_column_example'             => 'Données d\'exemple de colonne',
    'csv_roles_column_role'                => 'Signification des données de colonne',
    'csv_roles_do_map_value'               => 'Mapper ces valeurs',
    'csv_roles_column'                     => 'Colonne',
    'csv_roles_no_example_data'            => 'Aucun exemple de données disponible',
    'csv_roles_submit'                     => 'Passez à l’étape 4/4',

    // not csv, but normal warning
    'roles_warning'                        => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',
    'foreign_amount_warning'               => 'If you mark a column as containing an amount in a foreign currency, you must also set the column that contains which currency it is.',
    // file, map data
    'file_map_title'                       => 'Import setup (4/4) - Connect import data to Firefly III data',
    'file_map_text'                        => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'file_map_field_value'                 => 'Valeur du champ',
    'file_map_field_mapped_to'             => 'Mappé à',
    'map_do_not_map'                       => '(ne pas mapper)',
    'file_map_submit'                      => 'Démarrez l\'importation',
    'file_nothing_to_map'                  => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',

    // map things.
    'column__ignore'                       => '(ignorer cette colonne)',
    'column_account-iban'                  => 'Compte d’actif (IBAN)',
    'column_account-id'                    => 'Asset account ID (matching FF3)',
    'column_account-name'                  => 'Compte d’actif (nom)',
    'column_amount'                        => 'Montant',
    'column_amount_foreign'                => 'Amount (in foreign currency)',
    'column_amount_debit'                  => 'Montant (colonne débit)',
    'column_amount_credit'                 => 'Montant (colonne de crédit)',
    'column_amount-comma-separated'        => 'Amount (comma as decimal separator)',
    'column_bill-id'                       => 'Bill ID (matching FF3)',
    'column_bill-name'                     => 'Nom de la facture',
    'column_budget-id'                     => 'Budget ID (matching FF3)',
    'column_budget-name'                   => 'Nom du budget',
    'column_category-id'                   => 'Category ID (matching FF3)',
    'column_category-name'                 => 'Nom de catégorie',
    'column_currency-code'                 => 'Currency code (ISO 4217)',
    'column_foreign-currency-code'         => 'Foreign currency code (ISO 4217)',
    'column_currency-id'                   => 'Currency ID (matching FF3)',
    'column_currency-name'                 => 'Currency name (matching FF3)',
    'column_currency-symbol'               => 'Currency symbol (matching FF3)',
    'column_date-interest'                 => 'Interest calculation date',
    'column_date-book'                     => 'Transaction booking date',
    'column_date-process'                  => 'Transaction process date',
    'column_date-transaction'              => 'Date',
    'column_description'                   => 'Description',
    'column_opposing-iban'                 => 'Opposing account (IBAN)',
    'column_opposing-id'                   => 'Opposing account ID (matching FF3)',
    'column_external-id'                   => 'ID externe',
    'column_opposing-name'                 => 'Opposing account (name)',
    'column_rabo-debit-credit'             => 'Rabobank specific debit/credit indicator',
    'column_ing-debit-credit'              => 'ING specific debit/credit indicator',
    'column_sepa-ct-id'                    => 'SEPA Credit Transfer end-to-end ID',
    'column_sepa-ct-op'                    => 'SEPA Credit Transfer opposing account',
    'column_sepa-db'                       => 'SEPA Direct Debit',
    'column_tags-comma'                    => 'Tags (comma separated)',
    'column_tags-space'                    => 'Tags (space separated)',
    'column_account-number'                => 'Asset account (account number)',
    'column_opposing-number'               => 'Opposing account (account number)',
    'column_note'                          => 'Note(s)',

    // prerequisites
    'prerequisites'                        => 'Prerequisites',

    // bunq
    'bunq_prerequisites_title'             => 'Prerequisites for an import from bunq',
    'bunq_prerequisites_text'              => 'In order to import from bunq, you need to obtain an API key. You can do this through the app.',

    // Spectre
    'spectre_title'                        => 'Import using Spectre',
    'spectre_prerequisites_title'          => 'Prerequisites for an import using Spectre',
    'spectre_prerequisites_text'           => 'In order to import data using the Spectre API, you must provide Firefly III with two secret values. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'spectre_enter_pub_key'                => 'The import will only work when you enter this public key on your <a href="https://www.saltedge.com/clients/security/edit">security page</a>.',
    'spectre_accounts_title'               => 'Select accounts to import from',
    'spectre_accounts_text'                => 'Each account on the left below has been found by Spectre and can be imported into Firefly III. Please select the asset account that should hold any given transactions. If you do not wish to import from any particular account, remove the check from the checkbox.',
    'spectre_do_import'                    => 'Yes, import from this account',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Status',
    'spectre_extra_key_card_type'          => 'Card type',
    'spectre_extra_key_account_name'       => 'Account name',
    'spectre_extra_key_client_name'        => 'Client name',
    'spectre_extra_key_account_number'     => 'Account number',
    'spectre_extra_key_blocked_amount'     => 'Blocked amount',
    'spectre_extra_key_available_amount'   => 'Available amount',
    'spectre_extra_key_credit_limit'       => 'Credit limit',
    'spectre_extra_key_interest_rate'      => 'Interest rate',
    'spectre_extra_key_expiry_date'        => 'Expiry date',
    'spectre_extra_key_open_date'          => 'Open date',
    'spectre_extra_key_current_time'       => 'Current time',
    'spectre_extra_key_current_date'       => 'Current date',
    'spectre_extra_key_cards'              => 'Cards',
    'spectre_extra_key_units'              => 'Units',
    'spectre_extra_key_unit_price'         => 'Unit price',
    'spectre_extra_key_transactions_count' => 'Transaction count',

    // various other strings:
    'imported_from_account'                => 'Imported from ":account"',
];


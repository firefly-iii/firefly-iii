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
    'status_wait_title'               => 'Veuillez patienter...',
    'status_wait_text'                => 'Cette boîte disparaîtra dans un instant.',
    'status_fatal_title'              => 'Une erreur fatale est survenue',
    'status_fatal_text'               => 'Une erreur fatale est survenue que le traitement d\'importation ne peut pas récupérer. Voir l\'explication en rouge ci-dessous.',
    'status_fatal_more'               => 'Si l\'erreur est un time-out, l\'importation sera arrêtée pendant son traitement. Pour certaines configurations de serveur, ce n\'est que le serveur qui s\'est arrêté alors que l\'importation continue de fonctionner en arrière-plan. Pour vérifier cela, consultez les fichiers journaux. Si le problème persiste, envisagez d\'importer plutôt par ligne de commande.',
    'status_ready_title'              => 'L\'importation est prête à démarrer',
    'status_ready_text'               => 'L\'importation est prête à démarrer. Toute la configuration requise été effectuée. Vous pouvez téléchargez le fichier de configuration. Cela vous permettra de recommencer rapidement l\'importation si tout ne fonctionnait pas comme prévu. Pour exécuter l\'importation, vous pouvez soit exécuter la commande suivante dans la console du serveur, soit exécuter l\'importation depuis cette page web. Selon votre configuration générale, l\'importation via la console vous donnera plus de détails.',
    'status_ready_noconfig_text'      => 'L\'importation est prête à démarrer. Toute la configuration requise été effectuée. Pour exécuter l\'importation, vous pouvez soit exécuter la commande suivante dans la console du serveur, soit exécuter l\'importation depuis cette page web. Selon votre configuration générale, l\'importation via la console vous donnera plus de détails.',
    'status_ready_config'             => 'Télécharger la configuration',
    'status_ready_start'              => 'Démarrer l\'importation',
    'status_ready_share'              => 'Vous pouvez télécharger votre configuration et de la partager dans le <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">centre de configuration d\'import</a></strong>. Cela permettra à d\'autres utilisateurs de Firefly III d\'importer leurs fichiers plus facilement.',
    'status_job_new'                  => 'The job is brand new.',
    'status_job_configuring'          => 'The import is being configured.',
    'status_job_configured'           => 'The import is configured.',
    'status_job_running'              => 'L\'importation est en cours... Veuillez patienter...',
    'status_job_error'                => 'The job has generated an error.',
    'status_job_finished'             => 'The import has finished!',
    'status_running_title'            => 'L\'importation est en cours d\'exécution',
    'status_running_placeholder'      => 'Attendez pour une mise à jour...',
    'status_finished_title'           => 'Le traitement d\'importation est terminé',
    'status_finished_text'            => 'Le traitement d\'importation a importé vos données.',
    'status_errors_title'             => 'Erreurs lors de l\'importation',
    'status_errors_single'            => 'Une erreur est survenue lors de l\'importation. Cela ne semble pas être fatal.',
    'status_errors_multi'             => 'Certaines erreurs sont survenues lors de l\'importation. Celles-ci ne semblent pas être fatales.',
    'status_bread_crumb'              => 'Statut d\'importation',
    'status_sub_title'                => 'Statut d\'importation',
    'config_sub_title'                => 'Configurez votre importation',
    'status_finished_job'             => 'Les transactions importées peuvent être trouvées avec le mot-clé <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    'import_with_key'                 => 'Importer avec la clé \': key\'',

    // file: upload something:
    'file_upload_title'               => 'Configuration de l\'importation (1/4) - Téléchargez votre fichier',
    'file_upload_text'                => 'Ce traitement vous aidera à importer des fichiers de votre banque dans Firefly III. Consultez les pages d\'aide en haut à droite.',
    'file_upload_fields'              => 'Champs',
    'file_upload_help'                => 'Sélectionnez votre fichier',
    'file_upload_config_help'         => 'Si vous avez précédemment importé des données dans Firefly III, vous avez peut-être téléchargé un fichier de configuration qui définit les relations entre les différents champs. Pour certaines banques, des utilisateurs ont bien voulu partager leur fichier ici : <a href="https://github.com/firefly-iii/import-configurations/wiki">fichiers de configuration</a>.',
    'file_upload_type_help'           => 'Sélectionnez le type de fichier que vous allez télécharger',
    'file_upload_submit'              => 'Envoyer des fichiers',

    // file: upload types
    'import_file_type_csv'            => 'CSV (valeurs séparées par des virgules)',

    // file: initial config for CSV
    'csv_initial_title'               => 'Configuration d\'importation (2/4) - Configuration d\'importation CSV',
    'csv_initial_text'                => 'To be able to import your file correctly, please validate the options below.',
    'csv_initial_box'                 => 'Basic CSV import setup',
    'csv_initial_box_title'           => 'Basic CSV import setup options',
    'csv_initial_header_help'         => 'Check this box if the first row of your CSV file are the column titles.',
    'csv_initial_date_help'           => 'Date time format in your CSV. Follow the format like <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'csv_initial_delimiter_help'      => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'csv_initial_import_account_help' => 'If your CSV file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the CSV belong to.',
    'csv_initial_submit'              => 'Continue with step 3/4',

    // file: new options:
    'file_apply_rules_title'          => 'Apply rules',
    'file_apply_rules_description'    => 'Apply your rules. Note that this slows the import significantly.',
    'file_match_bills_title'          => 'Match bills',
    'file_match_bills_description'    => 'Match your bills to newly created withdrawals. Note that this slows the import significantly.',

    // file: roles config
    'csv_roles_title'                 => 'Import setup (3/4) - Define each column\'s role',
    'csv_roles_text'                  => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'csv_roles_table'                 => 'Table',
    'csv_roles_column_name'           => 'Name of column',
    'csv_roles_column_example'        => 'Column example data',
    'csv_roles_column_role'           => 'Column data meaning',
    'csv_roles_do_map_value'          => 'Map these values',
    'csv_roles_column'                => 'Column',
    'csv_roles_no_example_data'       => 'No example data available',
    'csv_roles_submit'                => 'Continue with step 4/4',
    'csv_roles_warning'               => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',

    // file: map data
    'file_map_title'                  => 'Import setup (4/4) - Connect import data to Firefly III data',
    'file_map_text'                   => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'file_map_field_value'            => 'Field value',
    'file_map_field_mapped_to'        => 'Mapped to',
    'map_do_not_map'                  => '(do not map)',
    'file_map_submit'                 => 'Start the import',

    // map things.
    'column__ignore'                  => '(ignore this column)',
    'column_account-iban'             => 'Asset account (IBAN)',
    'column_account-id'               => 'Asset account  ID (matching Firefly)',
    'column_account-name'             => 'Asset account (name)',
    'column_amount'                   => 'Amount',
    'column_amount_debit'             => 'Amount (debit column)',
    'column_amount_credit'            => 'Amount (credit column)',
    'column_amount-comma-separated'   => 'Amount (comma as decimal separator)',
    'column_bill-id'                  => 'Bill ID (matching Firefly)',
    'column_bill-name'                => 'Bill name',
    'column_budget-id'                => 'Budget ID (matching Firefly)',
    'column_budget-name'              => 'Budget name',
    'column_category-id'              => 'Category ID (matching Firefly)',
    'column_category-name'            => 'Category name',
    'column_currency-code'            => 'Currency code (ISO 4217)',
    'column_currency-id'              => 'Currency ID (matching Firefly)',
    'column_currency-name'            => 'Currency name (matching Firefly)',
    'column_currency-symbol'          => 'Currency symbol (matching Firefly)',
    'column_date-interest'            => 'Interest calculation date',
    'column_date-book'                => 'Transaction booking date',
    'column_date-process'             => 'Transaction process date',
    'column_date-transaction'         => 'Date',
    'column_description'              => 'Description',
    'column_opposing-iban'            => 'Opposing account (IBAN)',
    'column_opposing-id'              => 'Opposing account ID (matching Firefly)',
    'column_external-id'              => 'External ID',
    'column_opposing-name'            => 'Opposing account (name)',
    'column_rabo-debit-credit'        => 'Rabobank specific debit/credit indicator',
    'column_ing-debit-credit'         => 'ING specific debit/credit indicator',
    'column_sepa-ct-id'               => 'SEPA Credit Transfer end-to-end ID',
    'column_sepa-ct-op'               => 'SEPA Credit Transfer opposing account',
    'column_sepa-db'                  => 'SEPA Direct Debit',
    'column_tags-comma'               => 'Tags (comma separated)',
    'column_tags-space'               => 'Tags (space separated)',
    'column_account-number'           => 'Asset account (account number)',
    'column_opposing-number'          => 'Opposing account (account number)',
    'column_note'                     => 'Note(s)',

    // bunq
    'bunq_prerequisites_title'        => 'Prerequisites for an import from bunq',
    'bunq_prerequisites_text'         => 'In order to import from bunq, you need to obtain an API key. You can do this through the app.',

    // Spectre:
    'spectre_title'                   => 'Import using Spectre',
    'spectre_prerequisites_title'     => 'Prerequisites for an import using Spectre',
    'spectre_prerequisites_text'      => 'In order to import data using the Spectre API, you need to prove some secrets. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'spectre_enter_pub_key'           => 'The import will only work when you enter this public key on your <a href="https://www.saltedge.com/clients/security/edit">security page</a>.',
    'spectre_select_country_title'    => 'Select a country',
    'spectre_select_country_text'     => 'Firefly III has a large selection of banks and sites from which Spectre can download transactional data. These banks are sorted by country. Please not that there is a "Fake Country" for when you wish to test something. If you wish to import from other financial tools, please use the imaginary country called "Other financial applications". By default, Spectre only allows you to download data from fake banks. Make sure your status is "Live" on your <a href="https://www.saltedge.com/clients/dashboard">Dashboard</a> if you wish to download from real banks.',
    'spectre_select_provider_title'   => 'Select a bank',
    'spectre_select_provider_text'    => 'Spectre supports the following banks or financial services grouped under <em>:country</em>. Please pick the one you wish to import from.',
    'spectre_input_fields_title'      => 'Input mandatory fields',
    'spectre_input_fields_text'       => 'The following fields are mandated by ":provider" (from :country).',
    'spectre_instructions_english'    => 'These instructions are provided by Spectre for your convencience. They are in English:',
];

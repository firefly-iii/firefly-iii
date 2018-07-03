<?php

/**
 * import.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
    // ALL breadcrumbs and subtitles:
    'index_breadcrumb'                     => 'Importer des données dans Firefly III',
    'prerequisites_breadcrumb_fake'        => 'Prérequis pour la simulation d\'importation',
    'prerequisites_breadcrumb_spectre'     => 'Prérequis pour Spectre',
    'prerequisites_breadcrumb_bunq'        => 'Prérequis pour bunq',
    'job_configuration_breadcrumb'         => 'Configuration pour ":key"',
    'job_status_breadcrumb'                => 'Statut d\'importation pour ":key"',
    'cannot_create_for_provider'           => 'Firefly III ne peut pas créer de tâche pour le fournisseur ":provider".',

    // index page:
    'general_index_title'                  => 'Importer un fichier',
    'general_index_intro'                  => 'Bienvenue dans la routine d\'importation de Firefly III. Il existe différentes façons d\'importer des données dans Firefly III, affichées ici sous forme de boutons.',
    // import provider strings (index):
    'button_fake'                          => 'Simuler une importation',
    'button_file'                          => 'Importer un fichier',
    'button_bunq'                          => 'Importer depuis bunq',
    'button_spectre'                       => 'Importer en utilisant Spectre',
    'button_plaid'                         => 'Importer en utilisant Plaid',
    'button_yodlee'                        => 'Importer en utilisant Yodlee',
    'button_quovo'                         => 'Importer en utilisant Quovo',
    // global config box (index)
    'global_config_title'                  => 'Configuration d\'importation globale',
    'global_config_text'                   => 'À l\'avenir, cette boîte contiendra les préférences qui s\'appliquent à TOUTES les sources d\'importation ci-dessus.',
    // prerequisites box (index)
    'need_prereq_title'                    => 'Prérequis d\'importation',
    'need_prereq_intro'                    => 'Certaines méthodes d\'importation nécessitent votre attention avant de pouvoir être utilisées. Par exemple, elles peuvent nécessiter des clés d\'API spéciales ou des clés secrètes. Vous pouvez les configurer ici. L\'icône indique si ces conditions préalables ont été remplies.',
    'do_prereq_fake'                       => 'Prérequis pour la simulation',
    'do_prereq_file'                       => 'Prérequis pour les importations de fichiers',
    'do_prereq_bunq'                       => 'Prerequisites for imports from bunq',
    'do_prereq_spectre'                    => 'Prerequisites for imports using Spectre',
    'do_prereq_plaid'                      => 'Prerequisites for imports using Plaid',
    'do_prereq_yodlee'                     => 'Prerequisites for imports using Yodlee',
    'do_prereq_quovo'                      => 'Prerequisites for imports using Quovo',
    // provider config box (index)
    'can_config_title'                     => 'Import configuration',
    'can_config_intro'                     => 'Some import methods can be configured to your liking. They have extra settings you can tweak.',
    'do_config_fake'                       => 'Configuration for the fake provider',
    'do_config_file'                       => 'Configuration for file imports',
    'do_config_bunq'                       => 'Configuration for bunq imports',
    'do_config_spectre'                    => 'Configuration for imports from Spectre',
    'do_config_plaid'                      => 'Configuration for imports from Plaid',
    'do_config_yodlee'                     => 'Configuration for imports from Yodlee',
    'do_config_quovo'                      => 'Configuration for imports from Quovo',

    // prerequisites:
    'prereq_fake_title'                    => 'Prerequisites for an import from the fake import provider',
    'prereq_fake_text'                     => 'This fake provider requires a fake API key. It must be 32 characters long. You can use this one: 123456789012345678901234567890AA',
    'prereq_spectre_title'                 => 'Prerequisites for an import using the Spectre API',
    'prereq_spectre_text'                  => 'In order to import data using the Spectre API (v4), you must provide Firefly III with two secret values. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_spectre_pub'                   => 'Likewise, the Spectre API needs to know the public key you see below. Without it, it will not recognize you. Please enter this public key on your <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_bunq_title'                    => 'Prerequisites for an import from bunq',
    'prereq_bunq_text'                     => 'In order to import from bunq, you need to obtain an API key. You can do this through the app. Please note that the import function for bunq is in BETA. It has only been tested against the sandbox API.',
    'prereq_bunq_ip'                       => 'bunq requires your externally facing IP address. Firefly III has tried to fill this in using <a href="https://www.ipify.org/">the ipify service</a>. Make sure this IP address is correct, or the import will fail.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'         => 'Fake API key stored successfully!',
    'prerequisites_saved_for_spectre'      => 'App ID and secret stored!',
    'prerequisites_saved_for_bunq'         => 'API key and IP stored!',

    // job configuration:
    'job_config_apply_rules_title'         => 'Job configuration - apply your rules?',
    'job_config_apply_rules_text'          => 'Once the fake provider has run, your rules can be applied to the transactions. This adds time to the import.',
    'job_config_input'                     => 'Your input',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'         => 'Enter album name',
    'job_config_fake_artist_text'          => 'Many import routines have a few configuration steps you must go through. In the case of the fake import provider, you must answer some weird questions. In this case, enter "David Bowie" to continue.',
    'job_config_fake_song_title'           => 'Enter song name',
    'job_config_fake_song_text'            => 'Mention the song "Golden years" to continue with the fake import.',
    'job_config_fake_album_title'          => 'Enter album name',
    'job_config_fake_album_text'           => 'Some import routines require extra data halfway through the import. In the case of the fake import provider, you must answer some weird questions. Enter "Station to station" to continue.',
    // job configuration form the file provider
    'job_config_file_upload_title'         => 'Import setup (1/4) - Upload your file',
    'job_config_file_upload_text'          => 'This routine will help you import files from your bank into Firefly III. ',
    'job_config_file_upload_help'          => 'Select your file. Please make sure the file is UTF-8 encoded.',
    'job_config_file_upload_config_help'   => 'If you have previously imported data into Firefly III, you may have a configuration file, which will pre-set configuration values for you. For some banks, other users have kindly provided their <a href="https://github.com/firefly-iii/import-configurations/wiki">configuration file</a>',
    'job_config_file_upload_type_help'     => 'Select the type of file you will upload',
    'job_config_file_upload_submit'        => 'Upload files',
    'import_file_type_csv'                 => 'CSV (valeurs séparées par des virgules)',
    'file_not_utf8'                        => 'The file you have uploaded is not encoded as UTF-8 or ASCII. Firefly III cannot handle such files. Please use Notepad++ or Sublime to convert your file to UTF-8.',
    'job_config_uc_title'                  => 'Import setup (2/4) - Basic file setup',
    'job_config_uc_text'                   => 'To be able to import your file correctly, please validate the options below.',
    'job_config_uc_header_help'            => 'Check this box if the first row of your CSV file are the column titles.',
    'job_config_uc_date_help'              => 'Date time format in your file. Follow the format as <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'job_config_uc_delimiter_help'         => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'job_config_uc_account_help'           => 'If your file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the file belong to.',
    'job_config_uc_apply_rules_title'      => 'Apply rules',
    'job_config_uc_apply_rules_text'       => 'Applies your rules to every imported transaction. Note that this slows the import significantly.',
    'job_config_uc_specifics_title'        => 'Bank-specific options',
    'job_config_uc_specifics_txt'          => 'Some banks deliver badly formatted files. Firefly III can fix those automatically. If your bank delivers such files but it\'s not listed here, please open an issue on GitHub.',
    'job_config_uc_submit'                 => 'Continue',
    'invalid_import_account'               => 'You have selected an invalid account to import into.',
    // job configuration for Spectre:
    'job_config_spectre_login_title'       => 'Choose your login',
    'job_config_spectre_login_text'        => 'Firefly III has found :count existing login(s) in your Spectre account. Which one would you like to use to import from?',
    'spectre_login_status_active'          => 'Active',
    'spectre_login_status_inactive'        => 'Inactive',
    'spectre_login_status_disabled'        => 'Disabled',
    'spectre_login_new_login'              => 'Login with another bank, or one of these banks with different credentials.',
    'job_config_spectre_accounts_title'    => 'Select accounts to import from',
    'job_config_spectre_accounts_text'     => 'You have selected ":name" (:country). You have :count account(s) available from this provider. Please select the Firefly III asset account(s) where the transactions from these accounts should be stored. Remember, in order to import data both the Firefly III account and the ":name"-account must have the same currency.',
    'spectre_no_supported_accounts'        => 'You cannot import from this account due to a currency mismatch.',
    'spectre_do_not_import'                => '(do not import)',
    'spectre_no_mapping'                   => 'It seems you have not selected any accounts to import from.',
    'imported_from_account'                => 'Imported from ":account"',
    'spectre_account_with_number'          => 'Account :number',
    'job_config_spectre_apply_rules'       => 'Apply rules',
    'job_config_spectre_apply_rules_text'  => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',
    // job configuration for bunq:
    'job_config_bunq_accounts_title'       => 'bunq accounts',
    'job_config_bunq_accounts_text'        => 'These are the accounts associated with your bunq account. Please select the accounts from which you want to import, and in which account the transactions must be imported.',
    'bunq_no_mapping'                      => 'It seems you have not selected any accounts.',
    'should_download_config'               => 'You should download <a href=":route">the configuration file</a> for this job. This will make future imports way easier.',
    'share_config_file'                    => 'If you have imported data from a public bank, you should <a href="https://github.com/firefly-iii/import-configurations/wiki">share your configuration file</a> so it will be easy for other users to import their data. Sharing your configuration file will not expose your financial details.',
    'job_config_bunq_apply_rules'          => 'Apply rules',
    'job_config_bunq_apply_rules_text'     => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',
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

    // specifics:
    'specific_ing_name'                    => 'ING NL',
    'specific_ing_descr'                   => 'Create better descriptions in ING exports',
    'specific_sns_name'                    => 'SNS / Volksbank NL',
    'specific_sns_descr'                   => 'Trim quotes from SNS / Volksbank export files',
    'specific_abn_name'                    => 'ABN AMRO NL',
    'specific_abn_descr'                   => 'Fixes potential problems with ABN AMRO files',
    'specific_rabo_name'                   => 'Rabobank NL',
    'specific_rabo_descr'                  => 'Fixes potential problems with Rabobank files',
    'specific_pres_name'                   => 'President\'s Choice Financial CA',
    'specific_pres_descr'                  => 'Fixes potential problems with PC files',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'               => 'Import setup (3/4) - Define each column\'s role',
    'job_config_roles_text'                => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'job_config_roles_submit'              => 'Continue',
    'job_config_roles_column_name'         => 'Name of column',
    'job_config_roles_column_example'      => 'Column example data',
    'job_config_roles_column_role'         => 'Column data meaning',
    'job_config_roles_do_map_value'        => 'Map these values',
    'job_config_roles_no_example'          => 'No example data available',
    'job_config_roles_fa_warning'          => 'If you mark a column as containing an amount in a foreign currency, you must also set the column that contains which currency it is.',
    'job_config_roles_rwarning'            => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',
    'job_config_roles_colum_count'         => 'Column',
    // job config for the file provider (stage: mapping):
    'job_config_map_title'                 => 'Import setup (4/4) - Connect import data to Firefly III data',
    'job_config_map_text'                  => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'job_config_map_nothing'               => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',
    'job_config_field_value'               => 'Field value',
    'job_config_field_mapped'              => 'Mapped to',
    'map_do_not_map'                       => '(ne pas mapper)',
    'job_config_map_submit'                => 'Start the import',


    // import status page:
    'import_with_key'                      => 'Importer avec la clé \':key\'',
    'status_wait_title'                    => 'Veuillez patienter...',
    'status_wait_text'                     => 'Cette boîte disparaîtra dans un instant.',
    'status_running_title'                 => 'L\'importation est en cours d\'exécution',
    'status_job_running'                   => 'Please wait, running the import...',
    'status_job_storing'                   => 'Please wait, storing data...',
    'status_job_rules'                     => 'Please wait, running rules...',
    'status_fatal_title'                   => 'Fatal error',
    'status_fatal_text'                    => 'The import has suffered from an error it could not recover from. Apologies!',
    'status_fatal_more'                    => 'This (possibly very cryptic) error message is complemented by log files, which you can find on your hard drive, or in the Docker container where you run Firefly III from.',
    'status_finished_title'                => 'Import finished',
    'status_finished_text'                 => 'The import has finished.',
    'finished_with_errors'                 => 'There were some errors during the import. Please review them carefully.',
    'unknown_import_result'                => 'Unknown import result',
    'result_no_transactions'               => 'No transactions have been imported. Perhaps they were all duplicates is simply no transactions where present to be imported. Perhaps the log files can tell you what happened. If you import data regularly, this is normal.',
    'result_one_transaction'               => 'Une seule transaction a été importée. Elle est stockée sous le tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> où vous pouvez l\'afficher en détail.',
    'result_many_transactions'             => 'Firefly III a importé :count transactions. Elles sont stockées sous le tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> où vous pouvez les afficher en détail.',


    // general errors and warnings:
    'bad_job_status'                       => 'Vous ne pouvez pas accéder à cette page tant que l\'importation a le statut ":status".',

    // column roles for CSV import:
    'column__ignore'                       => '(ignorer cette colonne)',
    'column_account-iban'                  => 'Compte d’actif (IBAN)',
    'column_account-id'                    => 'Compte d\'actif (ID correspondant à FF3)',
    'column_account-name'                  => 'Compte d’actif (nom)',
    'column_amount'                        => 'Montant',
    'column_amount_foreign'                => 'Montant (en devise étrangère)',
    'column_amount_debit'                  => 'Montant (colonne débit)',
    'column_amount_credit'                 => 'Montant (colonne de crédit)',
    'column_amount-comma-separated'        => 'Montant (virgule comme séparateur décimal)',
    'column_bill-id'                       => 'Facture (ID correspondant à FF3)',
    'column_bill-name'                     => 'Nom de la facture',
    'column_budget-id'                     => 'Budget (ID correspondant à FF3)',
    'column_budget-name'                   => 'Nom du budget',
    'column_category-id'                   => 'Catégorie (ID correspondant à FF3)',
    'column_category-name'                 => 'Nom de catégorie',
    'column_currency-code'                 => 'Code de la devise (ISO 4217)',
    'column_foreign-currency-code'         => 'Code de devise étrangère (ISO 4217)',
    'column_currency-id'                   => 'Devise (ID correspondant à FF3)',
    'column_currency-name'                 => 'Nom de la devise (correspondant à FF3)',
    'column_currency-symbol'               => 'Symbole de la devise (correspondant à FF3)',
    'column_date-interest'                 => 'Date de calcul des intérêts',
    'column_date-book'                     => 'Date d\'enregistrement de la transaction',
    'column_date-process'                  => 'Date de traitement de la transaction',
    'column_date-transaction'              => 'Date',
    'column_date-due'                      => 'Date d\'échéance de la transaction',
    'column_date-payment'                  => 'Date de paiement de la transaction',
    'column_date-invoice'                  => 'Date de facturation de la transaction',
    'column_description'                   => 'Description',
    'column_opposing-iban'                 => 'Compte destinataire (IBAN)',
    'column_opposing-bic'                  => 'Compte destinataire (BIC)',
    'column_opposing-id'                   => 'Compte destinataire (ID correspondant à FF3)',
    'column_external-id'                   => 'ID externe',
    'column_opposing-name'                 => 'Compte destinataire (nom)',
    'column_rabo-debit-credit'             => 'Indicateur de débit/crédit spécifique à Rabobank',
    'column_ing-debit-credit'              => 'Indicateur de débit/crédit spécifique à ING',
    'column_sepa-ct-id'                    => 'Référence de bout en bout SEPA',
    'column_sepa-ct-op'                    => 'Référence SEPA du compte destinataire',
    'column_sepa-db'                       => 'Référence Unique de Mandat SEPA',
    'column_sepa-cc'                       => 'Code de rapprochement SEPA',
    'column_sepa-ci'                       => 'Identifiant Créancier SEPA',
    'column_sepa-ep'                       => 'Objectif externe SEPA',
    'column_sepa-country'                  => 'Code de pays SEPA',
    'column_tags-comma'                    => 'Tags (séparés par des virgules)',
    'column_tags-space'                    => 'Tags (séparé par un espace)',
    'column_account-number'                => 'Compte d’actif (numéro de compte)',
    'column_opposing-number'               => 'Compte destinataire (numéro de compte)',
    'column_note'                          => 'Note(s)',
    'column_internal-reference'            => 'Référence interne',

];

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
    'index_breadcrumb'                   => 'Import data into Firefly III',
    'prerequisites_breadcrumb_fake'      => 'Prerequisites for the fake import provider',
    'job_configuration_breadcrumb'       => 'Configuration for ":key"',
    'job_status_breadcrumb'              => 'Import status for ":key"',

    // index page:
    'general_index_title'                => 'Import a file',
    'general_index_intro'                => 'Welcome to Firefly III\'s import routine. There are a few ways of importing data into Firefly III, displayed here as buttons.',
    // import provider strings (index):
    'button_fake'                        => 'Fake an import',
    'button_file'                        => 'Import a file',
    'button_bunq'                        => 'Import from bunq',
    'button_spectre'                     => 'Import using Spectre',
    'button_plaid'                       => 'Import using Plaid',
    'button_yodlee'                      => 'Import using Yodlee',
    'button_quovo'                       => 'Import using Quovo',
    // global config box (index)
    'global_config_title'                => 'Global import configuration',
    'global_config_text'                 => 'In the future, this box will feature preferences that apply to ALL import providers above.',
    // prerequisites box (index)
    'need_prereq_title'                  => 'Import prerequisites',
    'need_prereq_intro'                  => 'Some import methods need your attention before they can be used. For example, they might require special API keys or application secrets. You can configure them here. The icon indicates if these prerequisites have been met.',
    'do_prereq_fake'                     => 'Prerequisites for the fake provider',
    'do_prereq_file'                     => 'Prerequisites for file imports',
    'do_prereq_bunq'                     => 'Prerequisites for imports from bunq',
    'do_prereq_spectre'                  => 'Prerequisites for imports using Spectre',
    'do_prereq_plaid'                    => 'Prerequisites for imports using Plaid',
    'do_prereq_yodlee'                   => 'Prerequisites for imports using Yodlee',
    'do_prereq_quovo'                    => 'Prerequisites for imports using Quovo',
    // provider config box (index)
    'can_config_title'                   => 'Import configuration',
    'can_config_intro'                   => 'Some import methods can be configured to your liking. They have extra settings you can tweak.',
    'do_config_fake'                     => 'Configuration for the fake provider',
    'do_config_file'                     => 'Configuration for file imports',
    'do_config_bunq'                     => 'Configuration for bunq imports',
    'do_config_spectre'                  => 'Configuration for imports from Spectre',
    'do_config_plaid'                    => 'Configuration for imports from Plaid',
    'do_config_yodlee'                   => 'Configuration for imports from Yodlee',
    'do_config_quovo'                    => 'Configuration for imports from Quovo',

    // prerequisites:
    'prereq_fake_title'                  => 'Prerequisites for an import from the fake import provider',
    'prereq_fake_text'                   => 'This fake provider requires a fake API key. It must be 32 characters long. You can use this one: 123456789012345678901234567890AA',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'       => 'Fake API key stored successfully!',

    // job configuration:
    'job_config_apply_rules_title'       => 'Job configuration - apply your rules?',
    'job_config_apply_rules_text'        => 'Once the fake provider has run, your rules can be applied to the transactions. This adds time to the import.',
    'job_config_input'                   => 'Your input',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'       => 'Enter album name',
    'job_config_fake_artist_text'        => 'Many import routines have a few configuration steps you must go through. In the case of the fake import provider, you must answer some weird questions. In this case, enter "David Bowie" to continue.',
    'job_config_fake_song_title'         => 'Enter song name',
    'job_config_fake_song_text'          => 'Mention the song "Golden years" to continue with the fake import.',
    'job_config_fake_album_title'        => 'Enter album name',
    'job_config_fake_album_text'         => 'Some import routines require extra data halfway through the import. In the case of the fake import provider, you must answer some weird questions. Enter "Station to station" to continue.',
    // job configuration form the file provider
    'job_config_file_upload_title'       => 'Import setup (1/4) - Upload your file',
    'job_config_file_upload_text'        => 'This routine will help you import files from your bank into Firefly III. ',
    'job_config_file_upload_help'        => 'Select your file. Please make sure the file is UTF-8 encoded.',
    'job_config_file_upload_config_help' => 'If you have previously imported data into Firefly III, you may have a configuration file, which will pre-set configuration values for you. For some banks, other users have kindly provided their <a href="https://github.com/firefly-iii/import-configurations/wiki">configuration file</a>',
    'job_config_file_upload_type_help'   => 'Select the type of file you will upload',
    'job_config_file_upload_submit'      => 'Upload files',
    'import_file_type_csv'               => 'CSV (comma separated values)',
    'file_not_utf8'                      => 'The file you have uploaded is not encoded as UTF-8 or ASCII. Firefly III cannot handle such files. Please use Notepad++ or Sublime to convert your file to UTF-8.',
    'job_config_uc_title'                => 'Import setup (2/4) - Basic file setup',
    'job_config_uc_text'                 => 'To be able to import your file correctly, please validate the options below.',
    'job_config_uc_header_help'          => 'Check this box if the first row of your CSV file are the column titles.',
    'job_config_uc_date_help'            => 'Date time format in your file. Follow the format as <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'job_config_uc_delimiter_help'       => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'job_config_uc_account_help'         => 'If your file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the file belong to.',
    'job_config_uc_apply_rules_title'    => 'Apply rules',
    'job_config_uc_apply_rules_text'     => 'Applies your rules to every imported transaction. Note that this slows the import significantly.',
    'job_config_uc_specifics_title'      => 'Bank-specific options',
    'job_config_uc_specifics_txt'        => 'Some banks deliver badly formatted files. Firefly III can fix those automatically. If your bank delivers such files but it\'s not listed here, please open an issue on GitHub.',
    'job_config_uc_submit'               => 'Continue',
    'invalid_import_account'             => 'You have selected an invalid account to import into.',
    // specifics:
    'specific_ing_name'                  => 'ING NL',
    'specific_ing_descr'                 => 'Create better descriptions in ING exports',
    'specific_sns_name'                  => 'SNS / Volksbank NL',
    'specific_sns_descr'                 => 'Trim quotes from SNS / Volksbank export files',
    'specific_abn_name'                  => 'ABN AMRO NL',
    'specific_abn_descr'                 => 'Fixes potential problems with ABN AMRO files',
    'specific_rabo_name'                 => 'Rabobank NL',
    'specific_rabo_descr'                => 'Fixes potential problems with Rabobank files',
    'specific_pres_name'                 => 'President\'s Choice Financial CA',
    'specific_pres_descr'                => 'Fixes potential problems with PC files',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'             => 'Import setup (3/4) - Define each column\'s role',
    'job_config_roles_text'              => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'job_config_roles_submit'            => 'Continue',
    'job_config_roles_column_name'       => 'Name of column',
    'job_config_roles_column_example'    => 'Column example data',
    'job_config_roles_column_role'       => 'Column data meaning',
    'job_config_roles_do_map_value'      => 'Map these values',
    'job_config_roles_no_example'        => 'No example data available',
    'job_config_roles_fa_warning'        => 'If you mark a column as containing an amount in a foreign currency, you must also set the column that contains which currency it is.',
    'job_config_roles_rwarning'          => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',
    'job_config_roles_colum_count'       => 'Column',
    // job config for the file provider (stage: mapping):
    'job_config_map_title'               => 'Import setup (4/4) - Connect import data to Firefly III data',
    'job_config_map_text'                => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'job_config_map_nothing'             => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',
    'job_config_field_value'             => 'Field value',
    'job_config_field_mapped'            => 'Mapped to',
    'map_do_not_map'                     => '(do not map)',
    'job_config_map_submit'                  => 'Start the import',


    // import status page:
    'import_with_key'                    => 'Import with key \':key\'',
    'status_wait_title'                  => 'Please hold...',
    'status_wait_text'                   => 'This box will disappear in a moment.',
    'status_running_title'               => 'The import is running',
    'status_job_running'                 => 'Please wait, running the import...',
    'status_job_storing'                 => 'Please wait, storing data...',
    'status_job_rules'                   => 'Please wait, running rules...',
    'status_fatal_title'                 => 'Fatal error',
    'status_fatal_text'                  => 'The import has suffered from an error it could not recover from. Apologies!',
    'status_fatal_more'                  => 'This (possibly very cryptic) error message is complemented by log files, which you can find on your hard drive, or in the Docker container where you run Firefly III from.',
    'status_finished_title'              => 'Import finished',
    'status_finished_text'               => 'The import has finished.',
    'finished_with_errors'               => 'There were some errors during the import. Please review them carefully.',
    'unknown_import_result'              => 'Unknown import result',
    'result_no_transactions'             => 'No transactions have been imported. Perhaps they were all duplicates is simply no transactions where present to be imported. Perhaps the error message below can tell you what happened.',
    'result_one_transaction'             => 'Exactly one transaction has been imported. It is stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect it further.',
    'result_many_transactions'           => 'Firefly III has imported :count transactions. They are stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect them further.',


    // general errors and warnings:
    'bad_job_status'                     => 'To access this page, your import job cannot have status ":status".',

    // column roles for CSV import:
    'column__ignore'                     => '(ignore this column)',
    'column_account-iban'                => 'Asset account (IBAN)',
    'column_account-id'                  => 'Asset account ID (matching FF3)',
    'column_account-name'                => 'Asset account (name)',
    'column_amount'                      => 'Amount',
    'column_amount_foreign'              => 'Amount (in foreign currency)',
    'column_amount_debit'                => 'Amount (debit column)',
    'column_amount_credit'               => 'Amount (credit column)',
    'column_amount-comma-separated'      => 'Amount (comma as decimal separator)',
    'column_bill-id'                     => 'Bill ID (matching FF3)',
    'column_bill-name'                   => 'Bill name',
    'column_budget-id'                   => 'Budget ID (matching FF3)',
    'column_budget-name'                 => 'Budget name',
    'column_category-id'                 => 'Category ID (matching FF3)',
    'column_category-name'               => 'Category name',
    'column_currency-code'               => 'Currency code (ISO 4217)',
    'column_foreign-currency-code'       => 'Foreign currency code (ISO 4217)',
    'column_currency-id'                 => 'Currency ID (matching FF3)',
    'column_currency-name'               => 'Currency name (matching FF3)',
    'column_currency-symbol'             => 'Currency symbol (matching FF3)',
    'column_date-interest'               => 'Interest calculation date',
    'column_date-book'                   => 'Transaction booking date',
    'column_date-process'                => 'Transaction process date',
    'column_date-transaction'            => 'Date',
    'column_date-due'                    => 'Transaction due date',
    'column_date-payment'                => 'Transaction payment date',
    'column_date-invoice'                => 'Transaction invoice date',
    'column_description'                 => 'Description',
    'column_opposing-iban'               => 'Opposing account (IBAN)',
    'column_opposing-bic'                => 'Opposing account (BIC)',
    'column_opposing-id'                 => 'Opposing account ID (matching FF3)',
    'column_external-id'                 => 'External ID',
    'column_opposing-name'               => 'Opposing account (name)',
    'column_rabo-debit-credit'           => 'Rabobank specific debit/credit indicator',
    'column_ing-debit-credit'            => 'ING specific debit/credit indicator',
    'column_sepa-ct-id'                  => 'SEPA end-to-end Identifier',
    'column_sepa-ct-op'                  => 'SEPA Opposing Account Identifier',
    'column_sepa-db'                     => 'SEPA Mandate Identifier',
    'column_sepa-cc'                     => 'SEPA Clearing Code',
    'column_sepa-ci'                     => 'SEPA Creditor Identifier',
    'column_sepa-ep'                     => 'SEPA External Purpose',
    'column_sepa-country'                => 'SEPA Country Code',
    'column_tags-comma'                  => 'Tags (comma separated)',
    'column_tags-space'                  => 'Tags (space separated)',
    'column_account-number'              => 'Asset account (account number)',
    'column_opposing-number'             => 'Opposing account (account number)',
    'column_note'                        => 'Note(s)',
    'column_internal-reference'          => 'Internal reference',

    // status of import:
    //    'status_wait_title'          => 'Please hold...',
    //    'status_wait_text'           => 'This box will disappear in a moment.',
    //    'status_fatal_title'         => 'A fatal error occurred',
    //    'status_fatal_text'          => 'A fatal error occurred, which the import-routine cannot recover from. Please see the explanation in red below.',
    //    'status_fatal_more'          => 'If the error is a time-out, the import will have stopped half-way. For some server configurations, it is merely the server that stopped while the import keeps running in the background. To verify this, check out the log files. If the problem persists, consider importing over the command line instead.',
    //    'status_ready_title'         => 'Import is ready to start',
    //    'status_ready_text'          => 'The import is ready to start. All the configuration you needed to do has been done. Please download the configuration file. It will help you with the import should it not go as planned. To actually run the import, you can either execute the following command in your console, or run the web-based import. Depending on your configuration, the console import will give you more feedback.',
    //    'status_ready_noconfig_text' => 'The import is ready to start. All the configuration you needed to do has been done. To actually run the import, you can either execute the following command in your console, or run the web-based import. Depending on your configuration, the console import will give you more feedback.',
    //    'status_ready_config'        => 'Download configuration',
    //    'status_ready_start'         => 'Start the import',
    //    'status_ready_share'         => 'Please consider downloading your configuration and sharing it at the <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">import configuration center</a></strong>. This will allow other users of Firefly III to import their files more easily.',
    //    'status_job_new'             => 'The job is brand new.',
    //    'status_job_configuring'     => 'The import is being configured.',
    //    'status_job_configured'      => 'The import is configured.',
    //    'status_job_running'         => 'The import is running.. Please wait..',
    //    'status_job_storing'         => 'The import is storing your data.. Please wait..',
    //    'status_job_error'           => 'The job has generated an error.',
    //    'status_job_finished'        => 'The import has finished!',
    //    'status_running_title'       => 'The import is running',
    //    'status_running_placeholder' => 'Please hold for an update...',
    //    'status_finished_title'      => 'Import routine finished',
    //    'status_finished_text'       => 'The import routine has imported your data.',
    //    'status_errors_title'        => 'Errors during the import',
    //    'status_errors_single'       => 'An error has occurred during the import. It does not appear to be fatal.',
    //    'status_errors_multi'        => 'Some errors occurred during the import. These do not appear to be fatal.',
    //    'status_with_count'          => 'One transaction has been imported|:count transactions have been imported.',
    //    'job_status_breadcrumb'      => 'Import job state',
    //
    //    'status_bread_crumb'                   => 'Import status',
    //    'status_sub_title'                     => 'Import status',
    //    'config_sub_title'                     => 'Set up your import',
    //    'import_config_bread_crumb'            => 'Import configuration',
    //    'status_finished_job'                  => 'The :count transactions imported can be found in tag <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    //    'status_finished_no_tag'               => 'Firefly III has not collected any transactions from your import file.',
    //    'import_with_key'                      => 'Import with key \':key\'',
    //    'finished_with_errors'                 => 'The import reported some problems.',
    //
    //    // file, upload something
    //    'file_upload_title'                    => 'Import setup (1/4) - Upload your file',
    //    'file_upload_text'                     => 'This routine will help you import files from your bank into Firefly III. Please check out the help pages in the top right corner.',
    //    'file_upload_fields'                   => 'Fields',
    //    'file_upload_help'                     => 'Select your file. Please make sure the file is UTF-8 encoded.',
    //    'file_upload_config_help'              => 'If you have previously imported data into Firefly III, you may have a configuration file, which will pre-set configuration values for you. For some banks, other users have kindly provided their <a href="https://github.com/firefly-iii/import-configurations/wiki">configuration file</a>',
    //    'file_upload_type_help'                => 'Select the type of file you will upload',
    //    'file_upload_submit'                   => 'Upload files',
    //
    //    // file, upload types
    //    'import_file_type_csv'                 => 'CSV (comma separated values)',
    //
    //    // file, initial config for CSV
    //    'csv_initial_title'                    => 'Import setup (2/4) - Basic CSV import setup',
    //    'csv_initial_text'                     => 'To be able to import your file correctly, please validate the options below.',
    //    'csv_initial_box'                      => 'Basic CSV import setup',
    //    'csv_initial_box_title'                => 'Basic CSV import setup options',
    //    'csv_initial_header_help'              => 'Check this box if the first row of your CSV file are the column titles.',
    //    'csv_initial_date_help'                => 'Date time format in your CSV. Follow the format like <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    //    'csv_initial_delimiter_help'           => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    //    'csv_initial_import_account_help'      => 'If your CSV file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the CSV belong to.',
    //    'csv_initial_submit'                   => 'Continue with step 3/4',
    //
    //    // file, new options:
    //    'file_apply_rules_title'               => 'Apply rules',
    //    'file_apply_rules_description'         => 'Apply your rules. Note that this slows the import significantly.',
    //    'file_match_bills_title'               => 'Match bills',
    //    'file_match_bills_description'         => 'Match your bills to newly created withdrawals. Note that this slows the import significantly.',
    //
    //    // file, roles config
    //    'csv_roles_title'                      => 'Import setup (3/4) - Define each column\'s role',
    //    'csv_roles_text'                       => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    //    'csv_roles_table'                      => 'Table',
    //    'csv_roles_column_name'                => 'Name of column',
    //    'csv_roles_column_example'             => 'Column example data',
    //    'csv_roles_column_role'                => 'Column data meaning',
    //    'csv_roles_do_map_value'               => 'Map these values',
    //    'csv_roles_column'                     => 'Column',
    //    'csv_roles_no_example_data'            => 'No example data available',
    //    'csv_roles_submit'                     => 'Continue with step 4/4',
    //
    //    // not csv, but normal warning
    //    'roles_warning'                        => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',
    //    'foreign_amount_warning'               => 'If you mark a column as containing an amount in a foreign currency, you must also set the column that contains which currency it is.',
    //
    //    // file, map data
    //    'file_map_title'                       => 'Import setup (4/4) - Connect import data to Firefly III data',
    //    'file_map_text'                        => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    //    'file_map_field_value'                 => 'Field value',
    //    'file_map_field_mapped_to'             => 'Mapped to',
    //    'map_do_not_map'                       => '(do not map)',
    //    'file_map_submit'                      => 'Start the import',
    //    'file_nothing_to_map'                  => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',
    //
    //    // map things.
    //    'column__ignore'                       => '(ignore this column)',
    //    'column_account-iban'                  => 'Asset account (IBAN)',
    //    'column_account-id'                    => 'Asset account ID (matching FF3)',
    //    'column_account-name'                  => 'Asset account (name)',
    //    'column_amount'                        => 'Amount',
    //    'column_amount_foreign'                => 'Amount (in foreign currency)',
    //    'column_amount_debit'                  => 'Amount (debit column)',
    //    'column_amount_credit'                 => 'Amount (credit column)',
    //    'column_amount-comma-separated'        => 'Amount (comma as decimal separator)',
    //    'column_bill-id'                       => 'Bill ID (matching FF3)',
    //    'column_bill-name'                     => 'Bill name',
    //    'column_budget-id'                     => 'Budget ID (matching FF3)',
    //    'column_budget-name'                   => 'Budget name',
    //    'column_category-id'                   => 'Category ID (matching FF3)',
    //    'column_category-name'                 => 'Category name',
    //    'column_currency-code'                 => 'Currency code (ISO 4217)',
    //    'column_foreign-currency-code'         => 'Foreign currency code (ISO 4217)',
    //    'column_currency-id'                   => 'Currency ID (matching FF3)',
    //    'column_currency-name'                 => 'Currency name (matching FF3)',
    //    'column_currency-symbol'               => 'Currency symbol (matching FF3)',
    //    'column_date-interest'                 => 'Interest calculation date',
    //    'column_date-book'                     => 'Transaction booking date',
    //    'column_date-process'                  => 'Transaction process date',
    //    'column_date-transaction'              => 'Date',
    //    'column_date-due'                      => 'Transaction due date',
    //    'column_date-payment'                  => 'Transaction payment date',
    //    'column_date-invoice'                  => 'Transaction invoice date',
    //    'column_description'                   => 'Description',
    //    'column_opposing-iban'                 => 'Opposing account (IBAN)',
    //    'column_opposing-bic'                  => 'Opposing account (BIC)',
    //    'column_opposing-id'                   => 'Opposing account ID (matching FF3)',
    //    'column_external-id'                   => 'External ID',
    //    'column_opposing-name'                 => 'Opposing account (name)',
    //    'column_rabo-debit-credit'             => 'Rabobank specific debit/credit indicator',
    //    'column_ing-debit-credit'              => 'ING specific debit/credit indicator',
    //    'column_sepa-ct-id'                    => 'SEPA end-to-end Identifier',
    //    'column_sepa-ct-op'                    => 'SEPA Opposing Account Identifier',
    //    'column_sepa-db'                       => 'SEPA Mandate Identifier',
    //    'column_sepa-cc'                       => 'SEPA Clearing Code',
    //    'column_sepa-ci'                       => 'SEPA Creditor Identifier',
    //    'column_sepa-ep'                       => 'SEPA External Purpose',
    //    'column_sepa-country'                  => 'SEPA Country Code',
    //    'column_tags-comma'                    => 'Tags (comma separated)',
    //    'column_tags-space'                    => 'Tags (space separated)',
    //    'column_account-number'                => 'Asset account (account number)',
    //    'column_opposing-number'               => 'Opposing account (account number)',
    //    'column_note'                          => 'Note(s)',
    //    'column_internal-reference'            => 'Internal reference',
    //
    //    // prerequisites
    //    'prerequisites'                        => 'Prerequisites',
    //    'prerequisites_breadcrumb_fake'        => 'Prerequisites for fake provider',
    //    'prerequisites_breadcrumb_file'        => 'Prerequisites for file imports',
    //    'prerequisites_breadcrumb_bunq'        => 'Prerequisites for Bunq',
    //    'prerequisites_breadcrumb_spectre'     => 'Prerequisites for Spectre',
    //    'prerequisites_breadcrumb_plaid'       => 'Prerequisites for Plaid',
    //    'prerequisites_breadcrumb_quovo'       => 'Prerequisites for Quovo',
    //    'prerequisites_breadcrumb_yodlee'      => 'Prerequisites for Yodlee',
    //
    //    // success messages:
    //    'prerequisites_saved_for_fake'         => 'API key stored for fake provider',
    //    'prerequisites_saved_for_file'         => 'Data stored for file imports',
    //    'prerequisites_saved_for_bunq'         => 'API key and IP stored for bunq',
    //    'prerequisites_saved_for_spectre'      => 'App ID and secret stored for Spectre',
    //    'prerequisites_saved_for_plaid'        => 'Data stored for Plaid',
    //    'prerequisites_saved_for_quovo'        => 'Data stored for Quovo',
    //    'prerequisites_saved_for_yodlee'       => 'Data stored for Yodlee',
    //

    //

    //
    //    // job configuration:
    //    'job_configuration_breadcrumb'         => 'Configuration for job ":key"',
    //
    //    // import index page:
    //    'index_breadcrumb'                     => 'Index',
    //    'upload_error'                         => 'The file you have uploaded could not be processed. Possibly it is of an invalid file type or encoding. The log files will have more information.',
    //
    //
    //    // bunq
    //    'bunq_prerequisites_title'             => 'Prerequisites for an import from bunq',
    //    'bunq_prerequisites_text'              => 'In order to import from bunq, you need to obtain an API key. You can do this through the app. Please note that the import function for bunq is in BETA. It has only been tested against the sandbox API.',
    //    'bunq_prerequisites_text_ip'           => 'Bunq requires your externally facing IP address. Firefly III has tried to fill this in using <a href="https://www.ipify.org/">the ipify service</a>. Make sure this IP address is correct, or the import will fail.',
    //    'bunq_do_import'                       => 'Yes, import from this account',
    //    'bunq_accounts_title'                  => 'Bunq accounts',
    //    'bunq_accounts_text'                   => 'These are the accounts associated with your bunq account. Please select the accounts from which you want to import, and in which account the transactions must be imported.',
    //
    //    // Spectre
    //    'spectre_title'                        => 'Import using Spectre',
    //    'spectre_prerequisites_title'          => 'Prerequisites for an import using Spectre',
    //    'spectre_prerequisites_text'           => 'In order to import data using the Spectre API (v4), you must provide Firefly III with two secret values. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    //    'spectre_enter_pub_key'                => 'The import will only work when you enter this public key on your <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    //    'spectre_accounts_title'               => 'Select accounts to import from',
    //    'spectre_accounts_text'                => 'Each account on the left below has been found by Spectre and can be imported into Firefly III. Please select the asset account that should hold any given transactions. If you do not wish to import from any particular account, remove the check from the checkbox.',
    //    'spectre_do_import'                    => 'Yes, import from this account',
    //    'spectre_no_supported_accounts'        => 'You cannot import from this account due to a currency mismatch.',
    //
    //    // keys from "extra" array:
    //    'spectre_extra_key_iban'               => 'IBAN',
    //    'spectre_extra_key_swift'              => 'SWIFT',
    //    'spectre_extra_key_status'             => 'Status',
    //    'spectre_extra_key_card_type'          => 'Card type',
    //    'spectre_extra_key_account_name'       => 'Account name',
    //    'spectre_extra_key_client_name'        => 'Client name',
    //    'spectre_extra_key_account_number'     => 'Account number',
    //    'spectre_extra_key_blocked_amount'     => 'Blocked amount',
    //    'spectre_extra_key_available_amount'   => 'Available amount',
    //    'spectre_extra_key_credit_limit'       => 'Credit limit',
    //    'spectre_extra_key_interest_rate'      => 'Interest rate',
    //    'spectre_extra_key_expiry_date'        => 'Expiry date',
    //    'spectre_extra_key_open_date'          => 'Open date',
    //    'spectre_extra_key_current_time'       => 'Current time',
    //    'spectre_extra_key_current_date'       => 'Current date',
    //    'spectre_extra_key_cards'              => 'Cards',
    //    'spectre_extra_key_units'              => 'Units',
    //    'spectre_extra_key_unit_price'         => 'Unit price',
    //    'spectre_extra_key_transactions_count' => 'Transaction count',
    //
    //    // various other strings:
    //    'imported_from_account'                => 'Imported from ":account"',
];

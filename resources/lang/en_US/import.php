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
    'status_wait_title'                    => 'Please hold...',
    'status_wait_text'                     => 'This box will disappear in a moment.',
    'status_fatal_title'                   => 'A fatal error occurred',
    'status_fatal_text'                    => 'A fatal error occurred, which the import-routine cannot recover from. Please see the explanation in red below.',
    'status_fatal_more'                    => 'If the error is a time-out, the import will have stopped half-way. For some server configurations, it is merely the server that stopped while the import keeps running in the background. To verify this, check out the log files. If the problem persists, consider importing over the command line instead.',
    'status_ready_title'                   => 'Import is ready to start',
    'status_ready_text'                    => 'The import is ready to start. All the configuration you needed to do has been done. Please download the configuration file. It will help you with the import should it not go as planned. To actually run the import, you can either execute the following command in your console, or run the web-based import. Depending on your configuration, the console import will give you more feedback.',
    'status_ready_noconfig_text'           => 'The import is ready to start. All the configuration you needed to do has been done. To actually run the import, you can either execute the following command in your console, or run the web-based import. Depending on your configuration, the console import will give you more feedback.',
    'status_ready_config'                  => 'Download configuration',
    'status_ready_start'                   => 'Start the import',
    'status_ready_share'                   => 'Please consider downloading your configuration and sharing it at the <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">import configuration center</a></strong>. This will allow other users of Firefly III to import their files more easily.',
    'status_job_new'                       => 'The job is brand new.',
    'status_job_configuring'               => 'The import is being configured.',
    'status_job_configured'                => 'The import is configured.',
    'status_job_running'                   => 'The import is running.. Please wait..',
    'status_job_error'                     => 'The job has generated an error.',
    'status_job_finished'                  => 'The import has finished!',
    'status_running_title'                 => 'The import is running',
    'status_running_placeholder'           => 'Please hold for an update...',
    'status_finished_title'                => 'Import routine finished',
    'status_finished_text'                 => 'The import routine has imported your data.',
    'status_errors_title'                  => 'Errors during the import',
    'status_errors_single'                 => 'An error has occurred during the import. It does not appear to be fatal.',
    'status_errors_multi'                  => 'Some errors occurred during the import. These do not appear to be fatal.',
    'status_bread_crumb'                   => 'Import status',
    'status_sub_title'                     => 'Import status',
    'config_sub_title'                     => 'Set up your import',
    'status_finished_job'                  => 'The :count transactions imported can be found in tag <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    'status_finished_no_tag'               => 'Firefly III has not collected any journals from your import file.',
    'import_with_key'                      => 'Import with key \':key\'',

    // file, upload something
    'file_upload_title'                    => 'Import setup (1/4) - Upload your file',
    'file_upload_text'                     => 'This routine will help you import files from your bank into Firefly III. Please check out the help pages in the top right corner.',
    'file_upload_fields'                   => 'Fields',
    'file_upload_help'                     => 'Select your file',
    'file_upload_config_help'              => 'If you have previously imported data into Firefly III, you may have a configuration file, which will pre-set configuration values for you. For some banks, other users have kindly provided their <a href="https://github.com/firefly-iii/import-configurations/wiki">configuration file</a>',
    'file_upload_type_help'                => 'Select the type of file you will upload',
    'file_upload_submit'                   => 'Upload files',

    // file, upload types
    'import_file_type_csv'                 => 'CSV (comma separated values)',

    // file, initial config for CSV
    'csv_initial_title'                    => 'Import setup (2/4) - Basic CSV import setup',
    'csv_initial_text'                     => 'To be able to import your file correctly, please validate the options below.',
    'csv_initial_box'                      => 'Basic CSV import setup',
    'csv_initial_box_title'                => 'Basic CSV import setup options',
    'csv_initial_header_help'              => 'Check this box if the first row of your CSV file are the column titles.',
    'csv_initial_date_help'                => 'Date time format in your CSV. Follow the format like <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'csv_initial_delimiter_help'           => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'csv_initial_import_account_help'      => 'If your CSV file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the CSV belong to.',
    'csv_initial_submit'                   => 'Continue with step 3/4',

    // file, new options:
    'file_apply_rules_title'               => 'Apply rules',
    'file_apply_rules_description'         => 'Apply your rules. Note that this slows the import significantly.',
    'file_match_bills_title'               => 'Match bills',
    'file_match_bills_description'         => 'Match your bills to newly created withdrawals. Note that this slows the import significantly.',

    // file, roles config
    'csv_roles_title'                      => 'Import setup (3/4) - Define each column\'s role',
    'csv_roles_text'                       => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'csv_roles_table'                      => 'Table',
    'csv_roles_column_name'                => 'Name of column',
    'csv_roles_column_example'             => 'Column example data',
    'csv_roles_column_role'                => 'Column data meaning',
    'csv_roles_do_map_value'               => 'Map these values',
    'csv_roles_column'                     => 'Column',
    'csv_roles_no_example_data'            => 'No example data available',
    'csv_roles_submit'                     => 'Continue with step 4/4',

    // not csv, but normal warning
    'roles_warning'                        => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',
    'foreign_amount_warning'               => 'If you mark a column as containing an amount in a foreign currency, you must also set the column that contains which currency it is.',
    // file, map data
    'file_map_title'                       => 'Import setup (4/4) - Connect import data to Firefly III data',
    'file_map_text'                        => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'file_map_field_value'                 => 'Field value',
    'file_map_field_mapped_to'             => 'Mapped to',
    'map_do_not_map'                       => '(do not map)',
    'file_map_submit'                      => 'Start the import',
    'file_nothing_to_map'                  => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',

    // map things.
    'column__ignore'                       => '(ignore this column)',
    'column_account-iban'                  => 'Asset account (IBAN)',
    'column_account-id'                    => 'Asset account ID (matching FF3)',
    'column_account-name'                  => 'Asset account (name)',
    'column_amount'                        => 'Amount',
    'column_amount_foreign'                => 'Amount (in foreign currency)',
    'column_amount_debit'                  => 'Amount (debit column)',
    'column_amount_credit'                 => 'Amount (credit column)',
    'column_amount-comma-separated'        => 'Amount (comma as decimal separator)',
    'column_bill-id'                       => 'Bill ID (matching FF3)',
    'column_bill-name'                     => 'Bill name',
    'column_budget-id'                     => 'Budget ID (matching FF3)',
    'column_budget-name'                   => 'Budget name',
    'column_category-id'                   => 'Category ID (matching FF3)',
    'column_category-name'                 => 'Category name',
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
    'column_external-id'                   => 'External ID',
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


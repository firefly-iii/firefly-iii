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
    'index_breadcrumb'                    => 'Import data into Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Prerequisites for the fake import provider',
    'prerequisites_breadcrumb_spectre'    => 'Prerequisites for Spectre',
    'prerequisites_breadcrumb_bunq'       => 'Prerequisites for bunq',
    'prerequisites_breadcrumb_ynab'       => 'Prerequisites for YNAB',
    'job_configuration_breadcrumb'        => 'Configuration for ":key"',
    'job_status_breadcrumb'               => 'Import status for ":key"',
    'disabled_for_demo_user'              => 'disabled in demo',

    // index page:
    'general_index_intro'                 => 'Welcome to Firefly III\'s import routine. There are a few ways of importing data into Firefly III, displayed here as buttons.',

    // import provider strings (index):
    'button_fake'                         => 'Fake an import',
    'button_file'                         => 'Import a file',
    'button_bunq'                         => 'Import from bunq',
    'button_spectre'                      => 'Import using Spectre',
    'button_plaid'                        => 'Import using Plaid',
    'button_yodlee'                       => 'Import using Yodlee',
    'button_quovo'                        => 'Import using Quovo',
    'button_ynab'                         => 'Import from You Need A Budget',
    'button_fints'                        => 'Import using FinTS',


    // prerequisites box (index)
    'need_prereq_title'                   => 'Import prerequisites',
    'need_prereq_intro'                   => 'Some import methods need your attention before they can be used. For example, they might require special API keys or application secrets. You can configure them here. The icon indicates if these prerequisites have been met.',
    'do_prereq_fake'                      => 'Prerequisites for the fake provider',
    'do_prereq_file'                      => 'Prerequisites for file imports',
    'do_prereq_bunq'                      => 'Prerequisites for imports from bunq',
    'do_prereq_spectre'                   => 'Prerequisites for imports using Spectre',
    'do_prereq_plaid'                     => 'Prerequisites for imports using Plaid',
    'do_prereq_yodlee'                    => 'Prerequisites for imports using Yodlee',
    'do_prereq_quovo'                     => 'Prerequisites for imports using Quovo',
    'do_prereq_ynab'                      => 'Prerequisites for imports from YNAB',

    // prerequisites:
    'prereq_fake_title'                   => 'Prerequisites for an import from the fake import provider',
    'prereq_fake_text'                    => 'This fake provider requires a fake API key. It must be 32 characters long. You can use this one: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Prerequisites for an import using the Spectre API',
    'prereq_spectre_text'                 => 'In order to import data using the Spectre API (v4), you must provide Firefly III with two secret values. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_spectre_pub'                  => 'Likewise, the Spectre API needs to know the public key you see below. Without it, it will not recognize you. Please enter this public key on your <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_bunq_title'                   => 'Prerequisites for an import from bunq',
    'prereq_bunq_text'                    => 'In order to import from bunq, you need to obtain an API key. You can do this through the app. Please note that the import function for bunq is in BETA. It has only been tested against the sandbox API.',
    'prereq_bunq_ip'                      => 'bunq requires your externally facing IP address. Firefly III has tried to fill this in using <a href="https://www.ipify.org/">the ipify service</a>. Make sure this IP address is correct, or the import will fail.',
    'prereq_ynab_title'                   => 'Prerequisites for an import from YNAB',
    'prereq_ynab_text'                    => 'In order to be able to download transactions from YNAB, please create a new application on your <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> and enter the client ID and secret on this page.',
    'prereq_ynab_redirect'                => 'To complete the configuration, enter the following URL at the <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> under the "Redirect URI(s)".',
    'callback_not_tls'                    => 'Firefly III has detected the following callback URI. It seems your server is not set up to accept TLS-connections (https). YNAB will not accept this URI. You may continue with the import (because Firefly III could be wrong) but please keep this in mind.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Fake API key stored successfully!',
    'prerequisites_saved_for_spectre'     => 'App ID and secret stored!',
    'prerequisites_saved_for_bunq'        => 'API key and IP stored!',
    'prerequisites_saved_for_ynab'        => 'YNAB client ID and secret stored!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Job configuration - apply your rules?',
    'job_config_apply_rules_text'         => 'Once the fake provider has run, your rules can be applied to the transactions. This adds time to the import.',
    'job_config_input'                    => 'Your input',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Enter album name',
    'job_config_fake_artist_text'         => 'Many import routines have a few configuration steps you must go through. In the case of the fake import provider, you must answer some weird questions. In this case, enter "David Bowie" to continue.',
    'job_config_fake_song_title'          => 'Enter song name',
    'job_config_fake_song_text'           => 'Mention the song "Golden years" to continue with the fake import.',
    'job_config_fake_album_title'         => 'Enter album name',
    'job_config_fake_album_text'          => 'Some import routines require extra data halfway through the import. In the case of the fake import provider, you must answer some weird questions. Enter "Station to station" to continue.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Import setup (1/4) - Upload your file',
    'job_config_file_upload_text'         => 'This routine will help you import files from your bank into Firefly III. ',
    'job_config_file_upload_help'         => 'Select your file. Please make sure the file is UTF-8 encoded.',
    'job_config_file_upload_config_help'  => 'If you have previously imported data into Firefly III, you may have a configuration file, which will pre-set configuration values for you. For some banks, other users have kindly provided their <a href="https://github.com/firefly-iii/import-configurations/wiki">configuration file</a>',
    'job_config_file_upload_type_help'    => 'Select the type of file you will upload',
    'job_config_file_upload_submit'       => 'Upload files',
    'import_file_type_csv'                => 'CSV (comma separated values)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'The file you have uploaded is not encoded as UTF-8 or ASCII. Firefly III cannot handle such files. Please use Notepad++ or Sublime to convert your file to UTF-8.',
    'job_config_uc_title'                 => 'Import setup (2/4) - Basic file setup',
    'job_config_uc_text'                  => 'To be able to import your file correctly, please validate the options below.',
    'job_config_uc_header_help'           => 'Check this box if the first row of your CSV file are the column titles.',
    'job_config_uc_date_help'             => 'Date time format in your file. Follow the format as <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'job_config_uc_account_help'          => 'If your file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the file belong to.',
    'job_config_uc_apply_rules_title'     => 'Apply rules',
    'job_config_uc_apply_rules_text'      => 'Applies your rules to every imported transaction. Note that this slows the import significantly.',
    'job_config_uc_specifics_title'       => 'Bank-specific options',
    'job_config_uc_specifics_txt'         => 'Some banks deliver badly formatted files. Firefly III can fix those automatically. If your bank delivers such files but it\'s not listed here, please open an issue on GitHub.',
    'job_config_uc_submit'                => 'Continue',
    'invalid_import_account'              => 'You have selected an invalid account to import into.',
    'import_liability_select'             => 'Liability',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Choose your login',
    'job_config_spectre_login_text'       => 'Firefly III has found :count existing login(s) in your Spectre account. Which one would you like to use to import from?',
    'spectre_login_status_active'         => 'Active',
    'spectre_login_status_inactive'       => 'Inactive',
    'spectre_login_status_disabled'       => 'Disabled',
    'spectre_login_new_login'             => 'Login with another bank, or one of these banks with different credentials.',
    'job_config_spectre_accounts_title'   => 'Select accounts to import from',
    'job_config_spectre_accounts_text'    => 'You have selected ":name" (:country). You have :count account(s) available from this provider. Please select the Firefly III asset account(s) where the transactions from these accounts should be stored. Remember, in order to import data both the Firefly III account and the ":name"-account must have the same currency.',
    'spectre_do_not_import'               => '(do not import)',
    'spectre_no_mapping'                  => 'It seems you have not selected any accounts to import from.',
    'imported_from_account'               => 'Imported from ":account"',
    'spectre_account_with_number'         => 'Account :number',
    'job_config_spectre_apply_rules'      => 'Apply rules',
    'job_config_spectre_apply_rules_text' => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',

    // job configuration for bunq:
    'job_config_bunq_accounts_title'      => 'bunq accounts',
    'job_config_bunq_accounts_text'       => 'These are the accounts associated with your bunq account. Please select the accounts from which you want to import, and in which account the transactions must be imported.',
    'bunq_no_mapping'                     => 'It seems you have not selected any accounts.',
    'should_download_config'              => 'You should download <a href=":route">the configuration file</a> for this job. This will make future imports way easier.',
    'share_config_file'                   => 'If you have imported data from a public bank, you should <a href="https://github.com/firefly-iii/import-configurations/wiki">share your configuration file</a> so it will be easy for other users to import their data. Sharing your configuration file will not expose your financial details.',
    'job_config_bunq_apply_rules'         => 'Apply rules',
    'job_config_bunq_apply_rules_text'    => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',
    'bunq_savings_goal'                   => 'Savings goal: :amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => 'Closed bunq account',

    'ynab_account_closed'                  => 'Account is closed!',
    'ynab_account_deleted'                 => 'Account is deleted!',
    'ynab_account_type_savings'            => 'savings account',
    'ynab_account_type_checking'           => 'checking account',
    'ynab_account_type_cash'               => 'cash account',
    'ynab_account_type_creditCard'         => 'credit card',
    'ynab_account_type_lineOfCredit'       => 'line of credit',
    'ynab_account_type_otherAsset'         => 'other asset account',
    'ynab_account_type_otherLiability'     => 'other liabilities',
    'ynab_account_type_payPal'             => 'Paypal',
    'ynab_account_type_merchantAccount'    => 'merchant account',
    'ynab_account_type_investmentAccount'  => 'investment account',
    'ynab_account_type_mortgage'           => 'mortgage',
    'ynab_do_not_import'                   => '(do not import)',
    'job_config_ynab_apply_rules'          => 'Apply rules',
    'job_config_ynab_apply_rules_text'     => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Select your budget',
    'job_config_ynab_select_budgets_text'  => 'You have :count budgets stored at YNAB. Please select the one from which Firefly III will import the transactions.',
    'job_config_ynab_no_budgets'           => 'There are no budgets available to be imported from.',
    'ynab_no_mapping'                      => 'It seems you have not selected any accounts to import from.',
    'job_config_ynab_bad_currency'         => 'You cannot import from the following budget(s), because you do not have accounts with the same currency as these budgets.',
    'job_config_ynab_accounts_title'       => 'Select accounts',
    'job_config_ynab_accounts_text'        => 'You have the following accounts available in this budget. Please select from which accounts you want to import, and where the transactions should be stored.',


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

    //job configuration for finTS
    'fints_connection_failed'              => 'An error occurred while trying to connecting to your bank. Please make sure that all the data you entered is correct. Original error message: :originalError',

    'job_config_fints_url_help'       => 'E.g. https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => 'For many banks this is your account number.',
    'job_config_fints_port_help'      => 'The default port is 443.',
    'job_config_fints_account_help'   => 'Choose the bank account for which you want to import transactions.',
    'job_config_local_account_help'   => 'Choose the Firefly III account corresponding to your bank account chosen above.',
    // specifics:
    'specific_ing_name'               => 'ING NL',
    'specific_ing_descr'              => 'Create better descriptions in ING exports',
    'specific_sns_name'               => 'SNS / Volksbank NL',
    'specific_sns_descr'              => 'Trim quotes from SNS / Volksbank export files',
    'specific_abn_name'               => 'ABN AMRO NL',
    'specific_abn_descr'              => 'Fixes potential problems with ABN AMRO files',
    'specific_rabo_name'              => 'Rabobank NL',
    'specific_rabo_descr'             => 'Fixes potential problems with Rabobank files',
    'specific_pres_name'              => 'President\'s Choice Financial CA',
    'specific_pres_descr'             => 'Fixes potential problems with PC files',
    'specific_belfius_name'           => 'Belfius BE',
    'specific_belfius_descr'          => 'Fixes potential problems with Belfius files',
    'specific_ingbelgium_name'        => 'ING BE',
    'specific_ingbelgium_descr'       => 'Fixes potential problems with ING Belgium files',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'          => 'Import setup (3/4) - Define each column\'s role',
    'job_config_roles_text'           => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'job_config_roles_submit'         => 'Continue',
    'job_config_roles_column_name'    => 'Name of column',
    'job_config_roles_column_example' => 'Column example data',
    'job_config_roles_column_role'    => 'Column data meaning',
    'job_config_roles_do_map_value'   => 'Map these values',
    'job_config_roles_no_example'     => 'No example data available',
    'job_config_roles_fa_warning'     => 'If you mark a column as containing an amount in a foreign currency, you must also set the column that contains which currency it is.',
    'job_config_roles_rwarning'       => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',
    'job_config_roles_colum_count'    => 'Column',
    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Import setup (4/4) - Connect import data to Firefly III data',
    'job_config_map_text'             => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'job_config_map_nothing'          => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',
    'job_config_field_value'          => 'Field value',
    'job_config_field_mapped'         => 'Mapped to',
    'map_do_not_map'                  => '(do not map)',
    'job_config_map_submit'           => 'Start the import',


    // import status page:
    'import_with_key'                 => 'Import with key \':key\'',
    'status_wait_title'               => 'Please hold...',
    'status_wait_text'                => 'This box will disappear in a moment.',
    'status_running_title'            => 'The import is running',
    'status_job_running'              => 'Please wait, running the import...',
    'status_job_storing'              => 'Please wait, storing data...',
    'status_job_rules'                => 'Please wait, running rules...',
    'status_fatal_title'              => 'Fatal error',
    'status_fatal_text'               => 'The import has suffered from an error it could not recover from. Apologies!',
    'status_fatal_more'               => 'This (possibly very cryptic) error message is complemented by log files, which you can find on your hard drive, or in the Docker container where you run Firefly III from.',
    'status_finished_title'           => 'Import finished',
    'status_finished_text'            => 'The import has finished.',
    'finished_with_errors'            => 'There were some errors during the import. Please review them carefully.',
    'unknown_import_result'           => 'Unknown import result',
    'result_no_transactions'          => 'No transactions have been imported. Perhaps they were all duplicates is simply no transactions where present to be imported. Perhaps the log files can tell you what happened. If you import data regularly, this is normal.',
    'result_one_transaction'          => 'Exactly one transaction has been imported. It is stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect it further.',
    'result_many_transactions'        => 'Firefly III has imported :count transactions. They are stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect them further.',


    // general errors and warnings:
    'bad_job_status'                  => 'To access this page, your import job cannot have status ":status".',

    // column roles for CSV import:
    'column__ignore'                  => '(ignore this column)',
    'column_account-iban'             => 'Asset account (IBAN)',
    'column_account-id'               => 'Asset account ID (matching FF3)',
    'column_account-name'             => 'Asset account (name)',
    'column_account-bic'              => 'Asset account (BIC)',
    'column_amount'                   => 'Amount',
    'column_amount_foreign'           => 'Amount (in foreign currency)',
    'column_amount_debit'             => 'Amount (debit column)',
    'column_amount_credit'            => 'Amount (credit column)',
    'column_amount_negated'           => 'Amount (negated column)',
    'column_amount-comma-separated'   => 'Amount (comma as decimal separator)',
    'column_bill-id'                  => 'Bill ID (matching FF3)',
    'column_bill-name'                => 'Bill name',
    'column_budget-id'                => 'Budget ID (matching FF3)',
    'column_budget-name'              => 'Budget name',
    'column_category-id'              => 'Category ID (matching FF3)',
    'column_category-name'            => 'Category name',
    'column_currency-code'            => 'Currency code (ISO 4217)',
    'column_foreign-currency-code'    => 'Foreign currency code (ISO 4217)',
    'column_currency-id'              => 'Currency ID (matching FF3)',
    'column_currency-name'            => 'Currency name (matching FF3)',
    'column_currency-symbol'          => 'Currency symbol (matching FF3)',
    'column_date-interest'            => 'Interest calculation date',
    'column_date-book'                => 'Transaction booking date',
    'column_date-process'             => 'Transaction process date',
    'column_date-transaction'         => 'Date',
    'column_date-due'                 => 'Transaction due date',
    'column_date-payment'             => 'Transaction payment date',
    'column_date-invoice'             => 'Transaction invoice date',
    'column_description'              => 'Description',
    'column_opposing-iban'            => 'Opposing account (IBAN)',
    'column_opposing-bic'             => 'Opposing account (BIC)',
    'column_opposing-id'              => 'Opposing account ID (matching FF3)',
    'column_external-id'              => 'External ID',
    'column_opposing-name'            => 'Opposing account (name)',
    'column_rabo-debit-credit'        => 'Rabobank specific debit/credit indicator',
    'column_ing-debit-credit'         => 'ING specific debit/credit indicator',
    'column_generic-debit-credit'     => 'Generic bank debit/credit indicator',
    'column_sepa_ct_id'               => 'SEPA end-to-end Identifier',
    'column_sepa_ct_op'               => 'SEPA Opposing Account Identifier',
    'column_sepa_db'                  => 'SEPA Mandate Identifier',
    'column_sepa_cc'                  => 'SEPA Clearing Code',
    'column_sepa_ci'                  => 'SEPA Creditor Identifier',
    'column_sepa_ep'                  => 'SEPA External Purpose',
    'column_sepa_country'             => 'SEPA Country Code',
    'column_sepa_batch_id'            => 'SEPA Batch ID',
    'column_tags-comma'               => 'Tags (comma separated)',
    'column_tags-space'               => 'Tags (space separated)',
    'column_account-number'           => 'Asset account (account number)',
    'column_opposing-number'          => 'Opposing account (account number)',
    'column_note'                     => 'Note(s)',
    'column_internal-reference'       => 'Internal reference',

    // error message
    'duplicate_row'                   => 'Row #:row (":description") could not be imported. It already exists.',

];

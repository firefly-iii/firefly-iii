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
    'index_breadcrumb'                    => 'Importuj dane do Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Prerequisites for the fake import provider',
    'prerequisites_breadcrumb_spectre'    => 'Prerequisites for Spectre',
    'prerequisites_breadcrumb_bunq'       => 'Prerequisites for bunq',
    'prerequisites_breadcrumb_ynab'       => 'Prerequisites for YNAB',
    'job_configuration_breadcrumb'        => 'Configuration for ":key"',
    'job_status_breadcrumb'               => 'Import status for ":key"',
    'cannot_create_for_provider'          => 'Firefly III cannot create a job for the ":provider"-provider.',
    'disabled_for_demo_user'              => 'disabled in demo',

    // index page:
    'general_index_title'                 => 'Importuj plik',
    'general_index_intro'                 => 'Welcome to Firefly III\'s import routine. There are a few ways of importing data into Firefly III, displayed here as buttons.',

    // import provider strings (index):
    'button_fake'                         => 'Fake an import',
    'button_file'                         => 'Importuj plik',
    'button_bunq'                         => 'Importuj z bunq',
    'button_spectre'                      => 'Importuj za pomocą Spectre',
    'button_plaid'                        => 'Importuj za pomocą Plaid',
    'button_yodlee'                       => 'Importuj za pomocą Yodlee',
    'button_quovo'                        => 'Importuj za pomocą Quovo',
    'button_ynab'                         => 'Import from You Need A Budget',
    // global config box (index)
    'global_config_title'                 => 'Globalna konfiguracja importu',
    'global_config_text'                  => 'In the future, this box will feature preferences that apply to ALL import providers above.',
    // prerequisites box (index)
    'need_prereq_title'                   => 'Wymagania importu',
    'need_prereq_intro'                   => 'Some import methods need your attention before they can be used. For example, they might require special API keys or application secrets. You can configure them here. The icon indicates if these prerequisites have been met.',
    'do_prereq_fake'                      => 'Prerequisites for the fake provider',
    'do_prereq_file'                      => 'Prerequisites for file imports',
    'do_prereq_bunq'                      => 'Prerequisites for imports from bunq',
    'do_prereq_spectre'                   => 'Prerequisites for imports using Spectre',
    'do_prereq_plaid'                     => 'Prerequisites for imports using Plaid',
    'do_prereq_yodlee'                    => 'Prerequisites for imports using Yodlee',
    'do_prereq_quovo'                     => 'Prerequisites for imports using Quovo',
    'do_prereq_ynab'                      => 'Prerequisites for imports from YNAB',

    // provider config box (index)
    'can_config_title'                    => 'Importuj konfigurację',
    'can_config_intro'                    => 'Some import methods can be configured to your liking. They have extra settings you can tweak.',
    'do_config_fake'                      => 'Configuration for the fake provider',
    'do_config_file'                      => 'Configuration for file imports',
    'do_config_bunq'                      => 'Configuration for bunq imports',
    'do_config_spectre'                   => 'Configuration for imports from Spectre',
    'do_config_plaid'                     => 'Configuration for imports from Plaid',
    'do_config_yodlee'                    => 'Configuration for imports from Yodlee',
    'do_config_quovo'                     => 'Configuration for imports from Quovo',

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
    'job_config_file_upload_type_help'    => 'Wybierz typ pliku, który będziesz przesyłać',
    'job_config_file_upload_submit'       => 'Prześlij pliki',
    'import_file_type_csv'                => 'CSV (wartości oddzielone przecinkami)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'The file you have uploaded is not encoded as UTF-8 or ASCII. Firefly III cannot handle such files. Please use Notepad++ or Sublime to convert your file to UTF-8.',
    'job_config_uc_title'                 => 'Import setup (2/4) - Basic file setup',
    'job_config_uc_text'                  => 'To be able to import your file correctly, please validate the options below.',
    'job_config_uc_header_help'           => 'Check this box if the first row of your CSV file are the column titles.',
    'job_config_uc_date_help'             => 'Date time format in your file. Follow the format as <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'job_config_uc_account_help'          => 'If your file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the file belong to.',
    'job_config_uc_apply_rules_title'     => 'Zastosuj reguły',
    'job_config_uc_apply_rules_text'      => 'Applies your rules to every imported transaction. Note that this slows the import significantly.',
    'job_config_uc_specifics_title'       => 'Bank-specific options',
    'job_config_uc_specifics_txt'         => 'Some banks deliver badly formatted files. Firefly III can fix those automatically. If your bank delivers such files but it\'s not listed here, please open an issue on GitHub.',
    'job_config_uc_submit'                => 'Kontynuuj',
    'invalid_import_account'              => 'You have selected an invalid account to import into.',
    'import_liability_select'             => 'Liability',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Wybierz swój login',
    'job_config_spectre_login_text'       => 'Firefly III has found :count existing login(s) in your Spectre account. Which one would you like to use to import from?',
    'spectre_login_status_active'         => 'Aktywny',
    'spectre_login_status_inactive'       => 'Nieaktywny',
    'spectre_login_status_disabled'       => 'Wyłączony',
    'spectre_login_new_login'             => 'Login with another bank, or one of these banks with different credentials.',
    'job_config_spectre_accounts_title'   => 'Select accounts to import from',
    'job_config_spectre_accounts_text'    => 'You have selected ":name" (:country). You have :count account(s) available from this provider. Please select the Firefly III asset account(s) where the transactions from these accounts should be stored. Remember, in order to import data both the Firefly III account and the ":name"-account must have the same currency.',
    'spectre_no_supported_accounts'       => 'You cannot import from this account due to a currency mismatch.',
    'spectre_do_not_import'               => '(nie importuj)',
    'spectre_no_mapping'                  => 'Wygląda na to, że nie wybrałeś żadnych kont z których można zaimportować dane.',
    'imported_from_account'               => 'Zaimportowane z ":account"',
    'spectre_account_with_number'         => ':number konta',
    'job_config_spectre_apply_rules'      => 'Zastosuj reguły',
    'job_config_spectre_apply_rules_text' => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',

    // job configuration for bunq:
    'job_config_bunq_accounts_title'      => 'konta bunq',
    'job_config_bunq_accounts_text'       => 'These are the accounts associated with your bunq account. Please select the accounts from which you want to import, and in which account the transactions must be imported.',
    'bunq_no_mapping'                     => 'It seems you have not selected any accounts.',
    'should_download_config'              => 'You should download <a href=":route">the configuration file</a> for this job. This will make future imports way easier.',
    'share_config_file'                   => 'If you have imported data from a public bank, you should <a href="https://github.com/firefly-iii/import-configurations/wiki">share your configuration file</a> so it will be easy for other users to import their data. Sharing your configuration file will not expose your financial details.',
    'job_config_bunq_apply_rules'         => 'Zastosuj reguły',
    'job_config_bunq_apply_rules_text'    => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',

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
    'spectre_extra_key_card_type'          => 'Typ karty',
    'spectre_extra_key_account_name'       => 'Nazwa konta',
    'spectre_extra_key_client_name'        => 'Nazwa klienta',
    'spectre_extra_key_account_number'     => 'Numer konta',
    'spectre_extra_key_blocked_amount'     => 'Zablokowana kwota',
    'spectre_extra_key_available_amount'   => 'Dostępna kwota',
    'spectre_extra_key_credit_limit'       => 'Limit kredytowy',
    'spectre_extra_key_interest_rate'      => 'Oprocentowanie',
    'spectre_extra_key_expiry_date'        => 'Data wygaśnięcia',
    'spectre_extra_key_open_date'          => 'Data otwarcia',
    'spectre_extra_key_current_time'       => 'Aktualny czas',
    'spectre_extra_key_current_date'       => 'Aktualna data',
    'spectre_extra_key_cards'              => 'Karty',
    'spectre_extra_key_units'              => 'Jednostki',
    'spectre_extra_key_unit_price'         => 'Cena jednostkowa',
    'spectre_extra_key_transactions_count' => 'Liczba transakcji',

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
    'job_config_roles_colum_count'         => 'Kolumna',
    // job config for the file provider (stage: mapping):
    'job_config_map_title'                 => 'Import setup (4/4) - Connect import data to Firefly III data',
    'job_config_map_text'                  => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'job_config_map_nothing'               => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',
    'job_config_field_value'               => 'Wartość pola',
    'job_config_field_mapped'              => 'Zmapowane na',
    'map_do_not_map'                       => '(nie mapuj)',
    'job_config_map_submit'                => 'Rozpocznij import',


    // import status page:
    'import_with_key'                      => 'Import z kluczem \':key\'',
    'status_wait_title'                    => 'Proszę czekać...',
    'status_wait_text'                     => 'To pole za chwilę zniknie.',
    'status_running_title'                 => 'Trwa importowanie',
    'status_job_running'                   => 'Proszę czekać, trwa importowanie danych...',
    'status_job_storing'                   => 'Proszę czekać, zapisuję dane...',
    'status_job_rules'                     => 'Proszę czekać, trwa procesowanie reguł...',
    'status_fatal_title'                   => 'Błąd krytyczny',
    'status_fatal_text'                    => 'The import has suffered from an error it could not recover from. Apologies!',
    'status_fatal_more'                    => 'This (possibly very cryptic) error message is complemented by log files, which you can find on your hard drive, or in the Docker container where you run Firefly III from.',
    'status_finished_title'                => 'Import zakończony',
    'status_finished_text'                 => 'Importowanie zostało zakończone.',
    'finished_with_errors'                 => 'There were some errors during the import. Please review them carefully.',
    'unknown_import_result'                => 'Nieznany wynik importu',
    'result_no_transactions'               => 'No transactions have been imported. Perhaps they were all duplicates is simply no transactions where present to be imported. Perhaps the log files can tell you what happened. If you import data regularly, this is normal.',
    'result_one_transaction'               => 'Exactly one transaction has been imported. It is stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect it further.',
    'result_many_transactions'             => 'Firefly III has imported :count transactions. They are stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect them further.',


    // general errors and warnings:
    'bad_job_status'                       => 'To access this page, your import job cannot have status ":status".',

    // column roles for CSV import:
    'column__ignore'                       => '(zignoruj tę kolumnę)',
    'column_account-iban'                  => 'Konto aktywów (IBAN)',
    'column_account-id'                    => 'ID konta aktywów (z bazy FF3)',
    'column_account-name'                  => 'Konto aktywów (nazwa)',
    'column_account-bic'                   => 'Asset account (BIC)',
    'column_amount'                        => 'Kwota',
    'column_amount_foreign'                => 'Kwota (w obcej walucie)',
    'column_amount_debit'                  => 'Kwota (kolumna debetowa)',
    'column_amount_credit'                 => 'Kwota (kolumna kredytowa)',
    'column_amount_negated'                => 'Amount (negated column)',
    'column_amount-comma-separated'        => 'Kwota (przecinek jako separator dziesiętny)',
    'column_bill-id'                       => 'ID rachunku (z bazy FF3)',
    'column_bill-name'                     => 'Nazwa rachunku',
    'column_budget-id'                     => 'ID budżetu (z bazy FF3)',
    'column_budget-name'                   => 'Nazwa budżetu',
    'column_category-id'                   => 'ID kategorii (z bazy FF3)',
    'column_category-name'                 => 'Nazwa kategorii',
    'column_currency-code'                 => 'Kod waluty (ISO 4217)',
    'column_foreign-currency-code'         => 'Kod obcej waluty (ISO 4217)',
    'column_currency-id'                   => 'ID waluty (z bazy FF3)',
    'column_currency-name'                 => 'Nazwa waluty (z bazy FF3)',
    'column_currency-symbol'               => 'Symbol waluty (z bazy FF3)',
    'column_date-interest'                 => 'Data obliczenia odsetek',
    'column_date-book'                     => 'Data księgowania transakcji',
    'column_date-process'                  => 'Data przetworzenia transakcji',
    'column_date-transaction'              => 'Data',
    'column_date-due'                      => 'Transaction due date',
    'column_date-payment'                  => 'Transaction payment date',
    'column_date-invoice'                  => 'Transaction invoice date',
    'column_description'                   => 'Opis',
    'column_opposing-iban'                 => 'Przeciwstawne konto (IBAN)',
    'column_opposing-bic'                  => 'Przeciwstawne konto (BIC)',
    'column_opposing-id'                   => 'ID przeciwstawnego konta (z bazy FF3)',
    'column_external-id'                   => 'Zewnętrzne ID',
    'column_opposing-name'                 => 'Przeciwstawne konto (nazwa)',
    'column_rabo-debit-credit'             => 'Specyficzny wskaźnik obciążenia/kredytu Rabobank',
    'column_ing-debit-credit'              => 'Specyficzny wskaźnik obciążenia/kredytu ING',
    'column_sepa-ct-id'                    => 'SEPA end-to-end Identifier',
    'column_sepa-ct-op'                    => 'SEPA Opposing Account Identifier',
    'column_sepa-db'                       => 'SEPA Mandate Identifier',
    'column_sepa-cc'                       => 'SEPA Clearing Code',
    'column_sepa-ci'                       => 'SEPA Creditor Identifier',
    'column_sepa-ep'                       => 'SEPA External Purpose',
    'column_sepa-country'                  => 'SEPA Country Code',
    'column_tags-comma'                    => 'Tagi (oddzielone przecinkami)',
    'column_tags-space'                    => 'Tagi (oddzielone spacjami)',
    'column_account-number'                => 'Konto aktywów (numer konta)',
    'column_opposing-number'               => 'Konto przeciwne (numer konta)',
    'column_note'                          => 'Notatki',
    'column_internal-reference'            => 'Internal reference',

];

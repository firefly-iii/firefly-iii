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
    'index_breadcrumb'                    => 'Importovat data do Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Prerequisites for the fake import provider',
    'prerequisites_breadcrumb_spectre'    => 'Prerequisites for Spectre',
    'prerequisites_breadcrumb_bunq'       => 'Prerequisites for bunq',
    'prerequisites_breadcrumb_ynab'       => 'Prerequisites for YNAB',
    'job_configuration_breadcrumb'        => 'Nastavení pro „:key“',
    'job_status_breadcrumb'               => 'Stav importu pro „:key“',
    'disabled_for_demo_user'              => 'v ukázce vypnuté',

    // index page:
    'general_index_intro'                 => 'Welcome to Firefly III\'s import routine. There are a few ways of importing data into Firefly III, displayed here as buttons.',

    // import provider strings (index):
    'button_fake'                         => 'Simulovat import',
    'button_file'                         => 'Importovat soubor',
    'button_bunq'                         => 'Importovat z bunq',
    'button_spectre'                      => 'Importovat pomocí Spectre',
    'button_plaid'                        => 'Importovat pomocí Plaid',
    'button_yodlee'                       => 'Importovat pomocí Yodlee',
    'button_quovo'                        => 'Importovat pomocí Quovo',
    'button_ynab'                         => 'Importovat z You Need A Budget',
    'button_fints'                        => 'Importovat pomocí FinTS',


    // prerequisites box (index)
    'need_prereq_title'                   => 'Import prerequisites',
    'need_prereq_intro'                   => 'Some import methods need your attention before they can be used. For example, they might require special API keys or application secrets. You can configure them here. The icon indicates if these prerequisites have been met.',
    'do_prereq_fake'                      => 'Prerequisites for the fake provider',
    'do_prereq_file'                      => 'Prerequisites for file imports',
    'do_prereq_bunq'                      => 'Předpoklady pro importy z bunq',
    'do_prereq_spectre'                   => 'Předpoklady pro importy z Spectre',
    'do_prereq_plaid'                     => 'Předpoklady pro importy z Plaid',
    'do_prereq_yodlee'                    => 'Předpoklady pro importy z Yodlee',
    'do_prereq_quovo'                     => 'Předpoklady pro importy z Quovo',
    'do_prereq_ynab'                      => 'Předpoklady pro importy z YNAB',

    // prerequisites:
    'prereq_fake_title'                   => 'Prerequisites for an import from the fake import provider',
    'prereq_fake_text'                    => 'This fake provider requires a fake API key. It must be 32 characters long. You can use this one: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Prerequisites for an import using the Spectre API',
    'prereq_spectre_text'                 => 'In order to import data using the Spectre API (v4), you must provide Firefly III with two secret values. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_spectre_pub'                  => 'Likewise, the Spectre API needs to know the public key you see below. Without it, it will not recognize you. Please enter this public key on your <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_bunq_title'                   => 'Předpoklady pro import z bunq',
    'prereq_bunq_text'                    => 'In order to import from bunq, you need to obtain an API key. You can do this through the app. Please note that the import function for bunq is in BETA. It has only been tested against the sandbox API.',
    'prereq_bunq_ip'                      => 'bunq requires your externally facing IP address. Firefly III has tried to fill this in using <a href="https://www.ipify.org/">the ipify service</a>. Make sure this IP address is correct, or the import will fail.',
    'prereq_ynab_title'                   => 'Předpoklady pro import z YNAB',
    'prereq_ynab_text'                    => 'In order to be able to download transactions from YNAB, please create a new application on your <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> and enter the client ID and secret on this page.',
    'prereq_ynab_redirect'                => 'To complete the configuration, enter the following URL at the <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> under the "Redirect URI(s)".',
    'callback_not_tls'                    => 'Firefly III zjistilo následující URI adresu zpětného volání. Zdá se, že váš server není nastaven tak, aby přijímal TLS připojení (https). YNAB tuto URI nepřijme. Můžete pokračovat v importu (protože Firefly III se může mýlit), ale mějte to na paměti.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Atrapa API klíče úspěšně uložena!',
    'prerequisites_saved_for_spectre'     => 'Identif. aplikace a heslo uloženo!',
    'prerequisites_saved_for_bunq'        => 'API klíč a IP adresa uložena!',
    'prerequisites_saved_for_ynab'        => 'Identifikátor YNAB klienta a heslo uloženo!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Nastavení úlohy – uplatnit vaše pravidla?',
    'job_config_apply_rules_text'         => 'Po spuštění atrapy poskytovatele je možné na transakce uplatnit pravidla. To ale prodlouží dobu importu.',
    'job_config_input'                    => 'Vaše zadání',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Zadejte název skupiny',
    'job_config_fake_artist_text'         => 'Mnoho importních rutin má několik kroků nastavení, kterými je třeba projít. V případě atrapy poskytovatele importu je třeba odpovědět na některé podivné otázky. V tomto případě pokračujte zadáním „David Bowie“.',
    'job_config_fake_song_title'          => 'Zadejte název skladby',
    'job_config_fake_song_text'           => 'Pro pokračování v atrapě importu zmiňte skladbu „Golden years2“.',
    'job_config_fake_album_title'         => 'Zadejte název alba',
    'job_config_fake_album_text'          => 'Some import routines require extra data halfway through the import. In the case of the fake import provider, you must answer some weird questions. Enter "Station to station" to continue.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Nastavení importu (1/4) – nahrajte svůj soubor',
    'job_config_file_upload_text'         => 'Tato rutina vám pomůže importovat soubory z vaší banky do Firefly III. ',
    'job_config_file_upload_help'         => 'Vyberte soubor. Ověřte, že obsah souboru je ve znakové sadě UTF-8.',
    'job_config_file_upload_config_help'  => 'If you have previously imported data into Firefly III, you may have a configuration file, which will pre-set configuration values for you. For some banks, other users have kindly provided their <a href="https://github.com/firefly-iii/import-configurations/wiki">configuration file</a>',
    'job_config_file_upload_type_help'    => 'Vyberte typ souboru, který budete nahrávat',
    'job_config_file_upload_submit'       => 'Nahrát soubory',
    'import_file_type_csv'                => 'CSV (středníkem oddělované hodnoty)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'The file you have uploaded is not encoded as UTF-8 or ASCII. Firefly III cannot handle such files. Please use Notepad++ or Sublime to convert your file to UTF-8.',
    'job_config_uc_title'                 => 'Nastavení importu (2/4) – základní nastavení souboru',
    'job_config_uc_text'                  => 'Aby byl možný správný import, ověřte níže uvedené volby.',
    'job_config_uc_header_help'           => 'Check this box if the first row of your CSV file are the column titles.',
    'job_config_uc_date_help'             => 'Date time format in your file. Follow the format as <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'job_config_uc_account_help'          => 'If your file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the file belong to.',
    'job_config_uc_apply_rules_title'     => 'Uplatnit pravidla',
    'job_config_uc_apply_rules_text'      => 'Applies your rules to every imported transaction. Note that this slows the import significantly.',
    'job_config_uc_specifics_title'       => 'Předvolby pro konkrétní banku',
    'job_config_uc_specifics_txt'         => 'Some banks deliver badly formatted files. Firefly III can fix those automatically. If your bank delivers such files but it\'s not listed here, please open an issue on GitHub.',
    'job_config_uc_submit'                => 'Pokračovat',
    'invalid_import_account'              => 'You have selected an invalid account to import into.',
    'import_liability_select'             => 'Závazek',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Zvolte své přihlášení',
    'job_config_spectre_login_text'       => 'Firefly III has found :count existing login(s) in your Spectre account. Which one would you like to use to import from?',
    'spectre_login_status_active'         => 'Aktivní',
    'spectre_login_status_inactive'       => 'Neaktivní',
    'spectre_login_status_disabled'       => 'Vypnuto',
    'spectre_login_new_login'             => 'Login with another bank, or one of these banks with different credentials.',
    'job_config_spectre_accounts_title'   => 'Vybrat účty ze kterých importovat',
    'job_config_spectre_accounts_text'    => 'You have selected ":name" (:country). You have :count account(s) available from this provider. Please select the Firefly III asset account(s) where the transactions from these accounts should be stored. Remember, in order to import data both the Firefly III account and the ":name"-account must have the same currency.',
    'spectre_do_not_import'               => '(neimportovat)',
    'spectre_no_mapping'                  => 'It seems you have not selected any accounts to import from.',
    'imported_from_account'               => 'Importováno z „:account“',
    'spectre_account_with_number'         => 'Účet :number',
    'job_config_spectre_apply_rules'      => 'Uplatnit pravidla',
    'job_config_spectre_apply_rules_text' => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',

    // job configuration for bunq:
    'job_config_bunq_accounts_title'      => 'bunq účty',
    'job_config_bunq_accounts_text'       => 'These are the accounts associated with your bunq account. Please select the accounts from which you want to import, and in which account the transactions must be imported.',
    'bunq_no_mapping'                     => 'It seems you have not selected any accounts.',
    'should_download_config'              => 'You should download <a href=":route">the configuration file</a> for this job. This will make future imports way easier.',
    'share_config_file'                   => 'If you have imported data from a public bank, you should <a href="https://github.com/firefly-iii/import-configurations/wiki">share your configuration file</a> so it will be easy for other users to import their data. Sharing your configuration file will not expose your financial details.',
    'job_config_bunq_apply_rules'         => 'Uplatnit pravidla',
    'job_config_bunq_apply_rules_text'    => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',
    'bunq_savings_goal'                   => 'Savings goal: :amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => 'Zrušený bunq účet',

    'ynab_account_closed'                  => 'Účet je uzavřen!',
    'ynab_account_deleted'                 => 'Účet je smazán!',
    'ynab_account_type_savings'            => 'spořicí účet',
    'ynab_account_type_checking'           => 'checking account',
    'ynab_account_type_cash'               => 'hotovostní účet',
    'ynab_account_type_creditCard'         => 'kreditní karta',
    'ynab_account_type_lineOfCredit'       => 'řádek úvěru',
    'ynab_account_type_otherAsset'         => 'other asset account',
    'ynab_account_type_otherLiability'     => 'ostatní závazky',
    'ynab_account_type_payPal'             => 'Paypal',
    'ynab_account_type_merchantAccount'    => 'merchant account',
    'ynab_account_type_investmentAccount'  => 'investiční účet',
    'ynab_account_type_mortgage'           => 'hypotéka',
    'ynab_do_not_import'                   => '(neimportovat)',
    'job_config_ynab_apply_rules'          => 'Uplatnit pravidla',
    'job_config_ynab_apply_rules_text'     => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Vyberte svůj rozpočet',
    'job_config_ynab_select_budgets_text'  => 'You have :count budgets stored at YNAB. Please select the one from which Firefly III will import the transactions.',
    'job_config_ynab_no_budgets'           => 'There are no budgets available to be imported from.',
    'ynab_no_mapping'                      => 'It seems you have not selected any accounts to import from.',
    'job_config_ynab_bad_currency'         => 'You cannot import from the following budget(s), because you do not have accounts with the same currency as these budgets.',
    'job_config_ynab_accounts_title'       => 'Vyberte účty',
    'job_config_ynab_accounts_text'        => 'You have the following accounts available in this budget. Please select from which accounts you want to import, and where the transactions should be stored.',


    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Stav',
    'spectre_extra_key_card_type'          => 'Typ karty',
    'spectre_extra_key_account_name'       => 'Název účtu',
    'spectre_extra_key_client_name'        => 'Jméno zákazníka',
    'spectre_extra_key_account_number'     => 'Číslo účtu',
    'spectre_extra_key_blocked_amount'     => 'Blokovaná částka',
    'spectre_extra_key_available_amount'   => 'Částka k dispozici',
    'spectre_extra_key_credit_limit'       => 'Credit limit',
    'spectre_extra_key_interest_rate'      => 'Úroková sazba',
    'spectre_extra_key_expiry_date'        => 'Datum skončení platnosti',
    'spectre_extra_key_open_date'          => 'Open date',
    'spectre_extra_key_current_time'       => 'Aktuální čas',
    'spectre_extra_key_current_date'       => 'Aktuální datum',
    'spectre_extra_key_cards'              => 'Karty',
    'spectre_extra_key_units'              => 'Jednotky',
    'spectre_extra_key_unit_price'         => 'Jednotková cena',
    'spectre_extra_key_transactions_count' => 'Počet transakcí',

    //job configuration for finTS
    'fints_connection_failed'              => 'An error occurred while trying to connecting to your bank. Please make sure that all the data you entered is correct. Original error message: :originalError',

    'job_config_fints_url_help'       => 'Např. https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => 'Pro mnohé banky je toto číslo vašeho účtu.',
    'job_config_fints_port_help'      => 'Výchozí port je 443.',
    'job_config_fints_account_help'   => 'Choose the bank account for which you want to import transactions.',
    'job_config_local_account_help'   => 'Choose the Firefly III account corresponding to your bank account chosen above.',
    // specifics:
    'specific_ing_name'               => 'ING NL',
    'specific_ing_descr'              => 'Vytvořit lepší popisy v exportu ING',
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
    'job_config_roles_submit'         => 'Pokračovat',
    'job_config_roles_column_name'    => 'Název sloupce',
    'job_config_roles_column_example' => 'Column example data',
    'job_config_roles_column_role'    => 'Význam dat ve sloupci',
    'job_config_roles_do_map_value'   => 'Mapovat tyto hodnoty',
    'job_config_roles_no_example'     => 'Nejsou k dispozici žádná ukázková data',
    'job_config_roles_fa_warning'     => 'If you mark a column as containing an amount in a foreign currency, you must also set the column that contains which currency it is.',
    'job_config_roles_rwarning'       => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',
    'job_config_roles_colum_count'    => 'Sloupec',
    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Import setup (4/4) - Connect import data to Firefly III data',
    'job_config_map_text'             => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'job_config_map_nothing'          => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',
    'job_config_field_value'          => 'Hodnota v kolonce',
    'job_config_field_mapped'         => 'Mapováno na',
    'map_do_not_map'                  => '(nemapovat)',
    'job_config_map_submit'           => 'Zahájit import',


    // import status page:
    'import_with_key'                 => 'Importovat s klíčem „:key“',
    'status_wait_title'               => 'Vyčkejte…',
    'status_wait_text'                => 'Toto okno za okamžik zmizí.',
    'status_running_title'            => 'Import je spuštěn',
    'status_job_running'              => 'Čekejte, import probíhá…',
    'status_job_storing'              => 'Čekejte, ukládání dat…',
    'status_job_rules'                => 'Čekejte, spouštění pravidel…',
    'status_fatal_title'              => 'Fatální chyba',
    'status_fatal_text'               => 'The import has suffered from an error it could not recover from. Apologies!',
    'status_fatal_more'               => 'This (possibly very cryptic) error message is complemented by log files, which you can find on your hard drive, or in the Docker container where you run Firefly III from.',
    'status_finished_title'           => 'Import dokončen',
    'status_finished_text'            => 'Import byl dokončen.',
    'finished_with_errors'            => 'There were some errors during the import. Please review them carefully.',
    'unknown_import_result'           => 'Neznámý výsledek importu',
    'result_no_transactions'          => 'No transactions have been imported. Perhaps they were all duplicates is simply no transactions where present to be imported. Perhaps the log files can tell you what happened. If you import data regularly, this is normal.',
    'result_one_transaction'          => 'Exactly one transaction has been imported. It is stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect it further.',
    'result_many_transactions'        => 'Firefly III has imported :count transactions. They are stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect them further.',


    // general errors and warnings:
    'bad_job_status'                  => 'To access this page, your import job cannot have status ":status".',

    // column roles for CSV import:
    'column__ignore'                  => '(ignorovat tento sloupec)',
    'column_account-iban'             => 'Asset account (IBAN)',
    'column_account-id'               => 'Asset account ID (matching FF3)',
    'column_account-name'             => 'Asset account (name)',
    'column_account-bic'              => 'Asset account (BIC)',
    'column_amount'                   => 'Částka',
    'column_amount_foreign'           => 'Amount (in foreign currency)',
    'column_amount_debit'             => 'Amount (debit column)',
    'column_amount_credit'            => 'Amount (credit column)',
    'column_amount_negated'           => 'Amount (negated column)',
    'column_amount-comma-separated'   => 'Amount (comma as decimal separator)',
    'column_bill-id'                  => 'Bill ID (matching FF3)',
    'column_bill-name'                => 'Bill name',
    'column_budget-id'                => 'Budget ID (matching FF3)',
    'column_budget-name'              => 'Název rozpočtu',
    'column_category-id'              => 'Category ID (matching FF3)',
    'column_category-name'            => 'Název kategorie',
    'column_currency-code'            => 'Kód měny (dle normy ISO 4217)',
    'column_foreign-currency-code'    => 'Kód cizí měny (dle normy ISO 4217)',
    'column_currency-id'              => 'Currency ID (matching FF3)',
    'column_currency-name'            => 'Currency name (matching FF3)',
    'column_currency-symbol'          => 'Currency symbol (matching FF3)',
    'column_date-interest'            => 'Datum výpočtu úroku',
    'column_date-book'                => 'Datum zaúčtování transakce',
    'column_date-process'             => 'Datum zpracování transakce',
    'column_date-transaction'         => 'Datum',
    'column_date-due'                 => 'Splatnost transakce',
    'column_date-payment'             => 'Datum platby transakce',
    'column_date-invoice'             => 'Datum vystavení transakce',
    'column_description'              => 'Popis',
    'column_opposing-iban'            => 'Opposing account (IBAN)',
    'column_opposing-bic'             => 'Opposing account (BIC)',
    'column_opposing-id'              => 'Opposing account ID (matching FF3)',
    'column_external-id'              => 'Externí identif.',
    'column_opposing-name'            => 'Účet protistrany (název)',
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
    'column_tags-comma'               => 'Štítky (oddělované čárkou)',
    'column_tags-space'               => 'Štítky (oddělované mezerou)',
    'column_account-number'           => 'Účet aktiv (číslo účtu)',
    'column_opposing-number'          => 'Opposing account (account number)',
    'column_note'                     => 'Poznámky',
    'column_internal-reference'       => 'Interní reference',

    // error message
    'duplicate_row'                   => 'Row #:row (":description") could not be imported. It already exists.',

];

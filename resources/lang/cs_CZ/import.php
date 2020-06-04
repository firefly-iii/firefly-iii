<?php

/**
 * import.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    // ALL breadcrumbs and subtitles:
    'index_breadcrumb'                    => 'Importovat data do Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Prerequisites for the fake import provider',
    'prerequisites_breadcrumb_spectre'    => 'Prerequisites for Spectre',
    'job_configuration_breadcrumb'        => 'Nastavení pro „:key“',
    'job_status_breadcrumb'               => 'Stav importu pro „:key“',
    'disabled_for_demo_user'              => 'v ukázce vypnuté',

    // index page:
    'general_index_intro'                 => 'Vítejte v rutině importu do Firefly III. Data je možné importovat vícero způsoby, zobrazenými zde jako tlačítka.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that the CSV importer will be moved to a new, separate tool. You can already beta-test this tool if you visit <a href="https://github.com/firefly-iii/csv-importer">this GitHub repository</a>. I would appreciate it if you would test the new importer and let me know what you think.',
    'final_csv_import'     => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that this is the last version of Firefly III that will feature a CSV importer. A separated tool is available that you should try for yourself: <a href="https://github.com/firefly-iii/csv-importer">the Firefly III CSV importer</a>. I would appreciate it if you would test the new importer and let me know what you think.',

    // import provider strings (index):
    'button_fake'                         => 'Simulovat import',
    'button_file'                         => 'Importovat soubor',
    'button_spectre'                      => 'Importovat pomocí Spectre',

    // prerequisites box (index)
    'need_prereq_title'                   => 'Import prerequisites',
    'need_prereq_intro'                   => 'Some import methods need your attention before they can be used. For example, they might require special API keys or application secrets. You can configure them here. The icon indicates if these prerequisites have been met.',
    'do_prereq_fake'                      => 'Prerequisites for the fake provider',
    'do_prereq_file'                      => 'Prerequisites for file imports',
    'do_prereq_spectre'                   => 'Předpoklady pro importy z Spectre',

    // prerequisites:
    'prereq_fake_title'                   => 'Prerequisites for an import from the fake import provider',
    'prereq_fake_text'                    => 'This fake provider requires a fake API key. It must be 32 characters long. You can use this one: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Prerequisites for an import using the Spectre API',
    'prereq_spectre_text'                 => 'In order to import data using the Spectre API (v4), you must provide Firefly III with two secret values. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_spectre_pub'                  => 'Likewise, the Spectre API needs to know the public key you see below. Without it, it will not recognize you. Please enter this public key on your <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'callback_not_tls'                    => 'Firefly III zjistilo následující URI adresu zpětného volání. Zdá se, že váš server není nastaven tak, aby přijímal TLS připojení (https). YNAB tuto URI nepřijme. Můžete pokračovat v importu (protože Firefly III se může mýlit), ale mějte to na paměti.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Atrapa API klíče úspěšně uložena!',
    'prerequisites_saved_for_spectre'     => 'Identif. aplikace a heslo uloženo!',

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
    'should_download_config'              => 'You should download <a href=":route">the configuration file</a> for this job. This will make future imports way easier.',
    'share_config_file'                   => 'If you have imported data from a public bank, you should <a href="https://github.com/firefly-iii/import-configurations/wiki">share your configuration file</a> so it will be easy for other users to import their data. Sharing your configuration file will not expose your financial details.',

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

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Import setup (4/4) - Connect import data to Firefly III data',
    'job_config_map_text'             => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'job_config_map_nothing'          => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',
    'job_config_field_value'          => 'Hodnota v kolonce',
    'job_config_field_mapped'         => 'Mapováno na',
    'map_do_not_map'                  => '(nemapovat)',
    'job_config_map_submit'           => 'Zahájit import',


    // import status page:
    'import_with_key'                 => 'Importováno s klíčem „:key“',
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

    // error message
    'duplicate_row'                   => 'Row #:row (":description") could not be imported. It already exists.',

];

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
    'index_breadcrumb'                    => 'Importuj dane do Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Wymagania dla dostawcy fałszywego importu',
    'prerequisites_breadcrumb_spectre'    => 'Wymagania dla Spectre',
    'job_configuration_breadcrumb'        => 'Konfiguracja dla ":key"',
    'job_status_breadcrumb'               => 'Status importu dla ":key"',
    'disabled_for_demo_user'              => 'zablokowane na demo',

    // index page:
    'general_index_intro'                 => 'Witamy w procedurze importu Firefly III. Istnieje kilka sposobów importowania danych do Firefly III.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'Zgodnie z <a href="https://www.patreon.com/posts/future-updates-30012174">tym wpisem na Patreon</a>, sposób, w jaki Firefly III zarządza importem danych zmieni się. Oznacza to, że importer CSV zostanie przeniesiony do nowego, oddzielnego narzędzia. Możesz już przetestować beta, jeśli odwiedzisz <a href="https://github.com/firefly-iii/csv-importer">repozytorium GitHub</a>. Byłbym wdzięczny, gdybyś przetestował nowego importera i dał mi znać, co myślisz.',
    'final_csv_import'     => 'Zgodnie z <a href="https://www.patreon.com/posts/future-updates-30012174">tym wpisem na Patreon</a> sposób, w jaki Firefly III zarządza importem danych zmieni się. Oznacza to, że importer jest to ostatnia wersja z importerem CSV. Nowe oddzielne narzędzie będzie dostępne. Możesz je już przetestować, jeśli odwiedzisz <a href="https://github.com/firefly-iii/csv-importer">repozytorium GitHub</a>. Byłbym wdzięczny, gdybyś przetestował nowego importera i dał mi znać, co myślisz.',

    // import provider strings (index):
    'button_fake'                         => 'Fałszywy import',
    'button_file'                         => 'Importuj plik',
    'button_spectre'                      => 'Importuj za pomocą Spectre',

    // prerequisites box (index)
    'need_prereq_title'                   => 'Wymagania importu',
    'need_prereq_intro'                   => 'Niektóre metody importu wymagają Twojej uwagi zanim będą mogły być użyte. Na przykład, mogą wymagać specjalnych kluczy API lub sekretów aplikacji. Tutaj możesz je skonfigurować. Ikonka wskazuje czy wymagania zostały spełnione.',
    'do_prereq_fake'                      => 'Wymagania dla fałszywego dostawcy',
    'do_prereq_file'                      => 'Wymagania dla importu plików',
    'do_prereq_spectre'                   => 'Wymagania dla importu za pomocą Spectre',

    // prerequisites:
    'prereq_fake_title'                   => 'Wymagania dla importu używającego fałszywego dostawcy importu',
    'prereq_fake_text'                    => 'Ten fałszywy dostawca wymaga fałszywego klucza API. Musi on mieć 32 znaki. Możesz go użyć: 1234567890123456789012345901234567890AA',
    'prereq_spectre_title'                => 'Wymagania wstępne do importowania za pomocą Spectre API',
    'prereq_spectre_text'                 => 'Aby importować dane za pomocą interfejsu Spectre API (v4), musisz dostarczyć Firefly III dwie tajne wartości. Można je znaleźć na <a href="https://www.saltedge.com/clients/profile/secrets">stronie sekretów</a>.',
    'prereq_spectre_pub'                  => 'API Spectre musi znać klucz publiczny, który widzisz poniżej. Bez niego nie będzie Cię rozpoznawał. Wprowadź ten klucz publiczny na <a href="https://www.saltedge.com/clients/profile/secrets">stronie sekretów</a>.',
    'callback_not_tls'                    => 'Firefly III wykrył następujący URI wywołania zwrotnego. Wygląda na to, że Twój serwer nie jest skonfigurowany do akceptowania połączeń TLS (https). YNAB nie zaakceptuje tego URI. Możesz kontynuować import (ponieważ Firefly III może się mylić) ale miej to na uwadze.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Fałszywy klucz API zapisany pomyślnie!',
    'prerequisites_saved_for_spectre'     => 'Zapisano identyfikator aplikacji i sekret aplikacji!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Konfiguracja zadania - zastosować twoje reguły?',
    'job_config_apply_rules_text'         => 'Once the fake provider has run, your rules can be applied to the transactions. This adds time to the import.',
    'job_config_input'                    => 'Your input',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Podaj nazwę albumu',
    'job_config_fake_artist_text'         => 'Many import routines have a few configuration steps you must go through. In the case of the fake import provider, you must answer some weird questions. In this case, enter "David Bowie" to continue.',
    'job_config_fake_song_title'          => 'Podaj nazwę piosenki',
    'job_config_fake_song_text'           => 'Mention the song "Golden years" to continue with the fake import.',
    'job_config_fake_album_title'         => 'Podaj nazwę albumu',
    'job_config_fake_album_text'          => 'Some import routines require extra data halfway through the import. In the case of the fake import provider, you must answer some weird questions. Enter "Station to station" to continue.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Konfiguracja importu (1/4) - Prześlij swój plik',
    'job_config_file_upload_text'         => 'This routine will help you import files from your bank into Firefly III. ',
    'job_config_file_upload_help'         => 'Select your file. Please make sure the file is UTF-8 encoded.',
    'job_config_file_upload_config_help'  => 'If you have previously imported data into Firefly III, you may have a configuration file, which will pre-set configuration values for you. For some banks, other users have kindly provided their <a href="https://github.com/firefly-iii/import-configurations/wiki">configuration file</a>',
    'job_config_file_upload_type_help'    => 'Wybierz typ pliku, który będziesz przesyłać',
    'job_config_file_upload_submit'       => 'Prześlij pliki',
    'import_file_type_csv'                => 'CSV (wartości oddzielone przecinkami)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'The file you have uploaded is not encoded as UTF-8 or ASCII. Firefly III cannot handle such files. Please use Notepad++ or Sublime to convert your file to UTF-8.',
    'job_config_uc_title'                 => 'Konfiguracja importu (2/4) - Podstawowa konfiguracja pliku',
    'job_config_uc_text'                  => 'Abyś mógł poprawnie zaimportować plik, sprawdź poprawność poniższych opcji.',
    'job_config_uc_header_help'           => 'Zaznacz to pole, jeśli pierwszy wiersz w pliku CSV to nazwy kolumn.',
    'job_config_uc_date_help'             => 'Date time format in your file. Follow the format as <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Wybierz separator pola, który jest używany w pliku wejściowym. Jeśli nie jesteś pewien, przecinek jest najbezpieczniejszym rozwiązaniem.',
    'job_config_uc_account_help'          => 'Jeśli Twój plik NIE zawiera informacji o Twoich kontach aktywów, użyj tego menu, aby wybrać, do którego konta należą transakcje w pliku.',
    'job_config_uc_apply_rules_title'     => 'Zastosuj reguły',
    'job_config_uc_apply_rules_text'      => 'Applies your rules to every imported transaction. Note that this slows the import significantly.',
    'job_config_uc_specifics_title'       => 'Bank-specific options',
    'job_config_uc_specifics_txt'         => 'Some banks deliver badly formatted files. Firefly III can fix those automatically. If your bank delivers such files but it\'s not listed here, please open an issue on GitHub.',
    'job_config_uc_submit'                => 'Kontynuuj',
    'invalid_import_account'              => 'You have selected an invalid account to import into.',
    'import_liability_select'             => 'Zobowiązanie',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Wybierz swój login',
    'job_config_spectre_login_text'       => 'Firefly III has found :count existing login(s) in your Spectre account. Which one would you like to use to import from?',
    'spectre_login_status_active'         => 'Aktywny',
    'spectre_login_status_inactive'       => 'Nieaktywny',
    'spectre_login_status_disabled'       => 'Wyłączony',
    'spectre_login_new_login'             => 'Login with another bank, or one of these banks with different credentials.',
    'job_config_spectre_accounts_title'   => 'Wybierz konta do zaimportowania z',
    'job_config_spectre_accounts_text'    => 'You have selected ":name" (:country). You have :count account(s) available from this provider. Please select the Firefly III asset account(s) where the transactions from these accounts should be stored. Remember, in order to import data both the Firefly III account and the ":name"-account must have the same currency.',
    'spectre_do_not_import'               => '(nie importuj)',
    'spectre_no_mapping'                  => 'Wygląda na to, że nie wybrałeś żadnych kont z których można zaimportować dane.',
    'imported_from_account'               => 'Zaimportowane z ":account"',
    'spectre_account_with_number'         => 'Konto :number',
    'job_config_spectre_apply_rules'      => 'Zastosuj reguły',
    'job_config_spectre_apply_rules_text' => 'By default, your rules will be applied to the transactions created during this import routine. If you do not want this to happen, deselect this checkbox.',

    // job configuration for bunq:
    'should_download_config'              => 'You should download <a href=":route">the configuration file</a> for this job. This will make future imports way easier.',
    'share_config_file'                   => 'If you have imported data from a public bank, you should <a href="https://github.com/firefly-iii/import-configurations/wiki">share your configuration file</a> so it will be easy for other users to import their data. Sharing your configuration file will not expose your financial details.',

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

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Import setup (4/4) - Connect import data to Firefly III data',
    'job_config_map_text'             => 'In the following tables, the left value shows you information found in your uploaded file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',
    'job_config_map_nothing'          => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',
    'job_config_field_value'          => 'Wartość pola',
    'job_config_field_mapped'         => 'Zmapowane na',
    'map_do_not_map'                  => '(nie mapuj)',
    'job_config_map_submit'           => 'Rozpocznij import',


    // import status page:
    'import_with_key'                 => 'Import z kluczem \':key\'',
    'status_wait_title'               => 'Proszę czekać...',
    'status_wait_text'                => 'To pole za chwilę zniknie.',
    'status_running_title'            => 'Trwa importowanie',
    'status_job_running'              => 'Proszę czekać, trwa importowanie danych...',
    'status_job_storing'              => 'Proszę czekać, zapisuję dane...',
    'status_job_rules'                => 'Proszę czekać, trwa procesowanie reguł...',
    'status_fatal_title'              => 'Błąd krytyczny',
    'status_fatal_text'               => 'The import has suffered from an error it could not recover from. Apologies!',
    'status_fatal_more'               => 'This (possibly very cryptic) error message is complemented by log files, which you can find on your hard drive, or in the Docker container where you run Firefly III from.',
    'status_finished_title'           => 'Import zakończony',
    'status_finished_text'            => 'Importowanie zostało zakończone.',
    'finished_with_errors'            => 'There were some errors during the import. Please review them carefully.',
    'unknown_import_result'           => 'Nieznany wynik importu',
    'result_no_transactions'          => 'No transactions have been imported. Perhaps they were all duplicates is simply no transactions where present to be imported. Perhaps the log files can tell you what happened. If you import data regularly, this is normal.',
    'result_one_transaction'          => 'Exactly one transaction has been imported. It is stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect it further.',
    'result_many_transactions'        => 'Firefly III has imported :count transactions. They are stored under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> where you can inspect them further.',

    // general errors and warnings:
    'bad_job_status'                  => 'To access this page, your import job cannot have status ":status".',

    // error message
    'duplicate_row'                   => 'Wiersz #:row (":description") nie mógł zostać zaimportowany. Już istnieje.',

];

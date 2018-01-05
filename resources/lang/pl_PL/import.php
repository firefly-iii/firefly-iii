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
    'status_wait_title'               => 'Proszę czekać...',
    'status_wait_text'                => 'To pole za chwilę zniknie.',
    'status_fatal_title'              => 'Wystąpił błąd krytyczny',
    'status_fatal_text'               => 'Wystąpił błąd krytyczny, którego procedura importu nie może naprawić. Zobacz wyjaśnienie na czerwono poniżej.',
    'status_fatal_more'               => 'Jeśli przekroczono limit czasu, import zostanie zatrzymany w połowie. W przypadku niektórych konfiguracji serwerów, jedynie serwer przestał odpowiadać podczas gdy importowanie nadal działa w tle. Aby to zweryfikować, należy sprawdzić pliki dziennika. Jeśli problem będzie się powtarzał, należy rozważyć Importowanie poprzez konsolę.',
    'status_ready_title'              => 'Import jest gotowy do uruchomienia',
    'status_ready_text'               => 'Import jest gotowy do uruchomienia. Cała konfiguracja, którą musisz wykonać, została wykonana. Proszę pobierz plik konfiguracyjny. Pomoże Ci w imporcie, jeśli nie pójdzie zgodnie z planem. Aby faktycznie uruchomić import, możesz wykonać następujące polecenie w konsoli lub uruchomić importowanie przez przeglądarkę www. W zależności od konfiguracji import przez konsolę daje więcej informacji zwrotnych.',
    'status_ready_noconfig_text'      => 'Import jest gotowy do uruchomienia. Cała konfiguracja, którą musisz wykonać, została wykonana. Aby faktycznie uruchomić import, możesz wykonać następujące polecenie w konsoli lub uruchomić importowanie przez przeglądarkę www. W zależności od konfiguracji import przez konsolę daje więcej informacji zwrotnych.',
    'status_ready_config'             => 'Pobierz konfigurację',
    'status_ready_start'              => 'Rozpocznij Importowanie',
    'status_ready_share'              => 'Rozważ pobranie konfiguracji i udostępnienie jej w <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">centrum konfiguracyjnym portali</a></strong>. Umożliwi to innym użytkownikom Firefly III łatwiejsze importowanie plików.',
    'status_job_new'                  => 'Zadanie jest zupełnie nowe.',
    'status_job_configuring'          => 'Import jest konfigurowany.',
    'status_job_configured'           => 'Import jest skonfigurowany.',
    'status_job_running'              => 'Import w toku... Proszę czekać..',
    'status_job_error'                => 'Zadanie wygenerowało błąd.',
    'status_job_finished'             => 'Importowanie zostało zakończone!',
    'status_running_title'            => 'Trwa importowanie',
    'status_running_placeholder'      => 'Proszę czekać na aktualizację...',
    'status_finished_title'           => 'Zakończono procedurę importu',
    'status_finished_text'            => 'Twoje dane zostały zaimportowane.',
    'status_errors_title'             => 'Błędy podczas importowania',
    'status_errors_single'            => 'Wystąpił błąd podczas importowania. Nie wydaje się być krytyczny.',
    'status_errors_multi'             => 'Wystąpiły błędy podczas importowania. Nie wydają się być krytyczne.',
    'status_bread_crumb'              => 'Status importu',
    'status_sub_title'                => 'Status importu',
    'config_sub_title'                => 'Skonfiguruj import',
    'status_finished_job'             => 'Zaimportowane transakcje można znaleźć w tagu <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    'import_with_key'                 => 'Import z kluczem \':key\'',

    // file, upload something
    'file_upload_title'               => 'Konfiguracja importu (1/4) - Prześlij swój plik',
    'file_upload_text'                => 'Ta procedura pomoże Ci importować pliki z twojego banku do Firefly III. Sprawdź stronę pomocy w prawym górnym rogu.',
    'file_upload_fields'              => 'Pola',
    'file_upload_help'                => 'Wybierz swój plik',
    'file_upload_config_help'         => 'Jeśli wcześniej importowałeś dane do Firefly III, możesz posiadać plik konfiguracji, który wstępnie ustawi wartości parametrów konfiguracyjnych za Ciebie. Dla niektórych banków, inni użytkownicy uprzejmie dostarczyli swoje <a href="https://github.com/firefly-iii/import-configurations/wiki">pliki konfiguracji</a>',
    'file_upload_type_help'           => 'Wybierz typ pliku, który będziesz przesyłać',
    'file_upload_submit'              => 'Prześlij pliki',

    // file, upload types
    'import_file_type_csv'            => 'CSV (wartości oddzielone przecinkami)',

    // file, initial config for CSV
    'csv_initial_title'               => 'Konfiguracja importu (2/4) - Podstawowa konfiguracja importu CSV',
    'csv_initial_text'                => 'Aby móc poprawnie zaimportować plik, sprawdź poprawność poniższych opcji.',
    'csv_initial_box'                 => 'Podstawowa konfiguracja importu CSV',
    'csv_initial_box_title'           => 'Podstawowe opcje konfiguracji importu CSV',
    'csv_initial_header_help'         => 'Zaznacz to pole, jeśli pierwszy wiersz w pliku CSV to nazwy kolumn.',
    'csv_initial_date_help'           => 'Format daty i czasu w pliku CSV. Format powinien być zgodny z opisem na <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">tej</a> stronie. Wartość domyślna będzie analizować daty, które wyglądają następująco: :dateExample.',
    'csv_initial_delimiter_help'      => 'Wybierz separator pola, który jest używany w pliku wejściowym. Jeśli nie jesteś pewien, przecinek jest najbezpieczniejszym rozwiązaniem.',
    'csv_initial_import_account_help' => 'Jeśli Twój plik CSV NIE zawiera informacji o Twoich kontach aktywów, użyj tego menu, aby wybrać, do którego konta należą transakcje w pliku CSV.',
    'csv_initial_submit'              => 'Przejdź do kroku 3/4',

    // file, new options:
    'file_apply_rules_title'          => 'Zastosuj reguły',
    'file_apply_rules_description'    => 'Zastosuj swoje reguły. Zwróć uwagę, że to znacznie spowalnia importowanie.',
    'file_match_bills_title'          => 'Dopasuj rachunki',
    'file_match_bills_description'    => 'Dopasuj swoje rachunki do nowo utworzonych wypłat. Zwróć uwagę, że to znacznie spowalnia importowanie.',

    // file, roles config
    'csv_roles_title'                 => 'Konfiguracja importu (3/4) - Zdefiniuj rolę każdej kolumny',
    'csv_roles_text'                  => 'Każda kolumna w pliku CSV zawiera określone dane. Proszę wskazać, jakiego rodzaju danych importer powinien oczekiwać. Opcja "mapowania" danych oznacza, że każdy wpis znaleziony w kolumnie zostanie połączony z wartością w bazie danych. Często odwzorowywana kolumna to kolumna zawierająca numer IBAN konta przeciwnego. Można go łatwo dopasować do obecnego numeru IBAN w bazie danych.',
    'csv_roles_table'                 => 'Tabela',
    'csv_roles_column_name'           => 'Nazwa kolumny',
    'csv_roles_column_example'        => 'Przykładowe dane kolumny',
    'csv_roles_column_role'           => 'Znaczenie danych w kolumnie',
    'csv_roles_do_map_value'          => 'Zmapuj te wartości',
    'csv_roles_column'                => 'Kolumna',
    'csv_roles_no_example_data'       => 'Brak przykładowych danych',
    'csv_roles_submit'                => 'Przejdź do kroku 4/4',

    // not csv, but normal warning
    'roles_warning'                   => 'Zaznacz jedną z kolumn jako kolumnę z kwotami. Wskazane jest również wybranie kolumny dla opisu, daty oraz konta przeciwnego.',

    // file, map data
    'file_map_title'                  => 'Ustawienia importu (4/4) - Połącz dane importu z danymi Firefly III',
    'file_map_text'                   => 'W poniższych tabelach lewa wartość pokazuje informacje znalezione w przesłanym pliku. Twoim zadaniem jest zamapowanie tej wartości, jeśli to możliwe, na wartość już obecną w bazie danych. Firefly będzie trzymać się tego mapowania. Jeśli nie ma wartości do odwzorowania lub nie chcesz mapować określonej wartości, nie wybieraj niczego.',
    'file_map_field_value'            => 'Wartość pola',
    'file_map_field_mapped_to'        => 'Zmapowane do',
    'map_do_not_map'                  => '(nie mapuj)',
    'file_map_submit'                 => 'Rozpocznij import',
    'file_nothing_to_map'             => 'W twoim pliku nie ma danych, które można by odwzorować na istniejące wartości. Naciśnij "Rozpocznij import", aby kontynuować.',

    // map things.
    'column__ignore'                  => '(zignoruj tę kolumnę)',
    'column_account-iban'             => 'Konto aktywów (IBAN)',
    'column_account-id'               => 'ID konta aktywów (taki sam jak w Firefly)',
    'column_account-name'             => 'Konto aktywów (nazwa)',
    'column_amount'                   => 'Kwota',
    'column_amount_debit'             => 'Kwota (kolumna debetowa)',
    'column_amount_credit'            => 'Kwota (kolumna kredytowa)',
    'column_amount-comma-separated'   => 'Kwota (przecinek jako separator dziesiętny)',
    'column_bill-id'                  => 'ID rachunku (taki sam jak w Firefly)',
    'column_bill-name'                => 'Nazwa rachunku',
    'column_budget-id'                => 'ID budżetu (taki sam jak w Firefly)',
    'column_budget-name'              => 'Nazwa budżetu',
    'column_category-id'              => 'ID kategorii (taki sam jak w Firefly)',
    'column_category-name'            => 'Nazwa kategorii',
    'column_currency-code'            => 'Kod waluty (ISO 4217)',
    'column_currency-id'              => 'ID waluty (taki sam jak w Firefly)',
    'column_currency-name'            => 'Nazwa waluty (taka sama jak w Firefly)',
    'column_currency-symbol'          => 'Symbol waluty (taki sam jak w Firefly)',
    'column_date-interest'            => 'Data obliczenia odsetek',
    'column_date-book'                => 'Data księgowania transakcji',
    'column_date-process'             => 'Data przetworzenia transakcji',
    'column_date-transaction'         => 'Data',
    'column_description'              => 'Opis',
    'column_opposing-iban'            => 'Przeciwstawne konto (IBAN)',
    'column_opposing-id'              => 'ID przeciwstawnego konta (takie same jak w Firefly)',
    'column_external-id'              => 'Zewnętrzne ID',
    'column_opposing-name'            => 'Przeciwstawne konto (nazwa)',
    'column_rabo-debit-credit'        => 'Specyficzny wskaźnik obciążenia/kredytu Rabobank',
    'column_ing-debit-credit'         => 'Specyficzny wskaźnik obciążenia/kredytu ING',
    'column_sepa-ct-id'               => 'SEPA transferu od końca do końca ID',
    'column_sepa-ct-op'               => 'SEPA przelew na przeciwne konto',
    'column_sepa-db'                  => 'SEPA polecenie zapłaty',
    'column_tags-comma'               => 'Tagi (oddzielone przecinkami)',
    'column_tags-space'               => 'Tagi (oddzielone spacjami)',
    'column_account-number'           => 'Konto aktywów (numer konta)',
    'column_opposing-number'          => 'Konto przeciwne (numer konta)',
    'column_note'                     => 'Notatki',

    // prerequisites
    'prerequisites'                   => 'Wymagania',

    // bunq
    'bunq_prerequisites_title'        => 'Wymagania wstępne dla importu z bunq',
    'bunq_prerequisites_text'         => 'Aby zaimportować z bunq, musisz uzyskać klucz API. Możesz to zrobić za pomocą aplikacji.',

    // Spectre
    'spectre_title'                   => 'Importuj za pomocą Spectre',
    'spectre_prerequisites_title'     => 'Wymagania wstępne do importowania za pomocą Spectre',
    'spectre_prerequisites_text'      => 'Aby importować dane za pomocą interfejsu Spectre API, musisz dostarczyć Firefly III dwie sekretne wartości. Można je znaleźć na <a href="https://www.saltedge.com/clients/profile/secrets">stronie sekretów</a>.',
    'spectre_enter_pub_key'           => 'Importowanie będzie działać tylko po wpisaniu tego klucza publicznego na <a href="https://www.saltedge.com/clients/security/edit">stronie zabezpieczeń</a>.',
];

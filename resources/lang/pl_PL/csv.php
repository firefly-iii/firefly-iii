<?php
/**
 * csv.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

return [

    // initial config
    'initial_title'                 => 'Konfiguracja importu (1/3) - Podstawowa konfiguracja importu CSV',
    'initial_text'                  => 'Abyś mógł poprawnie zaimportować plik, sprawdź poprawność poniższych opcji.',
    'initial_box'                   => 'Podstawowa konfiguracja importu CSV',
    'initial_box_title'             => '',
    'initial_header_help'           => '',
    'initial_date_help'             => '',
    'initial_delimiter_help'        => '',
    'initial_import_account_help'   => '',
    'initial_submit'                => 'Przejdź do kroku 2/3',

    // roles config
    'roles_title'                   => 'Konfiguracja importu (2/3) - Określ rolę każdej z kolumn',
    'roles_text'                    => '',
    'roles_table'                   => 'Tabela',
    'roles_column_name'             => 'Nazwa kolumny',
    'roles_column_example'          => 'Przykładowe dane w kolumnie',
    'roles_column_role'             => 'Znaczenie danych w kolumnie',
    'roles_do_map_value'            => 'Zmapuj te wartości',
    'roles_column'                  => 'Kolumna',
    'roles_no_example_data'         => 'Brak dostępnych danych przykładowych',
    'roles_submit'                  => 'Przejdź do kroku 3/3',
    'roles_warning'                 => '',

    // map data
    'map_title'                     => 'Konfiguracja importu (3/3) - Połącz importowane dane z danymi w Firefly III',
    'map_text'                      => '',
    'map_field_value'               => 'Wartość pola',
    'map_field_mapped_to'           => 'Zmapowane na',
    'map_do_not_map'                => '(nie mapuj)',
    'map_submit'                    => 'Rozpocznij Importowanie',

    // map things.
    'column__ignore'                => '(ignoruj tę kolumnę)',
    'column_account-iban'           => 'Konto aktywów (IBAN)',
    'column_account-id'             => 'ID konta aktywów (taki sam jak w Firefly)',
    'column_account-name'           => 'Konto aktywów (nazwa)',
    'column_amount'                 => 'Kwota',
    'column_amount-comma-separated' => 'Kwota (przecinek jako separator dziesiętny)',
    'column_bill-id'                => 'ID rachunku (taki sam jak w Firefly)',
    'column_bill-name'              => 'Nazwa rachunku',
    'column_budget-id'              => 'ID budżetu (taki sam jak w Firefly)',
    'column_budget-name'            => 'Nazwa budżetu',
    'column_category-id'            => 'ID kategorii (taki sam jak w Firefly)',
    'column_category-name'          => 'Nazwa kategorii',
    'column_currency-code'          => 'Kod waluty (ISO 4217)',
    'column_currency-id'            => 'ID waluty (taki sam jak w Firefly)',
    'column_currency-name'          => 'Nazwa waluty (taka sama jak w Firefly)',
    'column_currency-symbol'        => 'Symbol waluty (taki sam jak w Firefly)',
    'column_date-interest'          => '',
    'column_date-book'              => 'Data księgowania transakcji',
    'column_date-process'           => '',
    'column_date-transaction'       => 'Data',
    'column_description'            => 'Opis',
    'column_opposing-iban'          => '',
    'column_opposing-id'            => '',
    'column_external-id'            => 'Zewnętrzne ID',
    'column_opposing-name'          => '',
    'column_rabo-debet-credit'      => '',
    'column_ing-debet-credit'       => '',
    'column_sepa-ct-id'             => '',
    'column_sepa-ct-op'             => '',
    'column_sepa-db'                => '',
    'column_tags-comma'             => 'Tagi (oddzielone przecinkami)',
    'column_tags-space'             => 'Tagi (oddzielone spacjami)',
    'column_account-number'         => 'Konto aktywów (numer konta)',
    'column_opposing-number'        => '',
];
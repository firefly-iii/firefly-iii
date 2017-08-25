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
    'initial_title'                 => '',
    'initial_text'                  => '',
    'initial_box'                   => '',
    'initial_box_title'             => '',
    'initial_header_help'           => '',
    'initial_date_help'             => '',
    'initial_delimiter_help'        => '',
    'initial_import_account_help'   => '',
    'initial_submit'                => '',

    // roles config
    'roles_title'                   => '',
    'roles_text'                    => '',
    'roles_table'                   => '',
    'roles_column_name'             => '',
    'roles_column_example'          => '',
    'roles_column_role'             => '',
    'roles_do_map_value'            => '',
    'roles_column'                  => '',
    'roles_no_example_data'         => '',
    'roles_submit'                  => '',
    'roles_warning'                 => '',

    // map data
    'map_title'                     => '',
    'map_text'                      => 'Vrednosti na levi v spodnji tabeli prikazujejo podatke iz naložene CSV datoteke. Vaša naloga je, da jim, če je možno, določite obtoječio vrednost iz podatkovne baze. Firefly bo to upošteval pri uvozu. Če v podatkovni bazi ni ustrezne vrednosti, ali vrednosti ne želite določiti ničesar, potem pustite prazno.',
    'map_field_value'               => '',
    'map_field_mapped_to'           => '',
    'map_do_not_map'                => '',
    'map_submit'                    => '',

    // map things.
    'column__ignore'                => '(ignoriraj ta stolpec)',
    'column_account-iban'           => 'premoženjski račun (IBAN)',
    'column_account-id'             => 'ID premoženjskega računa (Firefly)',
    'column_account-name'           => 'premoženjski račun (ime)',
    'column_amount'                 => 'znesek',
    'column_amount-comma-separated' => 'znesek (z decimalno vejico)',
    'column_bill-id'                => 'ID trajnika (Firefly)',
    'column_bill-name'              => 'Ime trajnika',
    'column_budget-id'              => 'ID bugžeta (Firefly)',
    'column_budget-name'            => 'ime budžeta',
    'column_category-id'            => 'ID Kategorije (Firefly)',
    'column_category-name'          => 'ime kategorije',
    'column_currency-code'          => 'koda valute (ISO 4217)',
    'column_currency-id'            => 'ID valute (Firefly)',
    'column_currency-name'          => 'ime valute (Firefly)',
    'column_currency-symbol'        => 'simbol valute (Firefly)',
    'column_date-interest'          => 'Datum obračuna obresti',
    'column_date-book'              => 'datum knjiženja transakcije',
    'column_date-process'           => 'datum izvedbe transakcije',
    'column_date-transaction'       => 'datum',
    'column_description'            => 'opis',
    'column_opposing-iban'          => 'ciljni račun (IBAN)',
    'column_opposing-id'            => 'protiračun (firefly)',
    'column_external-id'            => 'zunanja ID številka',
    'column_opposing-name'          => 'ime ciljnega računa',
    'column_rabo-debet-credit'      => 'Poseben indikator za Rabobank',
    'column_ing-debet-credit'       => 'Poseben indikator za banko ING',
    'column_sepa-ct-id'             => 'SEPA številka transakcije',
    'column_sepa-ct-op'             => 'SEPA protiračun',
    'column_sepa-db'                => 'SEPA direktna obremenitev',
    'column_tags-comma'             => 'značke (ločene z vejicami)',
    'column_tags-space'             => 'značke (ločene s presledki)',
    'column_account-number'         => 'premoženjski račun (številka računa)',
    'column_opposing-number'        => 'protiračun (številka računa)',
];
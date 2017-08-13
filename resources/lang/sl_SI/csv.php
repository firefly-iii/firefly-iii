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
    'initial_title'                 => 'Import setup (1/3) - Basic CSV import setup',
    'initial_text'                  => 'To be able to import your file correctly, please validate the options below.',
    'initial_box'                   => 'Basic CSV import setup',
    'initial_box_title'             => 'Basic CSV import setup options',
    'initial_header_help'           => 'Check this box if the first row of your CSV file are the column titles.',
    'initial_date_help'             => 'Date time format in your CSV. Follow the format like <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'initial_delimiter_help'        => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'initial_import_account_help'   => 'If your CSV file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the CSV belong to.',
    'initial_submit'                => 'Continue with step 2/3',

    // roles config
    'roles_title'                   => 'Import setup (2/3) - Define each column\'s role',
    'roles_text'                    => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'roles_table'                   => 'Table',
    'roles_column_name'             => 'Name of column',
    'roles_column_example'          => 'Column example data',
    'roles_column_role'             => 'Column data meaning',
    'roles_do_map_value'            => 'Map these values',
    'roles_column'                  => 'Column',
    'roles_no_example_data'         => 'No example data available',
    'roles_submit'                  => 'Continue with step 3/3',
    'roles_warning'                 => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',

    // map data
    'map_title'                     => 'Import setup (3/3) - Connect import data to Firefly III data',
    'map_text'                      => 'Vrednosti na levi v spodnji tabeli prikazujejo podatke iz naložene CSV datoteke. Vaša naloga je, da jim, če je možno, določite obtoječio vrednost iz podatkovne baze. Firefly bo to upošteval pri uvozu. Če v podatkovni bazi ni ustrezne vrednosti, ali vrednosti ne želite določiti ničesar, potem pustite prazno.',
    'map_field_value'               => 'Field value',
    'map_field_mapped_to'           => 'Mapped to',
    'map_do_not_map'                => '(do not map)',
    'map_submit'                    => 'Start the import',

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
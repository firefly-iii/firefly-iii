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

    // map data
    'map_title'                     => 'Import setup (3/3) - Connect import data to Firefly III data',
    'map_text'                      => 'In den folgenden Tabellen zeigt der linke Wert Informationen, die sich in Ihrer hochgeladenen CSV-Datei befinden. Es ist Ihre Aufgabe, diesen Wert, wenn möglich, einem bereits in der Datenbank vorhandem zuzuordnen. Firefly wird sich an diese Zuordnung halten. Wenn kein Wert für die Zuordnung vorhanden ist oder Sie den bestimmten Wert nicht abbilden möchten, wählen Sie nichts aus.',
    'map_field_value'               => 'Field value',
    'map_field_mapped_to'           => 'Mapped to',
    'map_do_not_map'                => '(do not map)',
    'map_submit'                    => 'Start the import',

    // map things.
    'column__ignore'                => '(diese Spalte ignorieren)',
    'column_account-iban'           => 'Bestandskonto (IBAN)',
    'column_account-id'             => 'Bestandskonto (vgl. ID in Firefly)',
    'column_account-name'           => 'Bestandskonto (Name)',
    'column_amount'                 => 'Betrag',
    'column_amount-comma-separated' => 'Betrag (Komma als Dezimaltrennzeichen)',
    'column_bill-id'                => 'Rechnung (ID übereinstimmend mit Firefly)',
    'column_bill-name'              => 'Name der Rechnung',
    'column_budget-id'              => 'Bidget (ID übereinstimmend mit Firefly)',
    'column_budget-name'            => 'Budgetname',
    'column_category-id'            => 'Kategorie (ID übereinstimmend mit Firefly)',
    'column_category-name'          => 'Name der Kategorie',
    'column_currency-code'          => 'Währungsstandard (ISO 4217)',
    'column_currency-id'            => 'Währung (ID übereinstimmend mit Firefly)',
    'column_currency-name'          => 'Währungsname (übereinstimmend mit Firefly)',
    'column_currency-symbol'        => 'Währungssysmbol (übereinstimmend mit Firefly)',
    'column_date-interest'          => 'Datum der Zinsberechnung',
    'column_date-book'              => 'Buchungsdatum der Überweisung',
    'column_date-process'           => 'Verarbeitungsdatum der Überweisung',
    'column_date-transaction'       => 'Datum',
    'column_description'            => 'Beschreibung',
    'column_opposing-iban'          => 'Zielkonto (IBAN)',
    'column_opposing-id'            => 'Zielkonto (vgl. ID in Firefly)',
    'column_external-id'            => 'Externe ID',
    'column_opposing-name'          => 'Zielkonto (Name)',
    'column_rabo-debet-credit'      => 'Spezifisches Kennzeichen für Belastung/Kredit der Rabobank',
    'column_ing-debet-credit'       => 'Spezifisches Kennzeichen für Belastung/Kredit der ING',
    'column_sepa-ct-id'             => 'SEPA Überweisungstransaktionsnummer',
    'column_sepa-ct-op'             => 'SEPA Überweisungszielkonto',
    'column_sepa-db'                => 'SEPA Lastschriftnummer',
    'column_tags-comma'             => 'Tags (durch Komma getrennt)',
    'column_tags-space'             => 'Tags (durch Leerzeichen getrennt)',
    'column_account-number'         => 'Bestandskonto (Kontonr.)',
    'column_opposing-number'        => 'Zielkonto (Kontonr.)',
];
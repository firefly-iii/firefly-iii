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

declare(strict_types = 1);

return [

    'import_configure_title' => 'Konfigurieren Sie Ihren Import',
    'import_configure_intro' => 'Es gibt einige Optionen für Ihren CSV-Import. Bitte geben Sie an, ob Ihre CSV-Datei Überschriften in der ersten Spalte enthält und was das Datumsformat in Ihrem Datumsfeld ist. Dieses kann einige Experimente erfordern. Das Trennzeichen ist in der Regel ein ",", könnte aber auch ein "." sein. Bitte überprüfen Sie dieses sorgfältig.',
    'import_configure_form'  => 'Formular',
    'header_help'            => 'Hier auswählen, wenn die ersten Zeilen der CSV-Datei die Spaltenüberschriften sind',
    'date_help'              => 'Datumsformat in ihrer CSV-Datei. Geben Sie das Format so an, wie es <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">diese Seite</a> zeigt. Die Standardeinstellung ergibt Daten die so aussehen: :dateExample.',
    'delimiter_help'         => 'Wählen Sie das Trennzeichen, welches in ihrer Datei genutzt wird. Wenn Sie nicht sicher sind ist Komma die sicherste Option.',
    'import_account_help'    => 'Wenn ihre CSV-Datei KEINE Informationen über ihre Girokonten enthält nutzen Sie bitte diese Dropdown-Liste um anzugeben, zu welchem Girokonto die Transaktionen in de CSV-Datei gehören.',
    'upload_not_writeable'   => 'Das graue Feld enthält einen Dateipfad. Dieser sollte schreibbar sein. Bitte stellen Sie sicher, dass er es ist.',

    // roles
    'column_roles_title'     => 'Define column roles',
    'column_roles_text'      => '<p>Firefly III cannot guess what data each column contains. You must tell Firefly which kinds of data to expect. The example data can guide you into picking the correct type from the dropdown. If a column cannot be matched to a useful data type, please let me know <a href="https://github.com/JC5/firefly-iii/issues/new">by creating an issue</a>.</p><p>Some values in your CSV file, such as account names or categories, may already exist in your Firefly III database. If you select "map these values" Firefly will not attempt to search for matching values itself but allow you to match the CSV values against the values in your database. This allows you to fine-tune the import.</p>',
    'column_roles_table'     => 'Tabelle',
    'column_name'            => 'Name der Spalte',
    'column_example'         => 'Column example data',
    'column_role'            => 'Column data meaning',
    'do_map_value'           => 'Map these values',
    'column'                 => 'Spalte',
    'no_example_data'        => 'Keine Beispieldaten vorhanden',
    'store_column_roles'     => 'Import fortsetzen',
    'do_not_map'             => '(keine Zuordnung)',
    'map_title'              => 'Connect import data to Firefly III data',
    'map_text'               => 'In the following tables, the left value shows you information found in your uploaded CSV file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',

    'field_value'          => 'Field value',
    'field_mapped_to'      => 'Mapped to',
    'store_column_mapping' => 'Store mapping',

    // map things.


    'column__ignore'                => '(diese Spalte ignorieren)',
    'column_account-iban'           => 'Asset account (IBAN)',
    'column_account-id'             => 'Asset account  ID (matching Firefly)',
    'column_account-name'           => 'Asset account (name)',
    'column_amount'                 => 'Betrag',
    'column_amount-comma-separated' => 'Betrag (Komma als Dezimaltrennzeichen)',
    'column_bill-id'                => 'Bill ID (matching Firefly)',
    'column_bill-name'              => 'Name der Rechnung',
    'column_budget-id'              => 'Budget ID (matching Firefly)',
    'column_budget-name'            => 'Budgetname',
    'column_category-id'            => 'Category ID (matching Firefly)',
    'column_category-name'          => 'Name der Kategorie',
    'column_currency-code'          => 'Currency code (ISO 4217)',
    'column_currency-id'            => 'Currency ID (matching Firefly)',
    'column_currency-name'          => 'Currency name (matching Firefly)',
    'column_currency-symbol'        => 'Currency symbol (matching Firefly)',
    'column_date-interest'          => 'Interest calculation date',
    'column_date-book'              => 'Buchungsdatum der Überweisung',
    'column_date-process'           => 'Transaction process date',
    'column_date-transaction'       => 'Datum',
    'column_description'            => 'Beschreibung',
    'column_opposing-iban'          => 'Opposing account (IBAN)',
    'column_opposing-id'            => 'Opposing account ID (matching Firefly)',
    'column_external-id'            => 'External ID',
    'column_opposing-name'          => 'Opposing account (name)',
    'column_rabo-debet-credit'      => 'Rabobank specific debet/credit indicator',
    'column_ing-debet-credit'       => 'ING specific debet/credit indicator',
    'column_sepa-ct-id'             => 'SEPA Credit Transfer end-to-end ID',
    'column_sepa-ct-op'             => 'SEPA Credit Transfer opposing account',
    'column_sepa-db'                => 'SEPA Direct Debet',
    'column_tags-comma'             => 'Tags (durch Komma getrennt)',
    'column_tags-space'             => 'Tags (durch Leerzeichen getrennt)',
    'column_account-number'         => 'Asset account (account number)',
    'column_opposing-number'        => 'Opposing account (account number)',
];

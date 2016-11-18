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
    'import_configure_form'  => 'Standard CSV Importoptionen',
    'header_help'            => 'Hier auswählen, wenn die ersten Zeilen der CSV-Datei die Spaltenüberschriften sind',
    'date_help'              => 'Datumsformat in ihrer CSV-Datei. Geben Sie das Format so an, wie es <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">diese Seite</a> zeigt. Die Standardeinstellung ergibt Daten die so aussehen: :dateExample.',
    'delimiter_help'         => 'Wählen Sie das Trennzeichen, welches in ihrer Datei genutzt wird. Wenn Sie nicht sicher sind ist Komma die sicherste Option.',
    'import_account_help'    => 'Wenn ihre CSV-Datei KEINE Informationen über ihre Girokonten enthält nutzen Sie bitte diese Dropdown-Liste um anzugeben, zu welchem Girokonto die Transaktionen in de CSV-Datei gehören.',
    'upload_not_writeable'   => 'Das graue Feld enthält einen Dateipfad. Dieser sollte schreibbar sein. Bitte stellen Sie sicher, dass er es ist.',

    // roles
    'column_roles_title'     => 'Definieren Sie Spaltenfunktionen',
    'column_roles_table'     => 'Tabelle',
    'column_name'            => 'Name der Spalte',
    'column_example'         => 'Beispieldaten',
    'column_role'            => 'Bedeutung der Spalte',
    'do_map_value'           => 'Ordnen Sie diese Werte zu',
    'column'                 => 'Spalte',
    'no_example_data'        => 'Keine Beispieldaten vorhanden',
    'store_column_roles'     => 'Import fortsetzen',
    'do_not_map'             => '(keine Zuordnung)',
    'map_title'              => 'Verbinde Importdaten mit Firefly III Daten',
    'map_text'               => 'In den folgenden Tabellen zeigt der linke Wert Informationen, die sich in Ihrer hochgeladenen CSV-Datei befinden. Es ist Ihre Aufgabe, diesen Wert, wenn möglich, einem bereits in der Datenbank vorhandem zuzuordnen. Firefly wird sich an diese Zuordnung halten. Wenn kein Wert für die Zuordnung vorhanden ist oder Sie den bestimmten Wert nicht abbilden möchten, wählen Sie nichts aus.',

    'field_value'          => 'Feldwert',
    'field_mapped_to'      => 'Zugeordnet zu',
    'store_column_mapping' => 'Speicherzuordnung',

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
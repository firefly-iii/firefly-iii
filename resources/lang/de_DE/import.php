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
    'status_wait_title'                    => 'Bitte warten...',
    'status_wait_text'                     => 'Diese Box wird gleich verschwinden.',
    'status_fatal_title'                   => 'Ein schwerwiegender Fehler ist aufgetreten',
    'status_fatal_text'                    => 'Es ist ein schwerwiegender Fehler aufgetreten und die Importroutine kann nicht fortgeführt werden. Bitte sehen Sie sich die Erklärung in rot unten an.',
    'status_fatal_more'                    => 'Wenn der Fehler eine Zeitüberschreitung ist, wird der Import mittendrin gestoppt. Bei einigen Serverkonfigurationen wird lediglich der Server gestoppt, während der Import im Hintergrund ausgeführt wird. Um dies zu überprüfen, überprüfen Sie die Protokolldateien. Wenn das Problem weiterhin besteht, sollten Sie stattdessen den Import über die Befehlszeile in Erwägung ziehen.',
    'status_ready_title'                   => 'Der Import ist startbereit',
    'status_ready_text'                    => 'Der Import ist bereit zu starten. Alle Einstellungen wurden von Ihnen erledigt. Bitte laden Sie die Konfigurationsdatei herunter. Diese wird Ihnen beim Import helfen, sollte dieser nicht wie gewünscht verlaufen. Um den Import tatsächlich zu starten führen Sie den folgenden Befehl in der Konsole aus oder nutzen Sie den Web-basierten Import. Abhängig von ihrer Konfiguration wird Ihnen der Konsolenimport mehr Rückmeldungen geben.',
    'status_ready_noconfig_text'           => 'Der Import ist bereit zu starten. Alle Einstellungen wurden von Ihnen erledigt. Um den Import tatsächlich zu starten führen Sie den folgenden Befehl in der Konsole aus oder nutzen Sie den Web-basierten Import. Abhängig von ihrer Konfiguration wird Ihnen der Konsolenimport mehr Rückmeldungen geben.',
    'status_ready_config'                  => 'Download der Konfiguration',
    'status_ready_start'                   => 'Starte den Import',
    'status_ready_share'                   => 'Bitte denken Sie darüber nach ihre Konfiguration herunterzuladen und in der <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">Übersicht der Import-Einstellungen</a></strong> zu teilen. Dieses erlaubt es anderen Nutzern von Firefly III ihre Daten unkomplizierter zu importieren.',
    'status_job_new'                       => 'The job is brand new.',
    'status_job_configuring'               => 'The import is being configured.',
    'status_job_configured'                => 'Der Import ist konfiguriert.',
    'status_job_running'                   => 'Der Import läuft.. Bitte warten..',
    'status_job_error'                     => 'The job has generated an error.',
    'status_job_finished'                  => 'Der Import ist abgeschlossen!',
    'status_running_title'                 => 'Der Import läuft',
    'status_running_placeholder'           => 'Bitte warten Sie auf eine Aktualisierung...',
    'status_finished_title'                => 'Importassistent abgeschlossen',
    'status_finished_text'                 => 'Der Importassistent hat Ihre Daten importiert.',
    'status_errors_title'                  => 'Fehler beim Import',
    'status_errors_single'                 => 'Beim Import ist ein Fehler aufgetreten. Dieser scheint aber nicht schwerwiegend zu sein.',
    'status_errors_multi'                  => 'Beim Import sind einige Fehler aufgetreten. Diese scheinen aber nicht schwerwiegend zu sein.',
    'status_bread_crumb'                   => 'Importstatus',
    'status_sub_title'                     => 'Importstatus',
    'config_sub_title'                     => 'Import einrichten',
    'status_finished_job'                  => 'The :count transactions imported can be found in tag <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    'status_finished_no_tag'               => 'Firefly III has not collected any journals from your import file.',
    'import_with_key'                      => 'Import mit Schlüssel \':key\'',

    // file, upload something
    'file_upload_title'                    => 'Import-Setup (1/4) - Laden Sie Ihre Datei hoch',
    'file_upload_text'                     => 'Dieser Assistent hilft Ihnen, Dateien von Ihrer Bank in Firefly III zu importieren. Bitte sehen Sie sich die Hilfeseiten in der oberen rechten Ecke an.',
    'file_upload_fields'                   => 'Felder',
    'file_upload_help'                     => 'Datei auswählen',
    'file_upload_config_help'              => 'Wenn Sie bereits zuvor Daten in Firefly III importiert haben, haben Sie eventuell eine Konfigurationsdatei, welche einige Einstellungen für Sie voreinstellt. Für einige Banken haben andere Nutzer freundlicherweise bereits ihre <a href="https://github.com/firefly-iii/import-configurations/wiki">Konfigurationsdatei</a> zur Verfügung gestellt',
    'file_upload_type_help'                => 'Wählen Sie den Typ der hochzuladenden Datei',
    'file_upload_submit'                   => 'Dateien hochladen',

    // file, upload types
    'import_file_type_csv'                 => 'CSV (Kommagetrennte Werte)',

    // file, initial config for CSV
    'csv_initial_title'                    => 'Import Einrichten (2/4) - Grundlegende Einstellungen',
    'csv_initial_text'                     => 'Um Ihre Datei korrekt importieren zu können, überprüfen Sie bitte die folgenden Optionen.',
    'csv_initial_box'                      => 'Standard CSV Importeinstellungen',
    'csv_initial_box_title'                => 'Standard CSV Importeinstellungen',
    'csv_initial_header_help'              => 'Hier auswählen, wenn die ersten Zeilen der CSV-Datei die Spaltenüberschriften sind.',
    'csv_initial_date_help'                => 'Datumsformat in ihrer CSV-Datei. Geben Sie das Format so an, wie es <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">diese Seite</a> zeigt. Die Standardeinstellung ergibt Daten die so aussehen: :dateExample.',
    'csv_initial_delimiter_help'           => 'Wählen Sie das Trennzeichen, welches in ihrer Datei genutzt wird. Wenn Sie nicht sicher sind ist Komma die sicherste Option.',
    'csv_initial_import_account_help'      => 'Wenn ihre CSV-Datei KEINE Informationen über ihre Girokonten enthält, nutzen Sie bitte diese Dropdown-Liste um anzugeben, zu welchem Girokonto die Transaktionen in der CSV-Datei gehören.',
    'csv_initial_submit'                   => 'Fortfahren mit Schritt 3/4',

    // file, new options:
    'file_apply_rules_title'               => 'Regeln anwenden',
    'file_apply_rules_description'         => 'Regeln anwenden. Beachten Sie, dass dadurch der Import erheblich verlangsamt wird.',
    'file_match_bills_title'               => 'Rechnungen zuordnen',
    'file_match_bills_description'         => 'Ordnen Sie Ihre Rechnungen den neu erstellten Ausgaben zu. Beachten Sie, dass dadurch der Import erheblich verlangsamt wird.',

    // file, roles config
    'csv_roles_title'                      => 'Import Einrichten (3/4) - Jeder Spalte eine Rolle zuordnen',
    'csv_roles_text'                       => 'Jede Spalte in Ihrer CSV-Datei enthält bestimmte Daten. Bitte geben Sie an, welche Art von Daten enthalten sind. Die Option "Daten zuordnen" bedeutet, dass jeder Eintrag in der Spalte mit einem Wert aus Ihrer der Datenbank ersetzt wird. Eine oft zugeordnete Spalte ist die Spalte, welche die IBAN des fremden Kontos enthält. Diese können leicht mit bereits angelegten IBANs in Ihrer Datenbank verglichen werden.',
    'csv_roles_table'                      => 'Tabelle',
    'csv_roles_column_name'                => 'Name der Spalte',
    'csv_roles_column_example'             => 'Beispieldaten',
    'csv_roles_column_role'                => 'Bedeutung der Spalte',
    'csv_roles_do_map_value'               => 'Diese Werte zuordnen',
    'csv_roles_column'                     => 'Spalte',
    'csv_roles_no_example_data'            => 'Keine Beispieldaten vorhanden',
    'csv_roles_submit'                     => 'Fortfahren mit Schritt 4/4',

    // not csv, but normal warning
    'roles_warning'                        => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',
    'foreign_amount_warning'               => 'Wenn Sie eine Spalte als Fremdwährung markieren, müssen Sie auch die Spalte festlegen, welche angibt, welche Währung es ist.',
    // file, map data
    'file_map_title'                       => 'Import Einrichten (4/4) - Import mit bereits vorhandenen Daten verknüpfen',
    'file_map_text'                        => 'In den folgenden Tabellen zeigt der linke Wert Informationen, die sich in Ihrer hochgeladenen Datei befinden. Es ist Ihre Aufgabe, diesen Wert, wenn möglich, einem bereits in der Datenbank vorhandenen zuzuordnen. Firefly wird sich an diese Zuordnung halten. Wenn kein Wert für die Zuordnung vorhanden ist oder Sie den bestimmten Wert nicht abbilden möchten, wählen Sie nichts aus.',
    'file_map_field_value'                 => 'Feldwert',
    'file_map_field_mapped_to'             => 'Zugeordnet zu',
    'map_do_not_map'                       => '(keine Zuordnung)',
    'file_map_submit'                      => 'Starte den Import',
    'file_nothing_to_map'                  => 'There is no data present in your file that you can map to existing values. Please press "Start the import" to continue.',

    // map things.
    'column__ignore'                       => '(diese Spalte ignorieren)',
    'column_account-iban'                  => 'Bestandskonto (IBAN)',
    'column_account-id'                    => 'Asset account ID (matching FF3)',
    'column_account-name'                  => 'Bestandskonto (Name)',
    'column_amount'                        => 'Betrag',
    'column_amount_foreign'                => 'Betrag (in Fremdwährung)',
    'column_amount_debit'                  => 'Amount (debit column)',
    'column_amount_credit'                 => 'Amount (credit column)',
    'column_amount-comma-separated'        => 'Betrag (Komma als Dezimaltrennzeichen)',
    'column_bill-id'                       => 'Bill ID (matching FF3)',
    'column_bill-name'                     => 'Name der Rechnung',
    'column_budget-id'                     => 'Budget ID (matching FF3)',
    'column_budget-name'                   => 'Budgetname',
    'column_category-id'                   => 'Category ID (matching FF3)',
    'column_category-name'                 => 'Name der Kategorie',
    'column_currency-code'                 => 'Währungsstandard (ISO 4217)',
    'column_foreign-currency-code'         => 'Fremdwährungscode (ISO 4217)',
    'column_currency-id'                   => 'Currency ID (matching FF3)',
    'column_currency-name'                 => 'Currency name (matching FF3)',
    'column_currency-symbol'               => 'Currency symbol (matching FF3)',
    'column_date-interest'                 => 'Datum der Zinsberechnung',
    'column_date-book'                     => 'Buchungsdatum der Überweisung',
    'column_date-process'                  => 'Verarbeitungsdatum der Überweisung',
    'column_date-transaction'              => 'Datum',
    'column_description'                   => 'Beschreibung',
    'column_opposing-iban'                 => 'Zielkonto (IBAN)',
    'column_opposing-id'                   => 'Opposing account ID (matching FF3)',
    'column_external-id'                   => 'Externe ID',
    'column_opposing-name'                 => 'Zielkonto (Name)',
    'column_rabo-debit-credit'             => 'Rabobank specific debit/credit indicator',
    'column_ing-debit-credit'              => 'ING specific debit/credit indicator',
    'column_sepa-ct-id'                    => 'SEPA Transaktions-ID',
    'column_sepa-ct-op'                    => 'SEPA Transaktionspartner',
    'column_sepa-db'                       => 'SEPA-Lastschrift',
    'column_tags-comma'                    => 'Tags (durch Komma getrennt)',
    'column_tags-space'                    => 'Tags (durch Leerzeichen getrennt)',
    'column_account-number'                => 'Bestandskonto (Kontonr.)',
    'column_opposing-number'               => 'Zielkonto (Kontonr.)',
    'column_note'                          => 'Notiz(en)',

    // prerequisites
    'prerequisites'                        => 'Voraussetzungen',

    // bunq
    'bunq_prerequisites_title'             => 'Voraussetzungen für einen Import von bunq',
    'bunq_prerequisites_text'              => 'Um aus bunq importieren zu können, benötigen Sie einen API-Schlüssel. Sie können diesen in der App bekommen.',

    // Spectre
    'spectre_title'                        => 'Importieren mit Spectre',
    'spectre_prerequisites_title'          => 'Voraussetzungen für einen Import von Spectre',
    'spectre_prerequisites_text'           => 'In order to import data using the Spectre API, you must provide Firefly III with two secret values. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'spectre_enter_pub_key'                => 'Der Import funktioniert nur, wenn Sie diesen öffentlichen Schlüssel auf Ihrer <a href="https://www.saltedge.com/clients/security/edit">Sicherheitsseite</a> eingeben.',
    'spectre_accounts_title'               => 'Select accounts to import from',
    'spectre_accounts_text'                => 'Each account on the left below has been found by Spectre and can be imported into Firefly III. Please select the asset account that should hold any given transactions. If you do not wish to import from any particular account, remove the check from the checkbox.',
    'spectre_do_import'                    => 'Ja, von diesem Konto importieren',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'BIC (SWIFT) Code',
    'spectre_extra_key_status'             => 'Status',
    'spectre_extra_key_card_type'          => 'Art der Kreditkarte',
    'spectre_extra_key_account_name'       => 'Kontoname',
    'spectre_extra_key_client_name'        => 'Kundenname',
    'spectre_extra_key_account_number'     => 'Kontonummer',
    'spectre_extra_key_blocked_amount'     => 'Gesperrter Betrag',
    'spectre_extra_key_available_amount'   => 'Verfügbarer Betrag',
    'spectre_extra_key_credit_limit'       => 'Kreditlimit',
    'spectre_extra_key_interest_rate'      => 'Zinssatz',
    'spectre_extra_key_expiry_date'        => 'Ablaufdatum',
    'spectre_extra_key_open_date'          => 'Open date',
    'spectre_extra_key_current_time'       => 'Aktuelle Uhrzeit',
    'spectre_extra_key_current_date'       => 'Aktuelles Datum',
    'spectre_extra_key_cards'              => 'Karten',
    'spectre_extra_key_units'              => 'Einheiten',
    'spectre_extra_key_unit_price'         => 'Stückpreis',
    'spectre_extra_key_transactions_count' => 'Anzahl Transaktionen',

    // various other strings:
    'imported_from_account'                => 'Von ":account" importiert',
];


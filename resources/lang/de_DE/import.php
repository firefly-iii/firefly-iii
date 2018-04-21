<?php
declare(strict_types=1);

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

return [
    // status of import:
    'status_wait_title'                    => 'Bitte warten...',
    'status_wait_text'                     => 'Diese Box wird gleich ausgeblendet.',
    'status_fatal_title'                   => 'Ein schwerwiegender Fehler ist aufgetreten',
    'status_fatal_text'                    => 'Es ist ein schwerwiegender Fehler aufgetreten und die Importroutine kann nicht fortgeführt werden. Bitte sehen Sie sich die Erklärung in rot unten an.',
    'status_fatal_more'                    => 'Wenn der Fehler eine Zeitüberschreitung ist, wird der Import mittendrin gestoppt. Bei einigen Serverkonfigurationen wird lediglich der Server gestoppt, während der Import im Hintergrund ausgeführt wird. Um dies zu überprüfen, überprüfen Sie die Protokolldateien. Wenn das Problem weiterhin besteht, sollten Sie stattdessen den Import über die Befehlszeile in Erwägung ziehen.',
    'status_ready_title'                   => 'Der Import ist startbereit',
    'status_ready_text'                    => 'Der Import ist bereit zu starten. Alle Einstellungen wurden von Ihnen erledigt. Bitte laden Sie die Konfigurationsdatei herunter. Diese wird Ihnen beim Import helfen, sollte dieser nicht wie gewünscht verlaufen. Um den Import tatsächlich zu starten führen Sie den folgenden Befehl in der Konsole aus oder nutzen Sie den Web-basierten Import. Abhängig von ihrer Konfiguration wird Ihnen der Konsolenimport mehr Rückmeldungen geben.',
    'status_ready_noconfig_text'           => 'Der Import ist bereit zu starten. Alle Einstellungen wurden von Ihnen erledigt. Um den Import tatsächlich zu starten führen Sie den folgenden Befehl in der Konsole aus oder nutzen Sie den Web-basierten Import. Abhängig von ihrer Konfiguration wird Ihnen der Konsolenimport mehr Rückmeldungen geben.',
    'status_ready_config'                  => 'Konfiguration herunterladen',
    'status_ready_start'                   => 'Importieren starten',
    'status_ready_share'                   => 'Bitte denken Sie darüber nach ihre Konfiguration herunterzuladen und in der <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">Übersicht der Import-Einstellungen</a></strong> zu teilen. Dieses erlaubt es anderen Nutzern von Firefly III ihre Daten unkomplizierter zu importieren.',
    'status_job_new'                       => 'Die Aufgabe ist neu.',
    'status_job_configuring'               => 'Der Import wird konfiguriert.',
    'status_job_configured'                => 'Der Import ist konfiguriert.',
    'status_job_running'                   => 'Import wird ausgeführt … Bitte warten …',
    'status_job_error'                     => 'Ein Fehler ist aufgetreten.',
    'status_job_finished'                  => 'Import abgeschlossen!',
    'status_running_title'                 => 'Import wird ausgeführt',
    'status_running_placeholder'           => 'Bitte auf die Aktualisierung warten …',
    'status_finished_title'                => 'Importassistent abgeschlossen',
    'status_finished_text'                 => 'Der Importassistent hat Ihre Daten importiert.',
    'status_errors_title'                  => 'Fehler beim Importieren',
    'status_errors_single'                 => 'Beim Import ist ein Fehler aufgetreten. Dieser scheint aber nicht schwerwiegend zu sein.',
    'status_errors_multi'                  => 'Beim Importieren sind einige Fehler aufgetreten. Diese scheinen aber nicht schwerwiegend zu sein.',
    'status_bread_crumb'                   => 'Importstatus',
    'status_sub_title'                     => 'Importstatus',
    'config_sub_title'                     => 'Import einrichten',
    'status_finished_job'                  => 'Die importierten :count Überweisungen finden Sie im Schlagwort <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    'status_finished_no_tag'               => 'Firefly III hat keine Daten aus Ihrer Import-Datei gesammelt.',
    'import_with_key'                      => 'Mit Schlüssel „:key” importieren',

    // file, upload something
    'file_upload_title'                    => 'Import einrichten (1/4) • Ihre Datei hochladen',
    'file_upload_text'                     => 'Dieser Assistent hilft Ihnen, Dateien von Ihrer Bank in Firefly III zu importieren. Bitte sehen Sie sich die Hilfeseiten in der oberen rechten Ecke an.',
    'file_upload_fields'                   => 'Felder',
    'file_upload_help'                     => 'Datei auswählen',
    'file_upload_config_help'              => 'Wenn Sie bereits zuvor Daten in Firefly III importiert haben, haben Sie eventuell eine Konfigurationsdatei, welche einige Einstellungen für Sie voreinstellt. Für einige Banken haben andere Nutzer freundlicherweise bereits ihre <a href="https://github.com/firefly-iii/import-configurations/wiki">Konfigurationsdatei</a> zur Verfügung gestellt',
    'file_upload_type_help'                => 'Wählen Sie den Typ der hochzuladenden Datei',
    'file_upload_submit'                   => 'Dateien hochladen',

    // file, upload types
    'import_file_type_csv'                 => 'CSV (Kommagetrennte Werte)',

    // file, initial config for CSV
    'csv_initial_title'                    => 'Import einrichten (2/4) • Grundlegende Einrichtung des CSV-Imports',
    'csv_initial_text'                     => 'Um Ihre Datei korrekt importieren zu können, überprüfen Sie bitte die folgenden Optionen.',
    'csv_initial_box'                      => 'Standard CSV Importeinstellungen',
    'csv_initial_box_title'                => 'Standard CSV Importeinstellungen',
    'csv_initial_header_help'              => 'Hier auswählen, wenn die erste Zeilen der CSV-Datei die Spaltenüberschriften enthält.',
    'csv_initial_date_help'                => 'Datumsformat in ihrer CSV-Datei. Geben Sie das Format so an, wie es <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">diese Seite</a> zeigt. Die Standardeinstellung ergibt Daten die so aussehen: :dateExample.',
    'csv_initial_delimiter_help'           => 'Wählen Sie das Trennzeichen, welches in ihrer Datei genutzt wird. Wenn Sie nicht sicher sind ist Komma die sicherste Option.',
    'csv_initial_import_account_help'      => 'Wenn ihre CSV-Datei KEINE Informationen über ihre Bestandskont(o/n) enthält, nutzen Sie bitte diese Auswahlmenü um anzugeben, zu welchem Bestandskonto die Buchungen in der CSV-Datei gehören.',
    'csv_initial_submit'                   => 'Fortfahren mit Schritt 3/4',

    // file, new options:
    'file_apply_rules_title'               => 'Regeln anwenden',
    'file_apply_rules_description'         => 'Regeln anwenden. Beachten Sie, dass dadurch der Import erheblich verlangsamt wird.',
    'file_match_bills_title'               => 'Rechnungen zuordnen',
    'file_match_bills_description'         => 'Ordnen Sie Ihre Rechnungen den neu erstellten Ausgaben zu. Beachten Sie, dass dadurch der Import erheblich verlangsamt wird.',

    // file, roles config
    'csv_roles_title'                      => 'Import einrichten (3/4) • Funktion jeder Spalte festlegen',
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
    'roles_warning'                        => 'Markieren Sie mindestens die Spalte, die den jeweiligen Betrag enthält. Darüber hinaus sollten eine Spalte für die Beschreibung, das Datum und das Gegenkonto ausgewählt werden.',
    'foreign_amount_warning'               => 'Wenn Sie eine Spalte als Fremdwährung markieren, müssen Sie auch die Spalte festlegen, welche angibt, welche Währung es ist.',

    // file, map data
    'file_map_title'                       => 'Import einrichten (4/4) - Importdaten mit Firefly III-Daten verknüpfen',
    'file_map_text'                        => 'In den folgenden Tabellen zeigt der linke Wert Informationen, die sich in Ihrer hochgeladenen Datei befinden. Es ist Ihre Aufgabe, diesen Wert, wenn möglich, einem bereits in der Datenbank vorhandenen zuzuordnen. Firefly wird sich an diese Zuordnung halten. Wenn kein Wert für die Zuordnung vorhanden ist oder Sie den bestimmten Wert nicht abbilden möchten, wählen Sie nichts aus.',
    'file_map_field_value'                 => 'Feldwert',
    'file_map_field_mapped_to'             => 'Zugeordnet zu',
    'map_do_not_map'                       => '(keine Zuordnung)',
    'file_map_submit'                      => 'Import starten',
    'file_nothing_to_map'                  => 'Ihree Datei enthält keine Daten, die bestehenden Werten zugeordnet werden können. Klicken Sie "Import starten" um fortzufahren.',

    // map things.
    'column__ignore'                       => '(diese Spalte ignorieren)',
    'column_account-iban'                  => 'Bestandskonto (IBAN)',
    'column_account-id'                    => 'Kennung des Bestandkontos (passend zu FF3)',
    'column_account-name'                  => 'Bestandskonto (Name)',
    'column_amount'                        => 'Betrag',
    'column_amount_foreign'                => 'Betrag (in Fremdwährung)',
    'column_amount_debit'                  => 'Betrag (Debitoren-Spalte)',
    'column_amount_credit'                 => 'Betrag (Guthaben-Spalte)',
    'column_amount-comma-separated'        => 'Betrag (Komma als Dezimaltrennzeichen)',
    'column_bill-id'                       => 'Rechnung (ID übereinstimmend mit FF3)',
    'column_bill-name'                     => 'Rechnungsname',
    'column_budget-id'                     => 'Kostenrahmen-ID (übereinstimmend mit FF3)',
    'column_budget-name'                   => 'Kostenrahmenname',
    'column_category-id'                   => 'Kategorie (ID übereinstimmend mit FF3)',
    'column_category-name'                 => 'Name der Kategorie',
    'column_currency-code'                 => 'Währungsstandard (ISO 4217)',
    'column_foreign-currency-code'         => 'Fremdwährungscode (ISO 4217)',
    'column_currency-id'                   => 'Währung (ID übereinstimmend mit FF3)',
    'column_currency-name'                 => 'Währungsname (übereinstimmend mit FF3)',
    'column_currency-symbol'               => 'Währungssysmbol (übereinstimmend mit FF3)',
    'column_date-interest'                 => 'Datum der Zinsberechnung',
    'column_date-book'                     => 'Buchungsdatum der Überweisung',
    'column_date-process'                  => 'Verarbeitungsdatum der Überweisung',
    'column_date-transaction'              => 'Datum',
    'column_date-due'                      => 'Buchungsfälligkeit',
    'column_date-payment'                  => 'Buchungsdatum',
    'column_date-invoice'                  => 'Buchungsdatum der Rechnung',
    'column_description'                   => 'Beschreibung',
    'column_opposing-iban'                 => 'Zielkonto (IBAN)',
    'column_opposing-bic'                  => 'Zielkonto (BIC)',
    'column_opposing-id'                   => 'Zielkonto (vgl. ID in FF3)',
    'column_external-id'                   => 'Externe ID',
    'column_opposing-name'                 => 'Zielkonto (Name)',
    'column_rabo-debit-credit'             => 'Rabobank-spezifisches Belastungs- und Kreditkennzeichen',
    'column_ing-debit-credit'              => 'ING-spezifisches Belastungs- und Kreditkennzeichen',
    'column_sepa-ct-id'                    => 'SEPA • Ende-zu-Ende-Identifikationsnummer',
    'column_sepa-ct-op'                    => 'SEPA • Zielkonto-Identifikationsnummer',
    'column_sepa-db'                       => 'SEPA - Mandatskennung',
    'column_sepa-cc'                       => 'SEPA • Verrechnungsschlüssel',
    'column_sepa-ci'                       => 'SEPA • Identifikationsnummer des Zahlungsempfängers',
    'column_sepa-ep'                       => 'SEPA • Externer Verwendungszweck',
    'column_sepa-country'                  => 'SEPA • Landesschlüssel',
    'column_tags-comma'                    => 'Schlagwörter (durch Kommata getrennt)',
    'column_tags-space'                    => 'Schlagwörter (durch Leerzeichen getrennt)',
    'column_account-number'                => 'Bestandskonto (Kontonr.)',
    'column_opposing-number'               => 'Zielkonto (Kontonr.)',
    'column_note'                          => 'Notiz(en)',
    'column_internal-reference'            => 'Interne Referenz',

    // prerequisites
    'prerequisites'                        => 'Voraussetzungen',

    // bunq
    'bunq_prerequisites_title'             => 'Voraussetzungen für einen Import von bunq',
    'bunq_prerequisites_text'              => 'Um aus „bunq” importieren zu können, benötigen Sie einen API-Schlüssel. Sie können diesen über die App bekommen. Bitte beachten Sie, dass sich die Importfunktion von „bunq” noch im BETA-Stadium befindet. Es wurde nur gegen die Sandbox-API getestet.',
    'bunq_prerequisites_text_ip'           => '„Bunq” benötigt Ihre öffentlich zugängliche IP-Adresse. Firefly III versuchte, diese mithilfe <a href="https://www.ipify.org/">des ipify-Diensts </a> auszufüllen. Stellen Sie sicher, dass diese IP-Adresse korrekt ist, da sonst der Import fehlschlägt.',
    'bunq_do_import'                       => 'Ja, von diesem Konto importieren',
    'bunq_accounts_title'                  => 'Bunq-Konten',
    'bunq_accounts_text'                   => 'Dies sind jene Konten, die mit Ihrem „bunq”-Konto verknüpft sind. Bitte wählen Sie die Konten aus, von denen Sie importieren möchten, und in welches Konto die Buchungen importiert werden sollen.',

    // Spectre
    'spectre_title'                        => 'Importieren mit Spectre',
    'spectre_prerequisites_title'          => 'Voraussetzungen für einen Import von Spectre',
    'spectre_prerequisites_text'           => 'Um Daten mithilfe der Spectre-API importieren zu können, müssen einige Daten angegeben werden. Diese finden Sie auf der <a href="https://www.saltedge.com/clients/profile/secrets">Secrets</a>-Seite bei Saltedge.',
    'spectre_enter_pub_key'                => 'Der Import funktioniert nur, wenn Sie diesen öffentlichen Schlüssel auf Ihrer <a href="https://www.saltedge.com/clients/security/edit">Sicherheitsseite</a> eingeben.',
    'spectre_accounts_title'               => 'Import-Konten auswählen',
    'spectre_accounts_text'                => 'Die Konten auf der linken Seite wurde von Spectre gefunden und können für den Import verwendet werden. Ordnen Sie jeweils ein eigenes Konto zu, in das die Buchungen importiert werden sollen. Nicht ausgwählte Konten werden beim Import ignoriert.',
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
    'spectre_extra_key_credit_limit'       => 'Kreditrahmen',
    'spectre_extra_key_interest_rate'      => 'Zinssatz',
    'spectre_extra_key_expiry_date'        => 'Ablaufdatum',
    'spectre_extra_key_open_date'          => 'Anfangsdatum',
    'spectre_extra_key_current_time'       => 'Aktuelle Uhrzeit',
    'spectre_extra_key_current_date'       => 'Aktuelles Datum',
    'spectre_extra_key_cards'              => 'Karten',
    'spectre_extra_key_units'              => 'Einheiten',
    'spectre_extra_key_unit_price'         => 'Stückpreis',
    'spectre_extra_key_transactions_count' => 'Anzahl Transaktionen',

    // various other strings:
    'imported_from_account'                => 'Von „:account” importiert',
];

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
    'status_wait_title'                    => 'Momentje...',
    'status_wait_text'                     => 'Dit vak verdwijnt zometeen.',
    'status_fatal_title'                   => 'Er is een fatale fout opgetreden',
    'status_fatal_text'                    => 'Een fatale fout opgetreden, waar de import-routine niet van terug heeft. Zie de uitleg in het rood hieronder.',
    'status_fatal_more'                    => 'Als de fout een time-out is, zal de import-routine halverwege gestopt zijn. Bij bepaalde serverconfiguraties is het alleen maar de server die gestopt terwijl de import-routine op de achtergrond doorloopt. Controleer de logboekbestanden om te zien wat er aan de hand is. Als het probleem zich blijft voordoen, gebruik dan de command-line opdracht.',
    'status_ready_title'                   => 'De import is klaar om te beginnen',
    'status_ready_text'                    => 'De import kan beginnen. Alle configuratie is opgeslagen. Download dit bestand. Het kan schelen als je de import opnieuw moet doen. Om daadwerkelijk te beginnen, gebruik je of het commando in je console, of de website. Afhankelijk van hoe je Firefly III hebt ingesteld, geeft de console-methode meer feedback.',
    'status_ready_noconfig_text'           => 'De import kan beginnen. Alle configuratie is opgeslagen. Om daadwerkelijk te beginnen, gebruik je of het commando in je console, of de website. Afhankelijk van hoe je Firefly III hebt ingesteld, geeft de console-methode meer feedback.',
    'status_ready_config'                  => 'Download importconfiguratie',
    'status_ready_start'                   => 'Start importeren',
    'status_ready_share'                   => 'Overweeg om je configuratiebestand te downloaden en te delen op de <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">configuratiebestand-wiki</a></strong>. Hiermee kan je het andere Firefly III gebruikers weer makkelijker maken.',
    'status_job_new'                       => 'De import is gloednieuw.',
    'status_job_configuring'               => 'De import wordt geconfigureerd.',
    'status_job_configured'                => 'De import is geconfigureerd.',
    'status_job_running'                   => 'De import is bezig.. Momentje..',
    'status_job_error'                     => 'De import heeft een fout gegenereerd.',
    'status_job_finished'                  => 'Het importeren is voltooid!',
    'status_running_title'                 => 'De import is bezig',
    'status_running_placeholder'           => 'Wacht even voor een update...',
    'status_finished_title'                => 'Importeren is klaar',
    'status_finished_text'                 => 'De import is klaar.',
    'status_errors_title'                  => 'Fouten tijdens het importeren',
    'status_errors_single'                 => 'Er is een niet-fatale fout opgetreden tijdens het importeren.',
    'status_errors_multi'                  => 'Er zijn een aantal niet-fatale fouten opgetreden tijdens het importeren.',
    'status_bread_crumb'                   => 'Status van importeren',
    'status_sub_title'                     => 'Status van importeren',
    'config_sub_title'                     => 'Instellen van je import',
    'status_finished_job'                  => 'De :count geïmporteerde transacties kan je vinden onder tag <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    'status_finished_no_tag'               => 'Er is niets uit je bestand geïmporteerd.',
    'import_with_key'                      => 'Import met code \':key\'',

    // file, upload something
    'file_upload_title'                    => 'Importinstellingen (1/4) - Upload je bestand',
    'file_upload_text'                     => 'Deze pagina\'s helpen je bestanden van je bank te importeren in Firefly III. Gebruik de hulp-pagina\'s linksboven voor meer informatie.',
    'file_upload_fields'                   => 'Velden',
    'file_upload_help'                     => 'Selecteer je bestand',
    'file_upload_config_help'              => 'Als je eerder gegevens hebt geïmporteerd in Firefly III, heb je wellicht een configuratiebestand, dat een aantal zaken alvast voor je kan instellen. Voor bepaalde banken hebben andere gebruikers uit de liefde van hun hart het benodigde <a href="https://github.com/firefly-iii/import-configurations/wiki">configuratiebestand</a> gedeeld',
    'file_upload_type_help'                => 'Selecteer het type bestand dat je zal uploaden',
    'file_upload_submit'                   => 'Bestanden uploaden',

    // file, upload types
    'import_file_type_csv'                 => 'CSV (kommagescheiden waardes)',

    // file, initial config for CSV
    'csv_initial_title'                    => 'Importinstellingen (2/4) - Algemene CVS importinstellingen',
    'csv_initial_text'                     => 'Om je bestand goed te kunnen importeren moet je deze opties verifiëren.',
    'csv_initial_box'                      => 'Algemene CVS importinstellingen',
    'csv_initial_box_title'                => 'Algemene CVS importinstellingen',
    'csv_initial_header_help'              => 'Vink hier als de eerste rij kolomtitels bevat.',
    'csv_initial_date_help'                => 'Datum/tijd formaat in jouw CSV bestand. Volg het formaat zoals ze het <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">op deze pagina</a> uitleggen. Het standaardformaat ziet er zo uit: :dateExample.',
    'csv_initial_delimiter_help'           => 'Kies het veldscheidingsteken dat in jouw bestand wordt gebruikt. Als je het niet zeker weet, is de komma de beste optie.',
    'csv_initial_import_account_help'      => 'Als jouw CSV bestand geen referenties bevat naar jouw rekening(en), geef dan hier aan om welke rekening het gaat.',
    'csv_initial_submit'                   => 'Ga verder met stap 3/4',

    // file, new options:
    'file_apply_rules_title'               => 'Regels toepassen',
    'file_apply_rules_description'         => 'Past regels toe tijdens de import. Dit vertraagt de import behoorlijk.',
    'file_match_bills_title'               => 'Match contracten',
    'file_match_bills_description'         => 'Checkt of bestaande contracten matchen met nieuwe uitgaves. Dit vertraagt de import behoorlijk.',

    // file, roles config
    'csv_roles_title'                      => 'Importinstellingen (3/4) - rol van elke kolom definiëren',
    'csv_roles_text'                       => 'Elke kolom in je CSV-bestand bevat bepaalde gegevens. Gelieve aan te geven wat voor soort gegevens de import-routine kan verwachten. De optie "maak een link" betekent dat u elke vermelding in die kolom linkt aan een waarde uit je database. Een vaak gelinkte kolom is die met de IBAN-code van de tegenrekening. Die kan je dan linken aan de IBAN in jouw database.',
    'csv_roles_table'                      => 'Tabel',
    'csv_roles_column_name'                => 'Kolomnaam',
    'csv_roles_column_example'             => 'Voorbeeldgegevens',
    'csv_roles_column_role'                => 'Kolomrol',
    'csv_roles_do_map_value'               => 'Maak een link',
    'csv_roles_column'                     => 'Kolom',
    'csv_roles_no_example_data'            => 'Geen voorbeeldgegevens',
    'csv_roles_submit'                     => 'Ga verder met stap 4/4',

    // not csv, but normal warning
    'roles_warning'                        => 'Geef minstens de kolom aan waar het bedrag in staat. Als het even kan, ook een kolom voor de omschrijving, datum en de andere rekening.',
    'foreign_amount_warning'               => 'Als je een kolom markeert als "vreemde valuta" moet je ook aangeven in welke kolom de valuta staat.',
    // file, map data
    'file_map_title'                       => 'Importinstellingen (4/4) - Link importgegevens aan Firefly III-gegevens',
    'file_map_text'                        => 'In deze tabellen is de linkerwaarde een waarde uit je CSV bestand. Jij moet de link leggen, als mogelijk, met een waarde uit jouw database. Firefly houdt zich hier aan. Als er geen waarde is, selecteer dan ook niets.',
    'file_map_field_value'                 => 'Veldwaarde',
    'file_map_field_mapped_to'             => 'Gelinkt aan',
    'map_do_not_map'                       => '(niet linken)',
    'file_map_submit'                      => 'Start importeren',
    'file_nothing_to_map'                  => 'Je gaat geen gegevens importeren die te mappen zijn. Klik op "Start import" om verder te gaan.',

    // map things.
    'column__ignore'                       => '(negeer deze kolom)',
    'column_account-iban'                  => 'Betaalrekening (IBAN)',
    'column_account-id'                    => 'Betaalrekening (ID gelijk aan FF3)',
    'column_account-name'                  => 'Betaalrekeningnaam',
    'column_amount'                        => 'Bedrag',
    'column_amount_foreign'                => 'Bedrag (in vreemde valuta)',
    'column_amount_debit'                  => 'Bedrag (debetkolom)',
    'column_amount_credit'                 => 'Bedrag (creditkolom)',
    'column_amount-comma-separated'        => 'Bedrag (komma as decimaalscheidingsteken)',
    'column_bill-id'                       => 'Contract (ID gelijk aan FF3)',
    'column_bill-name'                     => 'Contractnaam',
    'column_budget-id'                     => 'Budget (ID gelijk aan FF3)',
    'column_budget-name'                   => 'Budgetnaam',
    'column_category-id'                   => 'Categorie (ID gelijk aan FF3)',
    'column_category-name'                 => 'Categorienaam',
    'column_currency-code'                 => 'Valutacode (ISO 4217)',
    'column_foreign-currency-code'         => 'Vreemde valutacode (ISO 4217)',
    'column_currency-id'                   => 'Valuta (ID gelijk aan FF3)',
    'column_currency-name'                 => 'Valutanaam (gelijk aan FF3)',
    'column_currency-symbol'               => 'Valutasymbool',
    'column_date-interest'                 => 'Datum (renteberekening)',
    'column_date-book'                     => 'Datum (boeking)',
    'column_date-process'                  => 'Datum (verwerking)',
    'column_date-transaction'              => 'Datum',
    'column_description'                   => 'Omschrijving',
    'column_opposing-iban'                 => 'Tegenrekening (IBAN)',
    'column_opposing-id'                   => 'Tegenrekening (ID gelijk aan FF3)',
    'column_external-id'                   => 'Externe ID',
    'column_opposing-name'                 => 'Tegenrekeningnaam',
    'column_rabo-debit-credit'             => 'Rabobankspecifiek bij/af indicator',
    'column_ing-debit-credit'              => 'ING-specifieke bij/af indicator',
    'column_sepa-ct-id'                    => 'SEPA end-to-end transactienummer',
    'column_sepa-ct-op'                    => 'SEPA tegenrekeningnummer',
    'column_sepa-db'                       => 'SEPA "direct debet"-nummer',
    'column_tags-comma'                    => 'Tags (kommagescheiden)',
    'column_tags-space'                    => 'Tags (spatiegescheiden)',
    'column_account-number'                => 'Betaalrekening (rekeningnummer)',
    'column_opposing-number'               => 'Tegenrekening (rekeningnummer)',
    'column_note'                          => 'Opmerking(en)',

    // prerequisites
    'prerequisites'                        => 'Vereisten',

    // bunq
    'bunq_prerequisites_title'             => 'Voorwaarden voor een import van bunq',
    'bunq_prerequisites_text'              => 'Om transacties bij bunq te importeren heb je een API sleutel nodig. Dit kan via de app.',

    // Spectre
    'spectre_title'                        => 'Importeer via Spectre',
    'spectre_prerequisites_title'          => 'Voorwaarden voor een import via Spectre',
    'spectre_prerequisites_text'           => 'Als je gegevens wilt importeren via de Spectre API, moet je een aantal geheime codes bezitten. Ze zijn te vinden op <a href="https://www.saltedge.com/clients/profile/secrets">de secrets pagina</a>.',
    'spectre_enter_pub_key'                => 'Het importeren werkt alleen als je deze publieke sleutel op uw <a href="https://www.saltedge.com/clients/security/edit">security pagina</a> invoert.',
    'spectre_accounts_title'               => 'Selecteer de accounts waaruit je wilt importeren',
    'spectre_accounts_text'                => 'Links staan de rekeningen die zijn gevonden door Spectre. Ze kunnen worden geïmporteerd in Firefly III. Selecteer er de juiste betaalrekening bij. Verwijder het vinkje als je toch niet van een bepaalde rekening wilt importeren.',
    'spectre_do_import'                    => 'Ja, importeer van deze rekening',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Status',
    'spectre_extra_key_card_type'          => 'Kaarttype',
    'spectre_extra_key_account_name'       => 'Rekeningnaam',
    'spectre_extra_key_client_name'        => 'Klantnaam',
    'spectre_extra_key_account_number'     => 'Rekeningnummer',
    'spectre_extra_key_blocked_amount'     => 'Gereserveerd bedrag',
    'spectre_extra_key_available_amount'   => 'Beschikbaar bedrag',
    'spectre_extra_key_credit_limit'       => 'Kredietlimiet',
    'spectre_extra_key_interest_rate'      => 'Rente',
    'spectre_extra_key_expiry_date'        => 'Vervaldatum',
    'spectre_extra_key_open_date'          => 'Openingsdatum',
    'spectre_extra_key_current_time'       => 'Huidige tijd',
    'spectre_extra_key_current_date'       => 'Huidige datum',
    'spectre_extra_key_cards'              => 'Kaarten',
    'spectre_extra_key_units'              => 'Eenheden',
    'spectre_extra_key_unit_price'         => 'Prijs per eenheid',
    'spectre_extra_key_transactions_count' => 'Transacties',

    // various other strings:
    'imported_from_account'                => 'Geïmporteerd uit ":account"',
];


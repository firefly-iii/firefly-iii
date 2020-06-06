<?php

/**
 * import.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    // ALL breadcrumbs and subtitles:
    'index_breadcrumb'                    => 'Gegevens importeren in Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Vereisten voor de nep-importhulp',
    'prerequisites_breadcrumb_spectre'    => 'Vereisten voor Spectre',
    'job_configuration_breadcrumb'        => 'Instellingen voor ":key"',
    'job_status_breadcrumb'               => 'Importstatus voor ":key"',
    'disabled_for_demo_user'              => 'uitgeschakeld in demo',

    // index page:
    'general_index_intro'                 => 'Dit is de import-routine van Firefly III. Er zijn verschillende manieren om gegevens te importeren in Firefly III, hier als knoppen weergegeven.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'De manier waarop Firefly III je data laat importeren gaat veranderen. Je kan lezen in <a href="https://www.patreon.com/posts/future-updates-30012174">deze Patreon-post</a> dat de CSV import-tool gaat verhuizen naar een zelfstandige repository en tool. Je kan deze alvast beta-testen als je <a href="https://github.com/firefly-iii/csv-importer">deze GitHub repository</a> bezoekt. Als je dat zou willen doen, heel graag, en laat me weten of het lukt.',
    'final_csv_import'     => 'De manier waarop Firefly III je data laat importeren gaat veranderen. Je kan lezen in <a href="https://www.patreon.com/posts/future-updates-30012174">deze Patreon-post</a> dat de CSV import-tool gaat verhuizen naar een zelfstandige repository en tool. Dit is de laatste versie met de CSV importer. Check <a href="https://github.com/firefly-iii/csv-importer">deze GitHub repository</a> en test de nieuwe tool.',

    // import provider strings (index):
    'button_fake'                         => 'Nepdata importeren',
    'button_file'                         => 'Importeer een bestand',
    'button_spectre'                      => 'Importeer via Spectre',

    // prerequisites box (index)
    'need_prereq_title'                   => 'Importvereisten',
    'need_prereq_intro'                   => 'Sommige importmethoden hebben je aandacht nodig voor ze gebruikt kunnen worden. Ze vereisen bijvoorbeeld speciale API-sleutels of geheime waardes. Je kan ze hier instellen. Het icoontje geeft aan of deze vereisten al ingevuld zijn.',
    'do_prereq_fake'                      => 'Vereisten voor de nep-importhulp',
    'do_prereq_file'                      => 'Vereisten voor het importeren van bestanden',
    'do_prereq_spectre'                   => 'Vereisten voor een import via Spectre',

    // prerequisites:
    'prereq_fake_title'                   => 'Instellingen voor importeren uit de nep-importhulp',
    'prereq_fake_text'                    => 'Deze nep-provider heeft een neppe API key nodig. Deze moet 32 tekens lang zijn. Je mag deze gebruiken: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Vereisten voor een import via Spectre',
    'prereq_spectre_text'                 => 'Als je gegevens wilt importeren via de Spectre API (v4), moet je een aantal geheime codes bezitten. Ze zijn te vinden op <a href="https://www.saltedge.com/clients/profile/secrets">de secrets pagina</a>.',
    'prereq_spectre_pub'                  => 'De Spectre API moet de publieke sleutel kennen die je hieronder ziet. Zonder deze sleutel herkent Spectre je niet. Voer deze publieke sleutel in op je <a href="https://www.saltedge.com/clients/profile/secrets">secrets-pagina</a>.',
    'callback_not_tls'                    => 'Firefly III heeft de volgende URI gedetecteerd. Het lijkt er op dat dit geen TLS-verbinding is (https). YNAB zal dit niet accepteren. Je mag doorgaan met de import (Firefly III kan er naast zitten), maar dan weet je het.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Nep API-sleutel is opgeslagen!',
    'prerequisites_saved_for_spectre'     => 'APP ID en secret opgeslagen!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Importinstellingen - regels toepassen?',
    'job_config_apply_rules_text'         => 'Als de nep-importhulp gedraaid heeft kunnen je regels worden toegepast op de transacties. Dit kost wel tijd.',
    'job_config_input'                    => 'Je invoer',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Voer albumnaam in',
    'job_config_fake_artist_text'         => 'Veel importroutines hebben een paar configuratiestappen die je moet doorlopen. In het geval van de nep-importhulp moet je een aantal rare vragen beantwoorden. Voer \'David Bowie\' in om door te gaan.',
    'job_config_fake_song_title'          => 'Naam van nummer',
    'job_config_fake_song_text'           => 'Noem het nummer "Golden years" om door te gaan met de nep import.',
    'job_config_fake_album_title'         => 'Albumnaam invoeren',
    'job_config_fake_album_text'          => 'Sommige importroutines vereisen extra gegevens halverwege de import. In het geval van de nep-importhulp moet je een aantal rare vragen beantwoorden. Voer "Station naar station" in om door te gaan.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Importinstellingen (1/4) - Upload je bestand',
    'job_config_file_upload_text'         => 'Met deze routine kan je bestanden van je bank importeren in Firefly III. ',
    'job_config_file_upload_help'         => 'Selecteer je bestand. Zorg ervoor dat het bestand UTF-8 gecodeerd is.',
    'job_config_file_upload_config_help'  => 'Als je eerder gegevens hebt geïmporteerd in Firefly III, heb je wellicht een configuratiebestand, dat een aantal zaken alvast voor je kan instellen. Voor bepaalde banken hebben andere gebruikers uit de liefde van hun hart het benodigde <a href="https://github.com/firefly-iii/import-configurations/wiki">configuratiebestand</a> gedeeld',
    'job_config_file_upload_type_help'    => 'Selecteer het type bestand dat je zal uploaden',
    'job_config_file_upload_submit'       => 'Bestanden uploaden',
    'import_file_type_csv'                => 'CSV (kommagescheiden waardes)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'Het bestand dat je hebt geüpload, is niet gecodeerd als UTF-8 of ASCII. Firefly III kan dergelijke bestanden niet verwerken. Gebruik Notepad ++ of Sublime om je bestand naar UTF-8 te converteren.',
    'job_config_uc_title'                 => 'Importinstellingen (2/4) - Algemene importinstellingen',
    'job_config_uc_text'                  => 'Om je bestand goed te kunnen importeren moet je deze opties verifiëren.',
    'job_config_uc_header_help'           => 'Vink hier als de eerste rij kolomtitels bevat.',
    'job_config_uc_date_help'             => 'Datum/tijd formaat in jouw bestand. Volg het formaat zoals ze het <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">op deze pagina</a> uitleggen. Het standaardformaat ziet er zo uit: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Kies het veldscheidingsteken dat in jouw bestand wordt gebruikt. Als je het niet zeker weet, is de komma de beste optie.',
    'job_config_uc_account_help'          => 'Als jouw CSV bestand geen referenties bevat naar jouw betaalrekening(en), geef dan hier aan om welke rekening het gaat.',
    'job_config_uc_apply_rules_title'     => 'Regels toepassen',
    'job_config_uc_apply_rules_text'      => 'Past je regels toe op elke geïmporteerde transactie. Merk op dat dit de import aanzienlijk vertraagt.',
    'job_config_uc_specifics_title'       => 'Bank-specifieke opties',
    'job_config_uc_specifics_txt'         => 'Sommige banken leveren slecht geformatteerde bestanden aan. Firefly III kan deze automatisch corrigeren. Als jouw bank dergelijke bestanden levert, maar deze hier niet wordt vermeld, open dan een issue op GitHub.',
    'job_config_uc_submit'                => 'Volgende',
    'invalid_import_account'              => 'Je hebt een ongeldige betaalrekening geselecteerd om in te importeren.',
    'import_liability_select'             => 'Passiva',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Kies je login',
    'job_config_spectre_login_text'       => 'Firefly III heeft :count bestaande login(s) gevonden in je Spectre-account. Welke wil je gebruiken om van te importeren?',
    'spectre_login_status_active'         => 'Actief',
    'spectre_login_status_inactive'       => 'Inactief',
    'spectre_login_status_disabled'       => 'Uitgeschakeld',
    'spectre_login_new_login'             => 'Log in via een andere bank, of via een van deze banken met andere inloggegevens.',
    'job_config_spectre_accounts_title'   => 'Selecteer de rekeningen waaruit je wilt importeren',
    'job_config_spectre_accounts_text'    => 'Je hebt ":name" (:country) geselecteerd. Je hebt :count rekening(en) bij deze provider. Kies de Firefly III betaalrekening(en) waar je de transacties in wilt opslaan. Denk er aan dat zowel de ":name"-rekeningen als de Firefly III rekeningen dezelfde valuta moeten hebben.',
    'spectre_do_not_import'               => '(niet importeren)',
    'spectre_no_mapping'                  => 'Je hebt geen rekeningen geselecteerd om uit te importeren.',
    'imported_from_account'               => 'Geïmporteerd uit ":account"',
    'spectre_account_with_number'         => 'Rekening :number',
    'job_config_spectre_apply_rules'      => 'Regels toepassen',
    'job_config_spectre_apply_rules_text' => 'Standaard worden je regels toegepast op de transacties die je tijdens deze routine importeert. Als je wilt dat dat niet gebeurt, zet dan het vinkje uit.',

    // job configuration for bunq:
    'should_download_config'              => 'Download <a href=":route">het configuratiebestand</a> voor deze import. Dit maakt toekomstige imports veel eenvoudiger.',
    'share_config_file'                   => 'Als je gegevens hebt geimporteerd van een gewone bank, <a href="https://github.com/firefly-iii/import-configurations/wiki">deel dan je configuratiebestand</a> zodat het makkelijk is voor andere gebruikers om hun gegevens te importeren. Als je je bestand deelt deel je natuurlijk géén privé-gegevens.',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Status',
    'spectre_extra_key_card_type'          => 'Kaartsoort',
    'spectre_extra_key_account_name'       => 'Rekeningnaam',
    'spectre_extra_key_client_name'        => 'Klantnaam',
    'spectre_extra_key_account_number'     => 'Rekeningnummer',
    'spectre_extra_key_blocked_amount'     => 'Geblokkeerd bedrag',
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

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Importinstellingen (4/4) - Link importgegevens aan Firefly III-gegevens',
    'job_config_map_text'             => 'In deze tabellen is de linkerwaarde een waarde uit je CSV bestand. Jij moet de link leggen, als mogelijk, met een waarde uit jouw database. Firefly houdt zich hier aan. Als er geen waarde is, selecteer dan ook niets.',
    'job_config_map_nothing'          => 'Je gaat geen gegevens importeren die te mappen zijn. Klik op "Start import" om verder te gaan.',
    'job_config_field_value'          => 'Veldwaarde',
    'job_config_field_mapped'         => 'Gelinkt aan',
    'map_do_not_map'                  => '(niet linken)',
    'job_config_map_submit'           => 'Start importeren',


    // import status page:
    'import_with_key'                 => 'Import met code \':key\'',
    'status_wait_title'               => 'Momentje...',
    'status_wait_text'                => 'Dit vak verdwijnt zometeen.',
    'status_running_title'            => 'De import is bezig',
    'status_job_running'              => 'Even geduld, de import loopt...',
    'status_job_storing'              => 'Even geduld, de gegevens worden opgeslagen...',
    'status_job_rules'                => 'Even geduld, je regels worden toegepast...',
    'status_fatal_title'              => 'Onherstelbare fout',
    'status_fatal_text'               => 'De import is tegen een fout aangelopen waar-ie niet meer van terug kan komen. Excuses!',
    'status_fatal_more'               => 'Deze (waarschijnlijk zeer cryptische) foutmelding wordt aangevuld door logbestanden, die je kan vinden op je harde schijf of in de Docker-container waar je Firefly III draait.',
    'status_finished_title'           => 'Importeren voltooid',
    'status_finished_text'            => 'Het importeren is voltooid.',
    'finished_with_errors'            => 'Er zijn enkele fouten opgetreden tijdens het importeren. Beoordeel ze zorgvuldig.',
    'unknown_import_result'           => 'Onbekend importresultaat',
    'result_no_transactions'          => 'Er zijn geen transacties geïmporteerd. Misschien waren ze allemaal dubbel, of er zijn simpelweg geen transacties gevonden die kunnen worden geïmporteerd. Misschien kunnen de logbestanden je vertellen wat er is gebeurd. Als je regelmatig gegevens importeert, is dit normaal.',
    'result_one_transaction'          => 'Precies één transactie is geïmporteerd. Je kan deze bekijken onder tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    'result_many_transactions'        => 'Firefly III heeft :count transacties geïmporteerd. Je kan ze inspecteren onder tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',

    // general errors and warnings:
    'bad_job_status'                  => 'Om deze pagina te bekijken mag je import-job niet de status ":status" hebben.',

    // error message
    'duplicate_row'                   => 'Rij #:row (":description) kan niet worden geïmporteerd. Deze bestaat al.',

];

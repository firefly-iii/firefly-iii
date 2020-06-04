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
    'index_breadcrumb'                    => 'Importer data til Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Forutsetninger for falsk import leverandør',
    'prerequisites_breadcrumb_spectre'    => 'Forutsetninger for Spectre',
    'job_configuration_breadcrumb'        => 'Konfigurasjon for ":key"',
    'job_status_breadcrumb'               => 'Importstatus for ":key"',
    'disabled_for_demo_user'              => 'deaktivert i demo',

    // index page:
    'general_index_intro'                 => 'Velkommen til Firefly IIIs importrutine. Det er flere måter å importere data på i Firefly III, vist her som knapper.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that the CSV importer will be moved to a new, separate tool. You can already beta-test this tool if you visit <a href="https://github.com/firefly-iii/csv-importer">this GitHub repository</a>. I would appreciate it if you would test the new importer and let me know what you think.',
    'final_csv_import'     => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that this is the last version of Firefly III that will feature a CSV importer. A separated tool is available that you should try for yourself: <a href="https://github.com/firefly-iii/csv-importer">the Firefly III CSV importer</a>. I would appreciate it if you would test the new importer and let me know what you think.',

    // import provider strings (index):
    'button_fake'                         => 'Utfør fake import',
    'button_file'                         => 'Importer fil',
    'button_spectre'                      => 'Importer med Spectre',

    // prerequisites box (index)
    'need_prereq_title'                   => 'Import forutsetninger',
    'need_prereq_intro'                   => 'Noen import metoder krever tilsyn før de kan bli benyttet. F. eks. at de krever en API nøkkel eller annen form for autentisering. DU kan konfigurere de her. Ikonet angir om forutsetningene har blitt oppfylt.',
    'do_prereq_fake'                      => 'Forutsetninger for falsk leverandør',
    'do_prereq_file'                      => 'Forutsetninger for fil import',
    'do_prereq_spectre'                   => 'Forutsetninger for import fra Spectre',

    // prerequisites:
    'prereq_fake_title'                   => 'Forutsetninger for import fra falsk leverandør',
    'prereq_fake_text'                    => 'Denne falske leverandøren krever en falsk API-nøkkel. Det må være 32 tegn. Du kan bruke denne: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Forutsetninger for å importere med Spectre API',
    'prereq_spectre_text'                 => 'For å importere data ved hjelp av Spectre API (v4), må du angi Firefly III to nøkkel verdier. De kan finnes på <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_spectre_pub'                  => 'Spectre API må også vite fellesnøkkelen du ser nedenfor. Uten den, vil den ikke gjenkjenne deg. Angi denne offentlige nøkkelen fra din <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'callback_not_tls'                    => 'Firefly III har oppdaget følgende callback URI. Det ser ut som serveren din er ikke konfigurert til å godta TLS-tilkoblinger (https). YNAB vil ikke godta denne URI. Du kan fortsette med importen (fordi Firefly III kan ta feil), men vær obs.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Falsk API-nøkkel lagret riktig!',
    'prerequisites_saved_for_spectre'     => 'App ID og secret lagret!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Jobb konfigurasjon - iverksette dine regler?',
    'job_config_apply_rules_text'         => 'Når den falske leverandøren har kjørt, kan reglene bli brukt på transaksjonene. Dette vil legge til ekstra tid på import jobben.',
    'job_config_input'                    => 'Ditt innspill',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Skriv inn albumnavn',
    'job_config_fake_artist_text'         => 'Noen import rutiner har konfigurasjonstrinn må du gå gjennom. I den falske import leverandøren må du svare på noen rare spørsmål. I dette tilfellet angi "David Bowie" for å fortsette.',
    'job_config_fake_song_title'          => 'Skriv inn sangtittel',
    'job_config_fake_song_text'           => 'Nevn sangen "Gylne år" for å fortsette med den falske importen.',
    'job_config_fake_album_title'         => 'Skriv inn albumnavn',
    'job_config_fake_album_text'          => 'Noen import rutiner krever tilleggsdata midt i import prosessen. I den falske import leverandøren må du svare noen rare spørsmål. Angi «Station to Station» for å fortsette.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Importoppsett (1/4) - Last opp filen din',
    'job_config_file_upload_text'         => 'Denne rutinen vil hjelpe deg med å importere filer fra banken din til Firefly III. ',
    'job_config_file_upload_help'         => 'Velg fil. Husk å kontroller at filen er UTF-8-kodet.',
    'job_config_file_upload_config_help'  => 'Hvis du tidligere har importert data til Firefly III, kan du ha en konfigurasjonsfil liggende som har forhåndsfylte konfigurasjonsverdier for deg. For enkelte banker har andre brukere lagt ut konfigurasjonsfiler, se <a href="https://github.com/firefly-iii/import-configurations/wiki">konfigurasjonen fil</a>',
    'job_config_file_upload_type_help'    => 'Velg type fil du vil laste opp',
    'job_config_file_upload_submit'       => 'Last opp filer',
    'import_file_type_csv'                => 'CSV (kommaseparerte verdier)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'Filen du har lastet opp er ikke kodet som UTF-8 eller ASCII. Firefly III kan ikke håndtere slike filer. Bruk Notepad ++ eller Sublime for å konvertere filen til UTF-8.',
    'job_config_uc_title'                 => 'Importoppsett (2/4) - Grunnleggende fil oppsett',
    'job_config_uc_text'                  => 'For å kunne importere filen riktig vennligst valider alternativene nedenfor.',
    'job_config_uc_header_help'           => 'Merk av denne boksen hvis den første raden i CSV-filen din består av kolonnetitler.',
    'job_config_uc_date_help'             => 'Datoformat for tiden i filen. Bruk formatet <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">denne side</a> angir. Standardverdien vil parse datoene til å se slik ut: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Velg feltskilletegnet som brukes i filen din. Hvis du ikke er sikker så er komma det tryggeste alternativet.',
    'job_config_uc_account_help'          => 'Hvis filen IKKE inneholder informasjon om dine bruks/aktiva konto(er), bruk denne rullegardinlisten til å velge hvilken konto transaksjonene i filen tilhører.',
    'job_config_uc_apply_rules_title'     => 'Utfør regler',
    'job_config_uc_apply_rules_text'      => 'Utfører dine regler på alle importerte transaksjoner. Merk at dette gjør importen betydelig tregere.',
    'job_config_uc_specifics_title'       => 'Bank-spesifikke alternativer',
    'job_config_uc_specifics_txt'         => 'Enkelte banker gir dårlig formaterte filer. Firefly III kan fikse disse automatisk. Hvis banken din leverer slike filer men er ikke oppført her, vennligst lag en sak på GitHub.',
    'job_config_uc_submit'                => 'Fortsett',
    'invalid_import_account'              => 'Du har valgt en ugyldig konto å importere til.',
    'import_liability_select'             => 'Gjeld',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Velg innlogging',
    'job_config_spectre_login_text'       => 'Firefly III har funnet :count eksisterende innlogginger på din Spectre konto. Hvilken vil du importere fra?',
    'spectre_login_status_active'         => 'Aktiv',
    'spectre_login_status_inactive'       => 'Inaktiv',
    'spectre_login_status_disabled'       => 'Deaktivert',
    'spectre_login_new_login'             => 'Logg inn med annen bank, eller en av disse bankene med forskjellig legitimasjon.',
    'job_config_spectre_accounts_title'   => 'Velg kontoer å importere fra',
    'job_config_spectre_accounts_text'    => 'Du har valgt ":name" (:country). Du har :count tilgjengelige kontorer fra denne leverandøren. Velg Firefly III bruks/aktiva kontoen(e) der transaksjonene fra disse kontoene skal lagres. Husk, for å importere data fra Firefly III kontoen og ":name"-kontoen må begge ha samme valuta.',
    'spectre_do_not_import'               => '(Ikke importer)',
    'spectre_no_mapping'                  => 'Du har ikke valgt noen kontoer å importere fra.',
    'imported_from_account'               => 'Importert fra ":account"',
    'spectre_account_with_number'         => 'Konto :number',
    'job_config_spectre_apply_rules'      => 'Bruk regler',
    'job_config_spectre_apply_rules_text' => 'Som standard vil reglene brukes på transaksjoner som er opprettet under denne importrutinen. Hvis du ikke ønsker at dette skal skje må du avhuke valget.',

    // job configuration for bunq:
    'should_download_config'              => 'Du bør laste ned <a href=":route">konfigurasjonen filen</a> for denne jobben. Dette vil gjøre fremtidige import jobber lettere.',
    'share_config_file'                   => 'Hvis du har importert data fra en offentlig bank bør du <a href="https://github.com/firefly-iii/import-configurations/wiki">dele din konfigurasjons fil</a> slik at det blir lettere for andre brukere å importere sin data. Å dele din konfigurasjons fil vil ikke eksponere dine finanisielle data eller verdier.',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Status',
    'spectre_extra_key_card_type'          => 'Korttype',
    'spectre_extra_key_account_name'       => 'Kontonavn',
    'spectre_extra_key_client_name'        => 'Klientnavn',
    'spectre_extra_key_account_number'     => 'Kontonummer',
    'spectre_extra_key_blocked_amount'     => 'Blokkert beløp',
    'spectre_extra_key_available_amount'   => 'Tilgjengelig beløp',
    'spectre_extra_key_credit_limit'       => 'Kredittgrense',
    'spectre_extra_key_interest_rate'      => 'Rentesats',
    'spectre_extra_key_expiry_date'        => 'Utløpsdato',
    'spectre_extra_key_open_date'          => 'Åpningsdato',
    'spectre_extra_key_current_time'       => 'Gjeldende tid',
    'spectre_extra_key_current_date'       => 'Gjeldende dato',
    'spectre_extra_key_cards'              => 'Kort',
    'spectre_extra_key_units'              => 'Enheter',
    'spectre_extra_key_unit_price'         => 'Enhetspris',
    'spectre_extra_key_transactions_count' => 'Transaksjonsantall',

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Importoppsett (4/4) - Koble importdata til Firefly III-data',
    'job_config_map_text'             => 'I følgende tabeller viser verdien til venstre informasjonen funnet i den opplastede filen. Det er din oppgave å knytte denne verdien, hvis mulig, til en eksisterende verdi i databasen. Firefly vil holde seg til denne tilordningen. Hvis det er ingen verdi å knytte til, eller du ikke ønsker å knytte den, velg ingenting.',
    'job_config_map_nothing'          => 'Det finnes ingen data i filen som du kan koble til eksisterende verdier. Vennligst trykk "Start import" for å fortsette.',
    'job_config_field_value'          => 'Feltverdi',
    'job_config_field_mapped'         => 'Koblet til',
    'map_do_not_map'                  => '(ikke koble til)',
    'job_config_map_submit'           => 'Start importen',


    // import status page:
    'import_with_key'                 => 'Importer med nøkkel \':key \'',
    'status_wait_title'               => 'Vennligst vent...',
    'status_wait_text'                => 'Denne boksen vil forsvinne om et øyeblikk.',
    'status_running_title'            => 'Importen kjører',
    'status_job_running'              => 'Vennligst vent, kjører import...',
    'status_job_storing'              => 'Vennligst vent, lagrer data...',
    'status_job_rules'                => 'Vennligst vent, kjører regler...',
    'status_fatal_title'              => 'Uopprettelig feil',
    'status_fatal_text'               => 'Det har oppstått en feil som importen ikke kunne gjenopprette seg fra. Beklager!',
    'status_fatal_more'               => 'Denne (muligens meget krytpiske) feilmeldingen suppleres av loggfiler, som du finner på harddisken eller i Docker container der du kjører Firefly III fra.',
    'status_finished_title'           => 'Import fullført',
    'status_finished_text'            => 'Importen er ferdig.',
    'finished_with_errors'            => 'Det oppstod enkelte feil under import. Vennligst les disse nøye.',
    'unknown_import_result'           => 'Ukjent import resultat',
    'result_no_transactions'          => 'Ingen transaksjoner er importert. Kanskje var alle duplikater eller så var det ingen transaksjoner tilstede som kunne importeres. Loggfilene kan gi en bedre indikering på hva som har oppstått. Hvis du importerer data regelmessig vil dette være normalt.',
    'result_one_transaction'          => 'Nøyaktig én transaksjon er importert. Den er lagret under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> hvor kan du undersøke videre.',
    'result_many_transactions'        => 'Firefly III har importert :count transaksjoner. De er lagret under tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> hvor du kan undersøke dem videre.',

    // general errors and warnings:
    'bad_job_status'                  => 'For å få adgang til denne siden kan ikke import jobben ha status ":status".',

    // error message
    'duplicate_row'                   => 'Row #:row (":description") could not be imported. It already exists.',

];

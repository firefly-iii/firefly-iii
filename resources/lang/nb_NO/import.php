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
    'prerequisites_breadcrumb_bunq'       => 'Forutsetninger for Bunq',
    'prerequisites_breadcrumb_ynab'       => 'Forutsetninger for YNAB',
    'job_configuration_breadcrumb'        => 'Konfigurasjon for ":key"',
    'job_status_breadcrumb'               => 'Importstatus for ":key"',
    'disabled_for_demo_user'              => 'deaktivert i demo',

    // index page:
    'general_index_intro'                 => 'Velkommen til Firefly IIIs importrutine. Det er flere måter å importere data på i Firefly III, vist her som knapper.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that the CSV importer will be moved to a new, separate tool. You can already beta-test this tool if you visit <a href="https://github.com/firefly-iii/csv-importer">this GitHub repository</a>. I would appreciate it if you would test the new importer and let me know what you think.',

    // import provider strings (index):
    'button_fake'                         => 'Utfør fake import',
    'button_file'                         => 'Importer fil',
    'button_bunq'                         => 'Importer fra bunq',
    'button_spectre'                      => 'Importer med Spectre',
    'button_plaid'                        => 'Importer med Plaid',
    'button_yodlee'                       => 'Importer med Yodlee',
    'button_quovo'                        => 'Importer med Quovo',
    'button_ynab'                         => 'Importer fra You Need A Budget',
    'button_fints'                        => 'Importer med FinTS',


    // prerequisites box (index)
    'need_prereq_title'                   => 'Import forutsetninger',
    'need_prereq_intro'                   => 'Noen import metoder krever tilsyn før de kan bli benyttet. F. eks. at de krever en API nøkkel eller annen form for autentisering. DU kan konfigurere de her. Ikonet angir om forutsetningene har blitt oppfylt.',
    'do_prereq_fake'                      => 'Forutsetninger for falsk leverandør',
    'do_prereq_file'                      => 'Forutsetninger for fil import',
    'do_prereq_bunq'                      => 'Forutsetninger for import fra bunq',
    'do_prereq_spectre'                   => 'Forutsetninger for import fra Spectre',
    'do_prereq_plaid'                     => 'Forutsetninger for import fra Plaid',
    'do_prereq_yodlee'                    => 'Forutsetninger for import fra Yodlee',
    'do_prereq_quovo'                     => 'Forutsetninger for import fra Quovo',
    'do_prereq_ynab'                      => 'Forutsetninger for import fra YNAB',

    // prerequisites:
    'prereq_fake_title'                   => 'Forutsetninger for import fra falsk leverandør',
    'prereq_fake_text'                    => 'Denne falske leverandøren krever en falsk API-nøkkel. Det må være 32 tegn. Du kan bruke denne: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Forutsetninger for å importere med Spectre API',
    'prereq_spectre_text'                 => 'For å importere data ved hjelp av Spectre API (v4), må du angi Firefly III to nøkkel verdier. De kan finnes på <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_spectre_pub'                  => 'Spectre API må også vite fellesnøkkelen du ser nedenfor. Uten den, vil den ikke gjenkjenne deg. Angi denne offentlige nøkkelen fra din <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'prereq_bunq_title'                   => 'Forutsetninger for å importere med bunq',
    'prereq_bunq_text'                    => 'For å importere fra bunq må du skaffe en API-nøkkel. Du kan gjøre dette gjennom appen. Vær oppmerksom på at importfunksjonen i bunq er i BETA. Den har kun blitt testet mot sandbox API\'et.',
    'prereq_bunq_ip'                      => 'bunq krever din eksterne (internett) IP-adresse. Firefly III har forsøkt å fylle dette ved å bruke <a href="https://www.ipify.org/">ipify service</a>. Kontroller at IP-adressen er riktig, hvis ikke vil importen mislykkes.',
    'prereq_ynab_title'                   => 'Forutsetninger for import fra YNAB',
    'prereq_ynab_text'                    => 'For å kunne laste ned transaksjoner fra YNAB, vennligst opprett en ny applikasjon på din <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> og angi client ID og nøkkel/secret fra denne siden.',
    'prereq_ynab_redirect'                => 'For å fullføre konfigurasjonen, oppgi følgende URL på <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> under "Redirect URI(s)".',
    'callback_not_tls'                    => 'Firefly III har oppdaget følgende callback URI. Det ser ut som serveren din er ikke konfigurert til å godta TLS-tilkoblinger (https). YNAB vil ikke godta denne URI. Du kan fortsette med importen (fordi Firefly III kan ta feil), men vær obs.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Falsk API-nøkkel lagret riktig!',
    'prerequisites_saved_for_spectre'     => 'App ID og secret lagret!',
    'prerequisites_saved_for_bunq'        => 'API-nøkkel og IP lagret!',
    'prerequisites_saved_for_ynab'        => 'YNAB klient-ID og secret lagret!',

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
    'job_config_bunq_accounts_title'      => 'bunq-kontoer',
    'job_config_bunq_accounts_text'       => 'Dette er kontoene knyttet til din bunq-konto. Vennligst velg kontoene du vil importere fra, og hvilken konto transaksjonene skal importeres til.',
    'bunq_no_mapping'                     => 'Du har ikke valgt noen kontoer.',
    'should_download_config'              => 'Du bør laste ned <a href=":route">konfigurasjonen filen</a> for denne jobben. Dette vil gjøre fremtidige import jobber lettere.',
    'share_config_file'                   => 'Hvis du har importert data fra en offentlig bank bør du <a href="https://github.com/firefly-iii/import-configurations/wiki">dele din konfigurasjons fil</a> slik at det blir lettere for andre brukere å importere sin data. Å dele din konfigurasjons fil vil ikke eksponere dine finanisielle data eller verdier.',
    'job_config_bunq_apply_rules'         => 'Bruk regler',
    'job_config_bunq_apply_rules_text'    => 'Som standard vil reglene brukes på transaksjoner som er opprettet under denne importrutinen. Hvis du ikke ønsker at dette skal skje må du avhuke valget.',
    'bunq_savings_goal'                   => 'Spare mål: :amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => 'Stengt bunq konto',

    'ynab_account_closed'                  => 'Konto er stengt!',
    'ynab_account_deleted'                 => 'Konto er slettet!',
    'ynab_account_type_savings'            => 'sparekonto',
    'ynab_account_type_checking'           => 'brukskonto',
    'ynab_account_type_cash'               => 'kontantkonto',
    'ynab_account_type_creditCard'         => 'kredittkort',
    'ynab_account_type_lineOfCredit'       => 'kredittgrense',
    'ynab_account_type_otherAsset'         => 'annen aktivakonto',
    'ynab_account_type_otherLiability'     => 'andre gjeld',
    'ynab_account_type_payPal'             => 'PayPal',
    'ynab_account_type_merchantAccount'    => 'selgerkonto',
    'ynab_account_type_investmentAccount'  => 'investerings konto',
    'ynab_account_type_mortgage'           => 'pant',
    'ynab_do_not_import'                   => '(Ikke importer)',
    'job_config_ynab_apply_rules'          => 'Bruk regler',
    'job_config_ynab_apply_rules_text'     => 'Som standard vil reglene brukes på transaksjoner som er opprettet under denne importrutinen. Hvis du ikke ønsker at dette skal skje må du avhuke valget.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Velg ditt budsjett',
    'job_config_ynab_select_budgets_text'  => 'Du har :count budsjett lagret hos YNAB. Vennligst velg en som Firefly III kan importere transaksjonene til.',
    'job_config_ynab_no_budgets'           => 'Det er ingen budsjett tilgjengelig å importere fra.',
    'ynab_no_mapping'                      => 'Du har ikke valgt noen kontoer å importere fra.',
    'job_config_ynab_bad_currency'         => 'Du kan ikke importere fra følgende budsjett, fordi du har ingen kontoer med samme valuta som disse budsjettene.',
    'job_config_ynab_accounts_title'       => 'Velg konto',
    'job_config_ynab_accounts_text'        => 'Du har følgende kontoer tilgjengelig i dette budsjettet. Vennligst velg hvilken konto du vil importere og hvor transaksjonene skal lagres.',


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

    //job configuration for finTS
    'fints_connection_failed'              => 'En feil oppsto ved tilkobling til din bank. Vennligst verifiser at all data du har skrevet inn er korrekt. Feilmelding: :originalError',

    'job_config_fints_url_help'       => 'F.eks. https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => 'For mange banker er dette konto nummeret ditt.',
    'job_config_fints_port_help'      => 'Standard porten er 443.',
    'job_config_fints_account_help'   => 'Velg bank kontoen du ønsker å importere transaksjoner til.',
    'job_config_local_account_help'   => 'Velg Firefly III kontoen knyttet til din bank konto valgt ovenfor.',
    // specifics:
    'specific_ing_name'               => 'ING NL',
    'specific_ing_descr'              => 'Lag bedre beskrivelser i ING eksporter',
    'specific_sns_name'               => 'SNS / Volksbank NL',
    'specific_sns_descr'              => 'Trim tekst fra SNS / Volksbank eksport filer',
    'specific_abn_name'               => 'ABN AMRO NL',
    'specific_abn_descr'              => 'Fikser potensielle problemer med ABN AMRO filer',
    'specific_rabo_name'              => 'Rabobank NL',
    'specific_rabo_descr'             => 'Fikser potensielle problemer med Rabobank filer',
    'specific_pres_name'              => 'President\'s Choice Financial CA',
    'specific_pres_descr'             => 'Fikser potensielle problemer med PC filer',
    'specific_belfius_name'           => 'Belfius BE',
    'specific_belfius_descr'          => 'Fikser potensielle problemer med Belfius filer',
    'specific_ingbelgium_name'        => 'ING BE',
    'specific_ingbelgium_descr'       => 'Fixes potential problems with ING Belgium files',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'          => 'Importoppsett (3/4) - Definer hver kolonnes rolle',
    'job_config_roles_text'           => 'Hver kolonne i CSV filen inneholder visse data. Vennligst indiker hvilken type data importen kan forvente. "Map" valget indikerer at du vil knytte hver oppføring funnet i kolonnen til en verdi in databasen. En ofte knyttet kolonne is kolonnen som inneholder IBAN til motstående konto. Dette kan enkelt matches mot IBAN verdier som er i databasen allerede.',
    'job_config_roles_submit'         => 'Fortsett',
    'job_config_roles_column_name'    => 'Navn på kolonne',
    'job_config_roles_column_example' => 'Kolonneeksempeldata',
    'job_config_roles_column_role'    => 'Kolonne forklaring',
    'job_config_roles_do_map_value'   => 'Sammenkoble disse verdiene',
    'job_config_roles_no_example'     => 'Ingen eksempeldata tilgjengelig',
    'job_config_roles_fa_warning'     => 'Hvis du merker en kolonne som inneholder et beløp i en fremmed valuta, må du også angi kolonnen som inneholder hvilken valuta det er.',
    'job_config_roles_rwarning'       => 'I det minste, merk én kolonne som beløps-kolonnen. Det anbefales også velge en kolonne for beskrivelse, dato og motstående konto.',
    'job_config_roles_colum_count'    => 'Kolonne',
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

    // column roles for CSV import:
    'column__ignore'                  => '(ignorer denne kolonnen)',
    'column_account-iban'             => 'Aktivakonto (IBAN)',
    'column_account-id'               => 'Aktivakonto-ID (koblet til FF3)',
    'column_account-name'             => 'Aktivakonto (navn)',
    'column_account-bic'              => 'Aktivakonto (BIC)',
    'column_amount'                   => 'Beløp',
    'column_amount_foreign'           => 'Beløp (i utenlandsk valuta)',
    'column_amount_debit'             => 'Beløp (debetkolonne)',
    'column_amount_credit'            => 'Beløp (kredittkolonne)',
    'column_amount_negated'           => 'Beløp (invers kolonne)',
    'column_amount-comma-separated'   => 'Beløp (komma som desimaltegn)',
    'column_bill-id'                  => 'Regning-ID (koblet til FF3)',
    'column_bill-name'                => 'Regningsnavn',
    'column_budget-id'                => 'Budsjett-ID (koblet til FF3)',
    'column_budget-name'              => 'Budsjettnavn',
    'column_category-id'              => 'Kategori-ID (samsvarer FF3)',
    'column_category-name'            => 'Kategorinavn',
    'column_currency-code'            => 'Valutakode (ISO 4217)',
    'column_foreign-currency-code'    => 'Utenlandsk valutakode (ISO 4217)',
    'column_currency-id'              => 'Valuta-ID (samsvarer FF3)',
    'column_currency-name'            => 'Valutanavn (samsvarer FF3)',
    'column_currency-symbol'          => 'Valuta symbol (samsvarer FF3)',
    'column_date-interest'            => 'Renteberegningsdato',
    'column_date-book'                => 'Bokføringsdato for transaksjon',
    'column_date-process'             => 'Prosesseringsdato for transaksjon',
    'column_date-transaction'         => 'Dato',
    'column_date-due'                 => 'Forfallsdato for transaksjon',
    'column_date-payment'             => 'Betalingsdato for transaksjon',
    'column_date-invoice'             => 'Fakturadato for transaksjon',
    'column_description'              => 'Beskrivelse',
    'column_opposing-iban'            => 'Motkonto (IBAN)',
    'column_opposing-bic'             => 'Motkonto (BIC)',
    'column_opposing-id'              => 'Motstående konto ID (samsvarer FF3)',
    'column_external-id'              => 'Ekstern ID',
    'column_opposing-name'            => 'Motstående konto (navn)',
    'column_rabo-debit-credit'        => 'Rabobank spesifikk debet/kreditt indikator',
    'column_ing-debit-credit'         => 'ING spesifikk debet/kreditt indikator',
    'column_generic-debit-credit'     => 'Generisk bank debet/kreditt indikator',
    'column_sepa_ct_id'               => 'SEPA ende-til-ende identifikator',
    'column_sepa_ct_op'               => 'SEPA Motstående kontoidentifikator',
    'column_sepa_db'                  => 'SEPA Mandat identifikator',
    'column_sepa_cc'                  => 'SEPA klareringskode',
    'column_sepa_ci'                  => 'SEPA kreditoridentifikator',
    'column_sepa_ep'                  => 'SEPA Eksternt formål',
    'column_sepa_country'             => 'SEPA landskode',
    'column_sepa_batch_id'            => 'SEPA Batch ID',
    'column_tags-comma'               => 'Tagger (kommaseparerte)',
    'column_tags-space'               => 'Tagger (oppdelt med mellomrom)',
    'column_account-number'           => 'Aktivakonto (kontonummer)',
    'column_opposing-number'          => 'Motkonto (kontonummer)',
    'column_note'                     => 'Notat(er)',
    'column_internal-reference'       => 'Intern referanse',

    // error message
    'duplicate_row'                   => 'Row #:row (":description") could not be imported. It already exists.',

];

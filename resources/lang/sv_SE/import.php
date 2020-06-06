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
    'index_breadcrumb'                    => 'Importera data till Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Förutsättningar för den fejkade importleverantören',
    'prerequisites_breadcrumb_spectre'    => 'Förutsättningar för Spectre',
    'job_configuration_breadcrumb'        => 'Konfiguration för ":key"',
    'job_status_breadcrumb'               => 'Importstatus för ":key"',
    'disabled_for_demo_user'              => 'inaktiverad i demo',

    // index page:
    'general_index_intro'                 => 'Välkommen till importrutinen i Firefly III. Knapparna visar de olika sätten att importera data till Firefly III.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that the CSV importer will be moved to a new, separate tool. You can already beta-test this tool if you visit <a href="https://github.com/firefly-iii/csv-importer">this GitHub repository</a>. I would appreciate it if you would test the new importer and let me know what you think.',
    'final_csv_import'     => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that this is the last version of Firefly III that will feature a CSV importer. A separated tool is available that you should try for yourself: <a href="https://github.com/firefly-iii/csv-importer">the Firefly III CSV importer</a>. I would appreciate it if you would test the new importer and let me know what you think.',

    // import provider strings (index):
    'button_fake'                         => 'Fejka en import',
    'button_file'                         => 'Importera en fil',
    'button_spectre'                      => 'Importera med Spectre',

    // prerequisites box (index)
    'need_prereq_title'                   => 'Importkrav',
    'need_prereq_intro'                   => 'Vissa importmetoder behöver konfigureras innan de kan användas. De kan till exempel kräva speciella API-nycklar eller applikationshemligheter. Du kan konfigurera dem här. Ikonen indikerar om dessa förutsättningar har uppfyllts.',
    'do_prereq_fake'                      => 'Förutsättningar för den fejkade leverantören',
    'do_prereq_file'                      => 'Förutsättningar för filimport',
    'do_prereq_spectre'                   => 'Förutsättningar för import med Spectre',

    // prerequisites:
    'prereq_fake_title'                   => 'Förutsättningar för import från den fejkade importleverantören',
    'prereq_fake_text'                    => 'Den fejkade leverantören kräver en fejkad API-nyckel. Den måste vara 32 tecken lång. Du kan använda den här: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Förutsättningar för import med Spectre API',
    'prereq_spectre_text'                 => 'För att importera data med Spectre API (v4) krävs två hemliga värden. Dessa kan hittas på <a href="https://www.saltedge.com/clients/profile/secrets">sidan för hemligheter</a>.',
    'prereq_spectre_pub'                  => 'Spectre API kräver även den pubilka nyckeln som syns nedan. Utan den kommer den inte att känna igen dig. Ange den publika nyckeln på din <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'callback_not_tls'                    => 'Firefly III har detekterat följande callback URI. Det verkar som din server inte är uppsatt att acceptera TLS-anslutningar (https). YNAB accepterar inte denna URI. Du kan fortsätta importen (för Firefly III kan ha fel) men ha detta i åtanke.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Lyckades spara falsk API nyckel!',
    'prerequisites_saved_for_spectre'     => 'App ID och hemlighet lagrad!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Jobbkonfiguration - applicera dina regler?',
    'job_config_apply_rules_text'         => 'När den falska leverantören har körts, kommer dina reglar att appliceras på transaktionerna. Detta lägger på tid på importen.',
    'job_config_input'                    => 'Din data',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Ange album namn',
    'job_config_fake_artist_text'         => 'Många importrutiner har några konfigurationssteg som måste gås genom. I fallet med fejk importhanteraren, så måste du svara några konstiga fråga. Som i detta fall, ange "David Bowie" för att fortsätta.',
    'job_config_fake_song_title'          => 'Ange låtnamn',
    'job_config_fake_song_text'           => 'Nämn låten "Golden years" för att fortsätta med fejkade importen.',
    'job_config_fake_album_title'         => 'Ange album namn',
    'job_config_fake_album_text'          => 'Vissa importrutiner behöver extra information halvvägs genom importen. I fallet med fejk importhanteraren, så måste du svara några konstiga fråga. Ange "Station to station" för att fortsätta.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Importinställningar (1/4) - Ladda upp fil',
    'job_config_file_upload_text'         => 'Denna rutin hjälper till att importera filer från din bank och till Firefly III. ',
    'job_config_file_upload_help'         => 'Välj din fil. Säkerställ att filen är UTF-8 kodad.',
    'job_config_file_upload_config_help'  => 'Om du tidigare har importerat data till Firefly III, kan du ha en konfigurationsfil, vilken sätter vissa konfigurationsvärden åt dig. För vissa banker behöver användare ange deras <a href="https://github.com/firefly-iii/import-configurations/wiki">konfigurationsfil</a>',
    'job_config_file_upload_type_help'    => 'Välj typ av fil som ska laddas upp',
    'job_config_file_upload_submit'       => 'Ladde upp filer',
    'import_file_type_csv'                => 'CSV (kommaseparerade värden)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'Filen du valde är inte kodad med UTF-8 eller ASCII. Firefly III kan inte hantera sådana filer. Vänligen använd Notepad++ eller Sublime för att konvertera den till UTF-8.',
    'job_config_uc_title'                 => 'Importeringsinställning (2/4) - Standard filinställningar',
    'job_config_uc_text'                  => 'För att importera filer korrekt, vänligen verifiera valen nedan.',
    'job_config_uc_header_help'           => 'Kryssa i denna ruta om första raden i din CSV är kolumntitlar.',
    'job_config_uc_date_help'             => 'Datum, tidformat i er fil. Följ formatet som <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">denna sida</a> beskriver. Som standard tolkas datum på följande sätt :dateExample.',
    'job_config_uc_delimiter_help'        => 'Välj fältavgränsare som används i inmatningsfilen. Om osäker, är komma det bästa valet.',
    'job_config_uc_account_help'          => 'Om filen INTE innehåller information om tillgångskont(on), använd denna rullgardinsmeny för att välja vilket konto transaktionerna i filen tillhör.',
    'job_config_uc_apply_rules_title'     => 'Tillämpa regler',
    'job_config_uc_apply_rules_text'      => 'Tillämpar dina reglar på alla importerade transaktioner. Notera att detta saktar ner importen betydligt.',
    'job_config_uc_specifics_title'       => 'Bankspecifika alternativ',
    'job_config_uc_specifics_txt'         => 'Vissa banker leverar dåligt formaterade filer. Firefly III kan laga dessa automatiskt. Om din bank levererar sådana filer men inte listas här, öppna ett ärende på GitHub.',
    'job_config_uc_submit'                => 'Fortsätt',
    'invalid_import_account'              => 'Ogiltigt konto att importera till.',
    'import_liability_select'             => 'Skyldighet',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Välj inloggningstyp',
    'job_config_spectre_login_text'       => 'Firefly III har funnit :count befintliga inloggning(ar) på ditt nuvarande Spectre konto. Vilken vill du använda för import ifrån?',
    'spectre_login_status_active'         => 'Aktiv',
    'spectre_login_status_inactive'       => 'Inaktiv',
    'spectre_login_status_disabled'       => 'Inaktiverad',
    'spectre_login_new_login'             => 'Logga in med en annan bank eller någon av dessa banker med olika referenser.',
    'job_config_spectre_accounts_title'   => 'Välj konton att importera från',
    'job_config_spectre_accounts_text'    => 'Du har valt ":name" (:country). Det finns :count konto(n) tillgängliga från denna leverantör. Vänligen välj de Firefly III tillgångskonto(n) som transaktionerna för dessa konton ska sparas. Kom ihåg, för att importera data både från Firefly III konto och ":name"-konto måste båda vara i samma valuta.',
    'spectre_do_not_import'               => '(importera ej)',
    'spectre_no_mapping'                  => 'Det verkar som att du ej valt något konto att importera från.',
    'imported_from_account'               => 'Importerat från ":account"',
    'spectre_account_with_number'         => 'Konto :number',
    'job_config_spectre_apply_rules'      => 'Tillämpa regler',
    'job_config_spectre_apply_rules_text' => 'Som standard kan dina regler tillämpas på transaktioner skapade under denna importrutin. Om du inte vill att detta ska ske, kryssa ur denna ruta.',

    // job configuration for bunq:
    'should_download_config'              => 'Du bör ladda ner <a href=":route">konfigurationsfilen</a> för detta jobb. Det gör framtida importer enklare.',
    'share_config_file'                   => 'Om du har importerat data från en offentlig bank bör du <a href="https://github.com/firefly-iii/import-configurations/wiki">dela din konfigurationsfil</a> så att det blir lätt för andra användare att importera sina data. Att dela din konfigurationsfil avslöjar inte dina finansiella detaljer.',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Status',
    'spectre_extra_key_card_type'          => 'Korttyp',
    'spectre_extra_key_account_name'       => 'Kontonamn',
    'spectre_extra_key_client_name'        => 'Klientnamn',
    'spectre_extra_key_account_number'     => 'Kontonummer',
    'spectre_extra_key_blocked_amount'     => 'Blockerat belopp',
    'spectre_extra_key_available_amount'   => 'Tillgängligt belopp',
    'spectre_extra_key_credit_limit'       => 'Kreditgräns',
    'spectre_extra_key_interest_rate'      => 'Räntesats',
    'spectre_extra_key_expiry_date'        => 'Förfallodatum',
    'spectre_extra_key_open_date'          => 'Öppningsdatum',
    'spectre_extra_key_current_time'       => 'Aktuell tid',
    'spectre_extra_key_current_date'       => 'Aktuellt datum',
    'spectre_extra_key_cards'              => 'Kort',
    'spectre_extra_key_units'              => 'Enheter',
    'spectre_extra_key_unit_price'         => 'Enhetspris',
    'spectre_extra_key_transactions_count' => 'Antal transaktioner',

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Importinställningar (4 / 4) - Anslut importdata till Firefly III data',
    'job_config_map_text'             => 'I följande tabeller, visar värdena till vänster information som finns i den uppladdade filen. Det är upp till dig att kartlägga dessa värden, om möjligt till värden som finns i din databas. Firefly försöker hålla sig till detta. Om det saknas värden, eller om inte önskar att koppla mot specifika värden, behöver inget väljas.',
    'job_config_map_nothing'          => 'Data saknas i fil för att kartlägga mot existerande värden. Tryck "Starta importen" för att fortsätta.',
    'job_config_field_value'          => 'Fältvärde',
    'job_config_field_mapped'         => 'Mappad mot',
    'map_do_not_map'                  => '(kartlägg inte)',
    'job_config_map_submit'           => 'Starta importen',


    // import status page:
    'import_with_key'                 => 'Importera med nyckel \':key\'',
    'status_wait_title'               => 'Vänligen vänta...',
    'status_wait_text'                => 'Denna ruta försvinner om en stund.',
    'status_running_title'            => 'Importen körs',
    'status_job_running'              => 'Vänligen vänta. kör import...',
    'status_job_storing'              => 'Vänligen vänta, lagrar data...',
    'status_job_rules'                => 'Vänligen vänta, regler körs...',
    'status_fatal_title'              => 'Kritiskt fel',
    'status_fatal_text'               => 'Importen utsattes för ett fel den inte kunde återhämta ifrån. Ursäkta!',
    'status_fatal_more'               => 'Detta (möjligen kryptiska) felmeddelande komplementeras i loggfilerna, som du kan finna på din hårddisk, eller i Docker behållaren där Firefly III körs ifrån.',
    'status_finished_title'           => 'Import slutförd',
    'status_finished_text'            => 'Importen har slutförts.',
    'finished_with_errors'            => 'Det fanns några fel vid importen. Gå genom dem noggrant.',
    'unknown_import_result'           => 'Okänt importresultat',
    'result_no_transactions'          => 'Inga transaktioner har importerats. Kan det var dubbletter eller så fanns det inga transaktioner att importera. Kanske loggfilerna kan berätta vad som hände. Om du importerar data regelbundet, kan det vara normalt.',
    'result_one_transaction'          => 'En transaktion har importerats. Den lagras under etiktten <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> där du kan inspektera den närmare.',
    'result_many_transactions'        => 'Firefly III har importerat :count transaktioner. De lagras under etiketten <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> där de kan inspekteras närmare.',

    // general errors and warnings:
    'bad_job_status'                  => 'För åtkomst till denna sida, kan importjobbet inte ha status ":status".',

    // error message
    'duplicate_row'                   => 'Rad #:row (":description") kunde inte importeras. Finns redan.',

];

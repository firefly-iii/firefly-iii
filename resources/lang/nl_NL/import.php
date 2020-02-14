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
    'prerequisites_breadcrumb_bunq'       => 'Vereisten voor bunq',
    'prerequisites_breadcrumb_ynab'       => 'Vereisten voor YNAB',
    'job_configuration_breadcrumb'        => 'Instellingen voor ":key"',
    'job_status_breadcrumb'               => 'Importstatus voor ":key"',
    'disabled_for_demo_user'              => 'uitgeschakeld in demo',

    // index page:
    'general_index_intro'                 => 'Dit is de import-routine van Firefly III. Er zijn verschillende manieren om gegevens te importeren in Firefly III, hier als knoppen weergegeven.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'De manier waarop Firefly III je data laat importeren gaat veranderen. Je kan lezen in <a href="https://www.patreon.com/posts/future-updates-30012174">deze Patreon-post</a> dat de CSV import-tool gaat verhuizen naar een zelfstandige repository en tool. Je kan deze alvast beta-testen als je <a href="https://github.com/firefly-iii/csv-importer">deze GitHub repository</a> bezoekt. Als je dat zou willen doen, heel graag, en laat me weten of het lukt.',

    // import provider strings (index):
    'button_fake'                         => 'Nepdata importeren',
    'button_file'                         => 'Importeer een bestand',
    'button_bunq'                         => 'Importeer uit bunq',
    'button_spectre'                      => 'Importeer via Spectre',
    'button_plaid'                        => 'Importeer via Plaid',
    'button_yodlee'                       => 'Importeer via Spectre',
    'button_quovo'                        => 'Importeer via Quovo',
    'button_ynab'                         => 'Importeren van "You Need A Budget"',
    'button_fints'                        => 'Importeer via FinTS',


    // prerequisites box (index)
    'need_prereq_title'                   => 'Importvereisten',
    'need_prereq_intro'                   => 'Sommige importmethoden hebben je aandacht nodig voor ze gebruikt kunnen worden. Ze vereisen bijvoorbeeld speciale API-sleutels of geheime waardes. Je kan ze hier instellen. Het icoontje geeft aan of deze vereisten al ingevuld zijn.',
    'do_prereq_fake'                      => 'Vereisten voor de nep-importhulp',
    'do_prereq_file'                      => 'Vereisten voor het importeren van bestanden',
    'do_prereq_bunq'                      => 'Vereisten voor een import van bunq',
    'do_prereq_spectre'                   => 'Vereisten voor een import via Spectre',
    'do_prereq_plaid'                     => 'Vereisten voor een import via Plaid',
    'do_prereq_yodlee'                    => 'Vereisten voor een import via Yodlee',
    'do_prereq_quovo'                     => 'Vereisten voor een import via Quovo',
    'do_prereq_ynab'                      => 'Vereisten voor imports van YNAB',

    // prerequisites:
    'prereq_fake_title'                   => 'Instellingen voor importeren uit de nep-importhulp',
    'prereq_fake_text'                    => 'Deze nep-provider heeft een neppe API key nodig. Deze moet 32 tekens lang zijn. Je mag deze gebruiken: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Vereisten voor een import via Spectre',
    'prereq_spectre_text'                 => 'Als je gegevens wilt importeren via de Spectre API (v4), moet je een aantal geheime codes bezitten. Ze zijn te vinden op <a href="https://www.saltedge.com/clients/profile/secrets">de secrets pagina</a>.',
    'prereq_spectre_pub'                  => 'De Spectre API moet de publieke sleutel kennen die je hieronder ziet. Zonder deze sleutel herkent Spectre je niet. Voer deze publieke sleutel in op je <a href="https://www.saltedge.com/clients/profile/secrets">secrets-pagina</a>.',
    'prereq_bunq_title'                   => 'Vereisten aan een import van bunq',
    'prereq_bunq_text'                    => 'Om te importeren vanaf bunq moet je een API key hebben. Deze kan je aanvragen in de app. Denk er aan dat deze functie in BETA is. De code is alleen getest op de sandbox API.',
    'prereq_bunq_ip'                      => 'bunq wilt graag je externe IP-adres weten. Firefly III heeft geprobeerd dit in te vullen met behulp van de <a href="https://www.ipify.org/">ipify-dienst</a>. Zorg dat je zeker weet dat dit IP-adres klopt, want anders zal de import niet werken.',
    'prereq_ynab_title'                   => 'Vereisten voor een import van YNAB',
    'prereq_ynab_text'                    => 'Om transacties te kunnen downloaden van YNAB moet je een nieuwe applicatie maken in je <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> en vervolgens hieronder de Client ID en secret opgeven.',
    'prereq_ynab_redirect'                => 'Om de configuratie af te maken voer je de volgende URL in op de <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> onder het kopje "Redirect URI(s)".',
    'callback_not_tls'                    => 'Firefly III heeft de volgende URI gedetecteerd. Het lijkt er op dat dit geen TLS-verbinding is (https). YNAB zal dit niet accepteren. Je mag doorgaan met de import (Firefly III kan er naast zitten), maar dan weet je het.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Nep API-sleutel is opgeslagen!',
    'prerequisites_saved_for_spectre'     => 'APP ID en secret opgeslagen!',
    'prerequisites_saved_for_bunq'        => 'API-sleutel en IP opgeslagen!',
    'prerequisites_saved_for_ynab'        => 'YNAB Client ID en secret opgeslagen!',

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
    'job_config_bunq_accounts_title'      => 'bunq rekeningen',
    'job_config_bunq_accounts_text'       => 'Deze rekeningen zijn geassocieerd met je bunq-account. Kies de rekeningen waar je van wilt importeren, en geef aan waar de gegevens geïmporteerd moeten worden.',
    'bunq_no_mapping'                     => 'Je hebt geen rekeningen geselecteerd om uit te importeren.',
    'should_download_config'              => 'Download <a href=":route">het configuratiebestand</a> voor deze import. Dit maakt toekomstige imports veel eenvoudiger.',
    'share_config_file'                   => 'Als je gegevens hebt geimporteerd van een gewone bank, <a href="https://github.com/firefly-iii/import-configurations/wiki">deel dan je configuratiebestand</a> zodat het makkelijk is voor andere gebruikers om hun gegevens te importeren. Als je je bestand deelt deel je natuurlijk géén privé-gegevens.',
    'job_config_bunq_apply_rules'         => 'Regels toepassen',
    'job_config_bunq_apply_rules_text'    => 'Standaard worden je regels toegepast op de transacties die je tijdens deze routine importeert. Als je wilt dat dat niet gebeurt, zet dan het vinkje uit.',
    'bunq_savings_goal'                   => 'Spaardoel: :amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => 'Gesloten bunqrekening',

    'ynab_account_closed'                  => 'Rekening is gesloten!',
    'ynab_account_deleted'                 => 'Rekening is verwijderd!',
    'ynab_account_type_savings'            => 'spaarrekening',
    'ynab_account_type_checking'           => 'betaalrekening',
    'ynab_account_type_cash'               => 'cash-rekening',
    'ynab_account_type_creditCard'         => 'creditcard',
    'ynab_account_type_lineOfCredit'       => '(doorlopend) krediet',
    'ynab_account_type_otherAsset'         => 'andere betaalrekening',
    'ynab_account_type_otherLiability'     => 'andere passiva',
    'ynab_account_type_payPal'             => 'Paypal',
    'ynab_account_type_merchantAccount'    => 'zakelijke rekening',
    'ynab_account_type_investmentAccount'  => 'beleggingsrekening',
    'ynab_account_type_mortgage'           => 'hypotheek',
    'ynab_do_not_import'                   => '(niet importeren)',
    'job_config_ynab_apply_rules'          => 'Regels toepassen',
    'job_config_ynab_apply_rules_text'     => 'Standaard worden je regels toegepast op de transacties die je tijdens deze routine importeert. Als je wilt dat dat niet gebeurt, zet dan het vinkje uit.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Selecteer je budget',
    'job_config_ynab_select_budgets_text'  => 'Je hebt :count budgetten bij YNAB. Kies degene waar Firefly III van moet importeren.',
    'job_config_ynab_no_budgets'           => 'Er zijn een budgets om van te importeren.',
    'ynab_no_mapping'                      => 'Het lijkt er op dat je geen rekeningen hebt geselecteerd om van te importeren.',
    'job_config_ynab_bad_currency'         => 'Je kan van de volgende budget(ten) niets importeren, omdat je geen rekeningen hebt met deze valuta.',
    'job_config_ynab_accounts_title'       => 'Selecteer rekeningen',
    'job_config_ynab_accounts_text'        => 'Je hebt de volgende rekeningen in dit budget. Selecteer de rekeningen waarvan je wilt importeren en waarvan de transacties moeten worden opgeslagen.',


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

    //job configuration for finTS
    'fints_connection_failed'              => 'Er is een fout opgetreden tijdens het verbinden met je bank. Zorg ervoor dat de ingevoerde gegevens kloppen. Oorspronkelijke foutbericht: :originalError',

    'job_config_fints_url_help'       => 'Bijvoorbeeld https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => 'Dit is meestal je rekeningnummer.',
    'job_config_fints_port_help'      => 'Standaardpoort is 443.',
    'job_config_fints_account_help'   => 'Kies de rekening die je wilt importeren.',
    'job_config_local_account_help'   => 'Kies de Firefly III betaalrekening die correspondeert met de gekozen rekening.',
    // specifics:
    'specific_ing_name'               => 'ING NL',
    'specific_ing_descr'              => 'Maak betere beschrijvingen in de export van ING',
    'specific_sns_name'               => 'SNS / Volksbank NL',
    'specific_sns_descr'              => 'Trim citaten uit exportbestanden van SNS / Volksbank',
    'specific_abn_name'               => 'ABN AMRO NL',
    'specific_abn_descr'              => 'Lost mogelijke problemen op met ABN AMRO-bestanden',
    'specific_rabo_name'              => 'Rabobank NL',
    'specific_rabo_descr'             => 'Lost mogelijke problemen op met Rabobank txt-bestanden',
    'specific_pres_name'              => 'President\'s Choice Financial CA',
    'specific_pres_descr'             => 'Lost mogelijke problemen op met PC bestanden',
    'specific_belfius_name'           => 'Belfius BE',
    'specific_belfius_descr'          => 'Lost mogelijke problemen op met Belfius-bestanden',
    'specific_ingbelgium_name'        => 'ING BE',
    'specific_ingbelgium_descr'       => 'Lost mogelijke problemen op met ING België bestanden',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'          => 'Importinstellingen (3/4) - rol van elke kolom definiëren',
    'job_config_roles_text'           => 'Elke kolom in je CSV-bestand bevat bepaalde gegevens. Geef hier aan wat voor soort gegevens de import-routine kan verwachten. De optie "maak een link" betekent dat je elke vermelding in die kolom linkt aan een waarde uit je database. Een vaak gelinkte kolom is die met de IBAN-code van de tegenrekening. Die kan je dan linken aan de IBAN in jouw database.',
    'job_config_roles_submit'         => 'Volgende',
    'job_config_roles_column_name'    => 'Kolomnaam',
    'job_config_roles_column_example' => 'Voorbeeldgegevens',
    'job_config_roles_column_role'    => 'Kolomrol',
    'job_config_roles_do_map_value'   => 'Maak een link',
    'job_config_roles_no_example'     => 'Geen voorbeeldgegevens',
    'job_config_roles_fa_warning'     => 'Als je een kolom markeert als "vreemde valuta" moet je ook aangeven in welke kolom de valuta staat.',
    'job_config_roles_rwarning'       => 'Geef minstens de kolom aan waar het bedrag in staat. Als het even kan, ook een kolom voor de omschrijving, datum en de andere rekening.',
    'job_config_roles_colum_count'    => 'Kolom',
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

    // column roles for CSV import:
    'column__ignore'                  => '(negeer deze kolom)',
    'column_account-iban'             => 'Betaalrekening (IBAN)',
    'column_account-id'               => 'Betaalrekening (ID gelijk aan FF3)',
    'column_account-name'             => 'Betaalrekeningnaam',
    'column_account-bic'              => 'Betaalrekening (BIC)',
    'column_amount'                   => 'Bedrag',
    'column_amount_foreign'           => 'Bedrag (in vreemde valuta)',
    'column_amount_debit'             => 'Bedrag (debetkolom)',
    'column_amount_credit'            => 'Bedrag (creditkolom)',
    'column_amount_negated'           => 'Bedrag (omgekeerd)',
    'column_amount-comma-separated'   => 'Bedrag (komma as decimaalscheidingsteken)',
    'column_bill-id'                  => 'Contract (ID gelijk aan FF3)',
    'column_bill-name'                => 'Contractnaam',
    'column_budget-id'                => 'Budget (ID gelijk aan FF3)',
    'column_budget-name'              => 'Budgetnaam',
    'column_category-id'              => 'Categorie (ID gelijk aan FF3)',
    'column_category-name'            => 'Categorienaam',
    'column_currency-code'            => 'Valutacode (ISO 4217)',
    'column_foreign-currency-code'    => 'Vreemde valutacode (ISO 4217)',
    'column_currency-id'              => 'Valuta (ID gelijk aan FF3)',
    'column_currency-name'            => 'Valutanaam (gelijk aan FF3)',
    'column_currency-symbol'          => 'Valutasymbool',
    'column_date-interest'            => 'Datum (renteberekening)',
    'column_date-book'                => 'Datum (boeking)',
    'column_date-process'             => 'Datum (verwerking)',
    'column_date-transaction'         => 'Datum',
    'column_date-due'                 => 'Verstrijkingsdatum',
    'column_date-payment'             => 'Datum (betaling)',
    'column_date-invoice'             => 'Datum (factuur)',
    'column_description'              => 'Omschrijving',
    'column_opposing-iban'            => 'Tegenrekening (IBAN)',
    'column_opposing-bic'             => 'BIC van tegenrekeningbank',
    'column_opposing-id'              => 'Tegenrekening (ID gelijk aan FF3)',
    'column_external-id'              => 'Externe ID',
    'column_opposing-name'            => 'Tegenrekeningnaam',
    'column_rabo-debit-credit'        => 'Rabobankspecifiek bij/af indicator',
    'column_ing-debit-credit'         => 'ING-specifieke bij/af indicator',
    'column_generic-debit-credit'     => 'Generieke bank debet/credit indicator',
    'column_sepa_ct_id'               => 'SEPA end-to-end identificatie',
    'column_sepa_ct_op'               => 'SEPA tegenrekening identificatie',
    'column_sepa_db'                  => 'SEPA mandaatidentificatie',
    'column_sepa_cc'                  => 'SEPA vrijwaringscode',
    'column_sepa_ci'                  => 'SEPA crediteuridentificatie',
    'column_sepa_ep'                  => 'SEPA transactiedoeleinde',
    'column_sepa_country'             => 'SEPA landcode',
    'column_sepa_batch_id'            => 'SEPA batchnummer',
    'column_tags-comma'               => 'Tags (kommagescheiden)',
    'column_tags-space'               => 'Tags (spatiegescheiden)',
    'column_account-number'           => 'Betaalrekening (rekeningnummer)',
    'column_opposing-number'          => 'Tegenrekening (rekeningnummer)',
    'column_note'                     => 'Opmerking(en)',
    'column_internal-reference'       => 'Interne referentie',

    // error message
    'duplicate_row'                   => 'Rij #:row (":description) kan niet worden geïmporteerd. Deze bestaat al.',

];

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
    'index_breadcrumb'                    => 'Adatok importálása a Firefly III-ba',
    'prerequisites_breadcrumb_fake'       => 'Előfeltételek az imitált import szolgáltató részére',
    'prerequisites_breadcrumb_spectre'    => 'Spectre előfeltételei',
    'prerequisites_breadcrumb_bunq'       => 'bunq előfeltételei',
    'prerequisites_breadcrumb_ynab'       => 'YNAB bunq előfeltételei',
    'job_configuration_breadcrumb'        => 'Konfiguráció ":key"',
    'job_status_breadcrumb'               => 'Importálás állapota: ":key"',
    'disabled_for_demo_user'              => 'nem érhető el bemutató módban',

    // index page:
    'general_index_intro'                 => 'Üdvözli a Firefly III importáló eljárása. A Firefly III-ba adatokat több módon is lehet importálni, melyek gombként jelennek meg.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'As outlined in <a href="https://www.patreon.com/posts/future-updates-30012174">this Patreon post</a>, the way Firefly III manages importing data is going to change. That means that the CSV importer will be moved to a new, separate tool. You can already beta-test this tool if you visit <a href="https://github.com/firefly-iii/csv-importer">this GitHub repository</a>. I would appreciate it if you would test the new importer and let me know what you think.',

    // import provider strings (index):
    'button_fake'                         => 'Importálás imitálása',
    'button_file'                         => 'Fájl importálása',
    'button_bunq'                         => 'Import bunq-ból',
    'button_spectre'                      => 'Importálás Spectre használatával',
    'button_plaid'                        => 'Importálás Plaid használatával',
    'button_yodlee'                       => 'Importálás Yodlee használatával',
    'button_quovo'                        => 'Importálás Quovo használatával',
    'button_ynab'                         => 'Importálás You Need A Budget-ből',
    'button_fints'                        => 'Importálás FinTS használatával',


    // prerequisites box (index)
    'need_prereq_title'                   => 'Importálás előfeltételei',
    'need_prereq_intro'                   => 'Néhány importálási mód felhasználói beavatkozást igényel a használata előtt. Például szükség lehet különleges API kulcsokra vagy titkos kódokra. Ezeket itt lehet beállítani. Az ikon jelzi, hogy teljesültek-e ezek az előfeltételek.',
    'do_prereq_fake'                      => 'Az imitálás szolgáltató előfeltételei',
    'do_prereq_file'                      => 'Fájl import előfeltételei',
    'do_prereq_bunq'                      => 'Importálás bunqból előfeltételei',
    'do_prereq_spectre'                   => 'A Spectre használatával történő importálás előfeltételei',
    'do_prereq_plaid'                     => 'A Plaid használatával történő importálás előfeltételei',
    'do_prereq_yodlee'                    => 'A Yodlee használatával történő importálás előfeltételei',
    'do_prereq_quovo'                     => 'A Quovo használatával történő importálás előfeltételei',
    'do_prereq_ynab'                      => 'Az YNAB használatával történő importálás előfeltételei',

    // prerequisites:
    'prereq_fake_title'                   => 'Importálás előfeltételei az imitált import szolgáltatótól',
    'prereq_fake_text'                    => 'Az imitált szolgáltatónak szüksége van egy hamis API kulcsra. Ennek 32 karakter hosszúnak kell lennie. Például lehet ez: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'A Spectre API használatával történő importálás előfeltételei',
    'prereq_spectre_text'                 => 'A Spectre API (v4) használatával történő importáláshoz két titkos értéket kell megadni a Firefly III-nak. Ezek a <a href="https://www.saltedge.com/clients/profile/secrets">titkos kódok oldalon</a> találhatóak.',
    'prereq_spectre_pub'                  => 'A Spectre API-nak a lenti nyilvános kulcsra is szüksége van. Enélkül nem fog felismerni téged. A nyilvános kulcsot a <a href="https://www.saltedge.com/clients/profile/secrets">titkos kódok oldalon</a> kell megadni.',
    'prereq_bunq_title'                   => 'Az bunq használatával történő importálás előfeltételei',
    'prereq_bunq_text'                    => 'A bunqból történő importáláshoz be kell szerezni egy API kulcsot. Ezt az alkalmazással lehet megtenni. Fontos tudni, hogy a bunq import funkció BETA állapotú. Csaka korlátozott API-val lett tesztelve.',
    'prereq_bunq_ip'                      => 'A bunqnak szüksége van a kívülről látható IP címre. A Firefly III megpróbálja kitölteni <a href="https://www.ipify.org/">az ipify szolgáltatás</a> használatával. Ellenőrizni kell, hogy jó-e az IP cím vagy különben az importálás nem fog sikerülni.',
    'prereq_ynab_title'                   => 'Az YNAB használatával történő importálás előfeltételei',
    'prereq_ynab_text'                    => 'Ahhoz, hogy a YNAB-ből tranzakciókat lehessen letölteni egy új alkalmazást kell létrehozni a <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> oldalon, majd az ügyfél azonosítót és a titkos kódot fel kell venni ezen az oldalon.',
    'prereq_ynab_redirect'                => 'A beállítás befejezéséhez meg kell adni a következő URL-t a <a href="https://app.youneedabudget.com/settings/developer">Developer Settings Page</a> oldalon a "Redirect URI(s)" alatt.',
    'callback_not_tls'                    => 'Firefly III has detected the following callback URI. It seems your server is not set up to accept TLS-connections (https). YNAB will not accept this URI. You may continue with the import (because Firefly III could be wrong) but please keep this in mind.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Hamis API kulcs sikeres eltárolva!',
    'prerequisites_saved_for_spectre'     => 'Alkalmazás azonosító és titkos kód eltárolva!',
    'prerequisites_saved_for_bunq'        => 'API kulcs és IP eltárolva!',
    'prerequisites_saved_for_ynab'        => 'YNAB kliens azonosító és titkos kód eltárolva!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Feladat beállítása - szabályok alkalmazása?',
    'job_config_apply_rules_text'         => 'Ha már fut az imitált szolgáltató, a szabályok alkalmazhatóak lesznek a tranzakciókon. Ez megnöveli az importálás idejét.',
    'job_config_input'                    => 'A bemenet',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Album nevének megadása',
    'job_config_fake_artist_text'         => 'Több import rutinnál el kell végezni néhány beállítást. Az imitált import szolgáltató használatakor néhány furcsa kérdésre kell válaszolni. Ebben az esetben a folytatáshoz ezt kell beírni: David Bowie.',
    'job_config_fake_song_title'          => 'Dal nevének megadása',
    'job_config_fake_song_text'           => 'A "Golden years" dallal lehet folytatni az imitált importot.',
    'job_config_fake_album_title'         => 'Album nevének megadása',
    'job_config_fake_album_text'          => 'Több import rutin számára a folyamat közben további adatokat kell megadni. Az imitált import szolgáltató használatakor néhány furcsa kérdésre kell válaszolni. Ebben az esetben a folytatáshoz ezt kell beírni: Station to station.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Importálás beállítása (1/4) - Saját fájl feltöltése',
    'job_config_file_upload_text'         => 'Ez a rutin segítséget nyújt fájlok importálásához a bankból a Firefly III-ba. ',
    'job_config_file_upload_help'         => 'Fájl kiválasztása. A fájlnak UTF-8 kódolásúnak kell lennie.',
    'job_config_file_upload_config_help'  => 'Ha korábban már történt adatimportálás a Firefly III-ba, akkor rendelkezésre áll egy előre beállított értékeket tartalmazó beállítási fájl. Néhány bank esetében más felhasználók nyilvánossá tették a saját <a href="https://github.com/firefly-iii/import-configurations/wiki">beállítási fájljukat</a>',
    'job_config_file_upload_type_help'    => 'A feltölteni kívánt fájl típusának kiválasztása',
    'job_config_file_upload_submit'       => 'Fájlok feltöltése',
    'import_file_type_csv'                => 'CSV (comma separated values - vesszővel elválasztott értékek)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'A feltöltött fájl nem UTF-8 vagy ASCII kódolású. A Firefly III nem tudja kezelni az ilyen fájlokat. A Notepad++ vagy a Sublime segítségével lehet a fájlt UTF-8-ra átkódolni.',
    'job_config_uc_title'                 => 'Importálás beállítása (2/4) - Alapvető fájl beállítások',
    'job_config_uc_text'                  => 'A fájl megfelelő importálásához ellenőrizni kell a lenti beállításokat.',
    'job_config_uc_header_help'           => 'Be kell jelölni, ha a CSV fájl első sora oszlopcímeket tartalmaz.',
    'job_config_uc_date_help'             => 'Dátumformátum a fájlban. Az <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">ezen az oldalon</a> bemutatott formátumot kell követnie. Az alapértelmezett érték az ilyen dátumokat fogja feldolgozni: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Ki kell választani a bemeneti fájlban használt mezőelválasztót. Ha nem biztos, hogy melyik, akkor a vessző a legbiztonságosabb választás.',
    'job_config_uc_account_help'          => 'Ha a fájl NEM tartalmaz információt az eszközszámlákról, akkor ebből a listából lehet kiválasztani, hogy a fájlban szereplő tranzakciók melyik bankszámlához tartoznak.',
    'job_config_uc_apply_rules_title'     => 'Szabályok alkalmazása',
    'job_config_uc_apply_rules_text'      => 'A szabályok alkalmazása az összes importált tranzakción. Ez jelentősen lelassítja az importálást.',
    'job_config_uc_specifics_title'       => 'Bank specifikus beállítások',
    'job_config_uc_specifics_txt'         => 'Néhány bank rosszul formázott fájlokat biztosít. A Firefly III automatikusan ki tudja ezeket javítani. Ha a te bankod ilyen fájlokat biztosít és nincs itt felsorolva, akkor a GitHubon lehet ezt bejelenteni.',
    'job_config_uc_submit'                => 'Folytatás',
    'invalid_import_account'              => 'Érvénytelen számla lett kiválasztva az importáláshoz.',
    'import_liability_select'             => 'Kötelezettség',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Válassz bejelentkezést',
    'job_config_spectre_login_text'       => 'A Firefly III :count meglévő bejelentkezést találta a Spectre számlához. Melyik legyen az importhoz használva?',
    'spectre_login_status_active'         => 'Aktív',
    'spectre_login_status_inactive'       => 'Inaktív',
    'spectre_login_status_disabled'       => 'Letiltott',
    'spectre_login_new_login'             => 'Bejelentkezés másik bankkal vagy ezen bankok egyikével más hitelesítő adatok megadásával.',
    'job_config_spectre_accounts_title'   => 'Az importáláshoz használt számlák kiválasztása',
    'job_config_spectre_accounts_text'    => '":name" (:country) kiválasztva. Ettől a szolgáltatótól :count számla áll rendelkezésre. Ki kell választani azokat a Firefly III eszközszámlákat amikbe az ezekből a számlákból származó tranzakció tárolni kell. Fontos tudni, hogy az adatok importálásához a Firefly III számlának és ":name"-számlának ugyanazt a pénznemet kell használnia.',
    'spectre_do_not_import'               => '(ne importálja)',
    'spectre_no_mapping'                  => 'Úgy tűnik az importáláshoz nincs számla kiválasztva.',
    'imported_from_account'               => 'Innen importálva: ":account"',
    'spectre_account_with_number'         => 'Bankszámla száma :number',
    'job_config_spectre_apply_rules'      => 'Szabályok alkalmazása',
    'job_config_spectre_apply_rules_text' => 'Alapértelmezés szerint a szabályok alkalmazva lesznek az importálás alatt létrejövő tranzakciókon. Ha erre nincs szükség, ki kell venni a dobozból a jelölést.',

    // job configuration for bunq:
    'job_config_bunq_accounts_title'      => 'bunq számlák',
    'job_config_bunq_accounts_text'       => 'Ezek a bunq fiókhoz tartozó számlák. Ki kell választani, hogy melyik számlából történjen az importálás és hogy melyik számlába legyenek importálva a tranzakciók.',
    'bunq_no_mapping'                     => 'Úgy tűnik egy számla sincs kiválasztva.',
    'should_download_config'              => 'Ehhez a feladathoz érdemes letölteni <a href=":route">a beállítási fájlt</a>. Ez könnyebbé teszi a későbbi importálásokat.',
    'share_config_file'                   => 'Ha nyilvános bankból importáltál adatokat kérlek <a href="https://github.com/firefly-iii/import-configurations/wiki">oszd meg a beállítási fájlodat</a> ami más felhasználók számára megkönnyíti az adataik importálását. A beállítási fájl megosztása nem fedi fel a pénzügyeid részleteit.',
    'job_config_bunq_apply_rules'         => 'Szabályok alkalmazása',
    'job_config_bunq_apply_rules_text'    => 'Alapértelmezés szerint a szabályok alkalmazva lesznek az importálás alatt létrejövő tranzakciókon. Ha erre nincs szükség, ki kell venni a dobozból a jelölést.',
    'bunq_savings_goal'                   => 'Megtakarítási cél :amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => 'Zárolt bunq fiók',

    'ynab_account_closed'                  => 'Fiók lezárva!',
    'ynab_account_deleted'                 => 'Fiók törölve!',
    'ynab_account_type_savings'            => 'megtakarítási számla',
    'ynab_account_type_checking'           => 'fiók ellenőrzése',
    'ynab_account_type_cash'               => 'készpénzszámla',
    'ynab_account_type_creditCard'         => 'bankkártya',
    'ynab_account_type_lineOfCredit'       => 'line of credit',
    'ynab_account_type_otherAsset'         => 'egyéb eszközszámla',
    'ynab_account_type_otherLiability'     => 'egyéb kötelezettségek',
    'ynab_account_type_payPal'             => 'PayPal',
    'ynab_account_type_merchantAccount'    => 'kereskedői számla',
    'ynab_account_type_investmentAccount'  => 'befektetési számla',
    'ynab_account_type_mortgage'           => 'jelzálog',
    'ynab_do_not_import'                   => '(ne importálja)',
    'job_config_ynab_apply_rules'          => 'Szabályok alkalmazása',
    'job_config_ynab_apply_rules_text'     => 'Alapértelmezés szerint a szabályok alkalmazva lesznek az importálás alatt létrejövő tranzakciókon. Ha erre nincs szükség, ki kell venni a dobozból a jelölést.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Költségkeret kiválasztása',
    'job_config_ynab_select_budgets_text'  => ':count költségkeret van tárolva a YNAB-ben. Ki kell választani, hogy a Firefly III melyikből importálja a tranzakciókat.',
    'job_config_ynab_no_budgets'           => 'There are no budgets available to be imported from.',
    'ynab_no_mapping'                      => 'It seems you have not selected any accounts to import from.',
    'job_config_ynab_bad_currency'         => 'Nem lehet importálni a következő költségkeretekből mert nincs olyan számla aminek a pénzneme egyezik ezekkel a költségkeretekkel.',
    'job_config_ynab_accounts_title'       => 'Számlák kiválasztása',
    'job_config_ynab_accounts_text'        => 'A következő számlák állnak rendelkezésre ebben a költségkeretben. Ki kell választani, hogy mely számlák legyenek importálva és, hogy a tranzakciók hol legyenek eltárolva.',


    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Állapot',
    'spectre_extra_key_card_type'          => 'Kártyatípus',
    'spectre_extra_key_account_name'       => 'Számla neve',
    'spectre_extra_key_client_name'        => 'Ügyfél neve',
    'spectre_extra_key_account_number'     => 'Számlaszám',
    'spectre_extra_key_blocked_amount'     => 'Blokkolt mennyiség',
    'spectre_extra_key_available_amount'   => 'Rendelkezésre álló mennyiség',
    'spectre_extra_key_credit_limit'       => 'Hitelkeret',
    'spectre_extra_key_interest_rate'      => 'Kamatláb',
    'spectre_extra_key_expiry_date'        => 'Lejárati dátum',
    'spectre_extra_key_open_date'          => 'Nyitás dátuma',
    'spectre_extra_key_current_time'       => 'Aktuális idő',
    'spectre_extra_key_current_date'       => 'Aktuális dátum',
    'spectre_extra_key_cards'              => 'Kártyák',
    'spectre_extra_key_units'              => 'Egységek',
    'spectre_extra_key_unit_price'         => 'Egységár',
    'spectre_extra_key_transactions_count' => 'Tranzakciók száma',

    //job configuration for finTS
    'fints_connection_failed'              => 'Hiba történt a bankhoz történő kapcsolódás során. Le kell ellenőrizni, hogy minden megadott adat megfelelő. Az eredeti hibaüzenet: :originalError',

    'job_config_fints_url_help'       => 'Pl. https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => 'Több banknál ez a számlaszám.',
    'job_config_fints_port_help'      => 'Az alapértelmezett port 443.',
    'job_config_fints_account_help'   => 'Kérem válaszon egy bankszámlát ahonnan szeretne tranzakciót importálni.',
    'job_config_local_account_help'   => 'Ki kell választani a fentebb megadott bankszámlának megfelelő Firefly III számlát.',
    // specifics:
    'specific_ing_name'               => 'ING NL',
    'specific_ing_descr'              => 'Jobb leírások létrehozása az ING exportálásokban',
    'specific_sns_name'               => 'SNS / Volksbank NL',
    'specific_sns_descr'              => 'Megjegyzések eltávolítása az SNS / Volksbank fájlokból',
    'specific_abn_name'               => 'ABN AMRO NL',
    'specific_abn_descr'              => 'Javítja az ABN AMRO fájlok lehetséges problémáit',
    'specific_rabo_name'              => 'Rabobank NL',
    'specific_rabo_descr'             => 'Javítja a Rabobank fájlok lehetséges problémáit',
    'specific_pres_name'              => 'President\'s Choice Financial CA',
    'specific_pres_descr'             => 'Javítja a PC fájlok lehetséges problémáit',
    'specific_belfius_name'           => 'Belfius BE',
    'specific_belfius_descr'          => 'Javítja a Belfius fájlok lehetséges problémáit',
    'specific_ingbelgium_name'        => 'ING BE',
    'specific_ingbelgium_descr'       => 'Fixes potential problems with ING Belgium files',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'          => 'Importálási beállítás (3/4) - Egyes oszlopok szerepeinek meghatározása',
    'job_config_roles_text'           => 'A CSV fájl minden oszlopa bizonyos adatot tartalmaz. Meg kell adni, hogy az importáló milyen adatokra számíthat. Az adat „hozzárendelése” azt jelenti, hogy az oszlopokban talált adatokat hozzá lehet kötni egy értékhez az adatbázisban. Egy gyakran hozzárendelt oszlop az az oszlop ami az ellenszámla IBAN kódját tartalmazza. Ez mér könnyen megfeleltethető lesz az adatbázisban már szereplő IBAN kódoknak.',
    'job_config_roles_submit'         => 'Folytatás',
    'job_config_roles_column_name'    => 'Oszlop neve',
    'job_config_roles_column_example' => 'Oszlop példaadat',
    'job_config_roles_column_role'    => 'Oszlopadat jelentése',
    'job_config_roles_do_map_value'   => 'Értékek hozzárendelése',
    'job_config_roles_no_example'     => 'Nincs elérhető példaadat',
    'job_config_roles_fa_warning'     => 'If you mark a column as containing an amount in a foreign currency, you must also set the column that contains which currency it is.',
    'job_config_roles_rwarning'       => 'Legalább egy oszlopot összeg oszlopként kell megjelölni. Javasolt továbbá kiválasztani egy oszlopot a leírásnak, a dátumnak és az ellenszámlának.',
    'job_config_roles_colum_count'    => 'Oszlop',
    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Importálás beállítása (4/4) - Adatok összekapcsolása a Firefly III adataival',
    'job_config_map_text'             => 'A következő táblákban a bal oldali érték a feltöltött fájlban található információkat mutatja. A felhasználó feladata az érték összerendelése egy, az adatbázisban már szereplő értékkel ha lehetséges. A Firefly ragaszkodni fog ehhez az összerendeléshez. Ha nincs érték amihez rendelni lehet, vagy nem szükséges az összerendelés akkor nem kell kiválasztani semmit se.',
    'job_config_map_nothing'          => 'Nincs olyan adat a fájlban amit meglévő értékhez lehet rendelni. Folytatás az „Importálás kezdése” gombbal.',
    'job_config_field_value'          => 'Mező értéke',
    'job_config_field_mapped'         => 'Hozzárendelve',
    'map_do_not_map'                  => '(nincs hozzárendelés)',
    'job_config_map_submit'           => 'Importálás elindítása',


    // import status page:
    'import_with_key'                 => 'Importálás \':key\' kulccsal',
    'status_wait_title'               => 'Kis türelmet...',
    'status_wait_text'                => 'Ez a doboz hamarosan eltűnik.',
    'status_running_title'            => 'Az importálás fut',
    'status_job_running'              => 'Kérem várjon, az importálás folyamatban van...',
    'status_job_storing'              => 'Kérem várjon, adatok tárolása...',
    'status_job_rules'                => 'Kérem várjon, szabályok futtatása...',
    'status_fatal_title'              => 'Végzetes hiba',
    'status_fatal_text'               => 'Az import közben hiba történt amit nem sikerült helyreállítani. Elnézést kérünk!',
    'status_fatal_more'               => 'Ezt a (valószínűleg nagyon rejtélyes) hibaüzenetet a merevlemezen, vagy a Firefly III futtatásához használt Docker tárolóban található naplófájlok egészítik ki.',
    'status_finished_title'           => 'Az importálás befejeződött',
    'status_finished_text'            => 'Az importálás befejeződött.',
    'finished_with_errors'            => 'Hibák történtek importálás közben. Alaposan át kell nézni őket.',
    'unknown_import_result'           => 'Ismeretlen import eredmény',
    'result_no_transactions'          => 'No transactions have been imported. Perhaps they were all duplicates is simply no transactions where present to be imported. Perhaps the log files can tell you what happened. If you import data regularly, this is normal.',
    'result_one_transaction'          => 'Pontosan egy tranzakció lett importálva. A <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> címke alatt lett eltárolva ahol később ellenőrizhető.',
    'result_many_transactions'        => 'A Firefly III :count tranzakciót importált. A <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> címke alatt lettek eltárolva ahol később ellenőrizhetőek.',


    // general errors and warnings:
    'bad_job_status'                  => 'Ennek az oldalnak az eléréséhez az import művelet állapota nem lehet ":status".',

    // column roles for CSV import:
    'column__ignore'                  => '(oszlop figyelmen kívül hagyása)',
    'column_account-iban'             => 'Eszközszámla (IBAN)',
    'column_account-id'               => 'Eszközszámla azonosító (FF3-nak megfelelő)',
    'column_account-name'             => 'Eszközszámla (név)',
    'column_account-bic'              => 'Eszközszámla (BIC)',
    'column_amount'                   => 'Összeg',
    'column_amount_foreign'           => 'Összeg (devizában)',
    'column_amount_debit'             => 'Összeg (tartozás oszlop)',
    'column_amount_credit'            => 'Összeg (hitel oszlop)',
    'column_amount_negated'           => 'Összeg (negált oszlop)',
    'column_amount-comma-separated'   => 'Összeg (vessző mint tizedes elválasztó)',
    'column_bill-id'                  => 'Számla azonosító (FF3-nak megfelelő)',
    'column_bill-name'                => 'Számla neve',
    'column_budget-id'                => 'Költségkeret azonosító (FF3-nak megfelelő)',
    'column_budget-name'              => 'Költségkeret neve',
    'column_category-id'              => 'Kategória azonosító (FF3-nak megfelelő)',
    'column_category-name'            => 'Kategória neve',
    'column_currency-code'            => 'Pénznem kód (ISO 4217)',
    'column_foreign-currency-code'    => 'Deviza kód (ISO 4217)',
    'column_currency-id'              => 'Pénznem azonosító (FF3-nak megfelelő)',
    'column_currency-name'            => 'Pénznem neve (FF3-nak megfelelő)',
    'column_currency-symbol'          => 'Pénznem szimbóluma (FF3-nak megfelelő)',
    'column_date-interest'            => 'Kamatszámítás dátuma',
    'column_date-book'                => 'Tranzakció könyvelési dátuma',
    'column_date-process'             => 'Tranzakció feldolgozási dátuma',
    'column_date-transaction'         => 'Dátum',
    'column_date-due'                 => 'Tranzakció esedékessége',
    'column_date-payment'             => 'Tranzakció fizetési dátuma',
    'column_date-invoice'             => 'Tranzakció számla dátuma',
    'column_description'              => 'Leírás',
    'column_opposing-iban'            => 'Ellenszámla (IBAN)',
    'column_opposing-bic'             => 'Ellenszámla (BIC)',
    'column_opposing-id'              => 'Ellenszámla azonosító (FF3-nak megfelelő)',
    'column_external-id'              => 'Külső azonosító',
    'column_opposing-name'            => 'Ellenszámla (név)',
    'column_rabo-debit-credit'        => 'Rabobank specifikus tartozás/hitel indikátor',
    'column_ing-debit-credit'         => 'ING specifikus tartozás/hitel indikátor',
    'column_generic-debit-credit'     => 'Általános bank specifikus tartozás/hitel indikátor',
    'column_sepa_ct_id'               => 'SEPA végpontok közti azonosító',
    'column_sepa_ct_op'               => 'SEPA ellenszámla azonosító',
    'column_sepa_db'                  => 'SEPA megbízás azonosító',
    'column_sepa_cc'                  => 'SEPA Clearing Code',
    'column_sepa_ci'                  => 'SEPA hitelező azonosító',
    'column_sepa_ep'                  => 'SEPA External Purpose',
    'column_sepa_country'             => 'SEPA országkód',
    'column_sepa_batch_id'            => 'SEPA Batch ID',
    'column_tags-comma'               => 'Címkék (vesszővel elválasztva)',
    'column_tags-space'               => 'Címkék (szóközzel elválasztva)',
    'column_account-number'           => 'Eszközszámla (számlaszám)',
    'column_opposing-number'          => 'Ellenszámla (számlaszám)',
    'column_note'                     => 'Megjegyzések',
    'column_internal-reference'       => 'Belső hivatkozás',

    // error message
    'duplicate_row'                   => 'Row #:row (":description") could not be imported. It already exists.',

];

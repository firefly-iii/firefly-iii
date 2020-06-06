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
    'index_breadcrumb'                    => 'Tuo tietoja Firefly III:een',
    'prerequisites_breadcrumb_fake'       => 'Edellytykset harjoittelutuonnille',
    'prerequisites_breadcrumb_spectre'    => 'Spectren käytön edellytykset',
    'job_configuration_breadcrumb'        => 'Avaimen ":key" asetukset',
    'job_status_breadcrumb'               => 'Avaimen ":key" tuonnin tila',
    'disabled_for_demo_user'              => 'ei käytössä esittelytilassa',

    // index page:
    'general_index_intro'                 => 'Tervetuloa Firefly III:n tuontirutiiniin. Tietoja voidaan tuoda Firefly III:een monella eri tavalla, jotka näytetään tässä painikkeina.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'Kuten <a href="https://www.patreon.com/posts/future-updates-30012174">tässä Patreon-artikkelissa</a> esitetään, Firefly III:n tuontitietojen hallintatapa muuttuu. Tämä tarkoittaa, että CSV-tuoja siirretään uuteen, erilliseen työkaluun. Tätä toimintoa voi jo testata vierailemalla <a href="https://github.com/firefly-iii/csv-importer">tässä GitHub-arkistossa</a>. Olisin kiitollinen, jos testaisit uutta työkalua ja kertoisit mielipiteesi siitä.',
    'final_csv_import'     => 'Kuten <a href="https://www.patreon.com/posts/future-updates-30012174"> tässä Patreon-viestissä </a> kuvataan, Firefly III:n tietojen tuonnin hallintatapa muuttuu. Tämä tarkoittaa, että tämä on viimeinen versio Firefly III:sta, jossa on CSV-tuontityökalu. Sen sijaan saatavana on erillinen työkalu, jota sinun tulisi kokeilla itse: <a href="https://github.com/firefly-iii/csv-importer"> Firefly III CSV -tuontityökalu </a>. Olisin kiitollinen, jos testaisit uuden työkalun ja kerrot minulle mielipiteesi.',

    // import provider strings (index):
    'button_fake'                         => 'Tee harjoittelutuonti',
    'button_file'                         => 'Tuo tiedosto',
    'button_spectre'                      => 'Tuonti Spectren avulla',

    // prerequisites box (index)
    'need_prereq_title'                   => 'Tuonnin edellytykset',
    'need_prereq_intro'                   => 'Jotkut tuontitavat tarvitsevat huomiotasi ennen kuin niitä voidaan käyttää. Ne voivat esimerkiksi vaatia erityisiä API-avaimia tai sovelluksen salaisuuksia. Voit määrittää ne täällä. Kuvake osoittaa, täyttyvätkö nämä edellytykset.',
    'do_prereq_fake'                      => 'Edellytykset harjoittelutuonnille',
    'do_prereq_file'                      => 'Tiedostojen tuonnin edellytykset',
    'do_prereq_spectre'                   => 'Edellytykset tuonnille Spectrestä',

    // prerequisites:
    'prereq_fake_title'                   => 'Edellytykset tuonnille leikkipankista',
    'prereq_fake_text'                    => 'Tämä leikkipankki tarvitsee tekaistun API avaimen. Sen pituuden täytyy olla tasan 32 merkkiä. Voit käyttää vaikka tätä: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Edellytykset tuonnille Spectren API:a käyttäen',
    'prereq_spectre_text'                 => 'Tuodaksesi tietoja käyttäen Spectre API:a (v4), sinun on toimitettava Firefly III:lle kaksi salaista arvoa. Ne löytyvät <a href="https://www.saltedge.com/clients/profile/secrets">salaisuussivulta</a>.',
    'prereq_spectre_pub'                  => 'Samoin, Spectre API:n on tiedettävä julkinen avain, jonka näet alla. Ilman sitä se ei tunnista sinua. Anna tämä julkinen avain <a href="https://www.saltedge.com/clients/profile/secrets">salaisuus</a> sivullasi.',
    'callback_not_tls'                    => 'Firefly III on havainnut seuraavan takaisinkutsu-URI:n. Vaikuttaa siltä, ​​että palvelinta ei ole määritetty hyväksymään TLS-yhteyksiä (https). YNAB ei hyväksy tätä URI:a. Voit jatkaa tuontia (koska Firefly III saattaa olla väärässä), mutta pidä tämä mielessä.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Leikki-API-avain tallennettu onnistuneesti!',
    'prerequisites_saved_for_spectre'     => 'Sovellustunnus ja salaisuus tallennettu!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Työnmääritys - Käytetään sääntöjäsi?',
    'job_config_apply_rules_text'         => 'Kun leikkipankin tuonti on tehty, sääntöjäsi voidaan soveltaa tapahtumiin. Tämä pidentää tuontiin kuluvaa aikaa.',
    'job_config_input'                    => 'Syötteesi',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Kirjoita albumin nimi',
    'job_config_fake_artist_text'         => 'Monissa tuontirutiineissa on muutama asetusvaihe, jotka sinun on suoritettava. Leikkipankin tapauksessa sinun on vastattava joihinkin outoihin kysymyksiin. Kirjoita tässä tapauksessa "David Bowie" jatkaaksesi.',
    'job_config_fake_song_title'          => 'Kirjoita kappaleen nimi',
    'job_config_fake_song_text'           => 'Mainitse kappale "Golden years" jatkaaksesi leikkituontia.',
    'job_config_fake_album_title'         => 'Kirjoita albumin nimi',
    'job_config_fake_album_text'          => 'Jotkin tuontirutiinit vaativat lisätietoja tuonnin puolivälissä. Leikkipankin tapauksessa sinun on vain vastattava outoihin kysymyksiin. Kirjoita "Station to station" jatkaaksesi.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Tuonnin asetukset (1/4) - Vie tiedostosi',
    'job_config_file_upload_text'         => 'Tämän rutiinin avulla voit tuoda pankistasi tiedostoja Firefly III:een. ',
    'job_config_file_upload_help'         => 'Valitse tiedosto. Varmista, että tiedosto on UTF-8-koodattu.',
    'job_config_file_upload_config_help'  => 'Jos olet aiemmin tuonut tietoja Firefly III:een, sinulla voi olla asetustiedosto, joka esiasettaa asetusarvot sinulle. Joillekin pankeille, muut käyttäjät ovat ystävällisesti toimittaneet omat <a href="https://github.com/firefly-iii/import-configurations/wiki">asetustiedostonsa</a>',
    'job_config_file_upload_type_help'    => 'Valitse vietävän tiedoston tyyppi',
    'job_config_file_upload_submit'       => 'Lähetä tiedostot',
    'import_file_type_csv'                => 'CSV (pilkkuerotellut arvot)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'Lähettämääsi tiedostoa ei ole koodattu UTF-8- tai ASCII-tiedostona. Firefly III ei osaa käsitellä tällaisia ​​tiedostoja. Muunna tiedoston merkistökoodaus UTF-8:ksi Notepad++:n tai Sublime:n avulla.',
    'job_config_uc_title'                 => 'Tuonnin asetukset (2/4) - Tiedostojen perusasetukset',
    'job_config_uc_text'                  => 'Voidaksesi tuoda tiedostosi oikein, tarkista alla olevat vaihtoehdot.',
    'job_config_uc_header_help'           => 'Valitse tämä valintaruutu, jos CSV-tiedostosi ensimmäisellä rivillä ovat sarakkeiden otsikot.',
    'job_config_uc_date_help'             => 'Päivämäärän ja ajan muotoilu tiedostossa. Seuraa muotoilua <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">tämän sivun</a> esimerkin mukaan. Oletusarvo jäsentää päivämäärät, jotka näyttävät tältä: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Valitse syöttötiedostossa käytetty kenttäerotin. Jos et ole varma, pilkku on turvallisin vaihtoehto.',
    'job_config_uc_account_help'          => 'Jos tiedostosi EI sisällä tietoja omaisuustileistäsi, käytä tätä alasvetovalikkoa valitaksesi mihin tiliin tiedoston tapahtumat kuuluvat.',
    'job_config_uc_apply_rules_title'     => 'Aja säännöt',
    'job_config_uc_apply_rules_text'      => 'Soveltaa sääntöjäsi jokaiseen tuotuun tapahtumaan. Huomaa, että tämä hidastaa tuontia merkittävästi.',
    'job_config_uc_specifics_title'       => 'Pankkikohtaiset vaihtoehdot',
    'job_config_uc_specifics_txt'         => 'Jotkut pankit toimittavat tiedostot huonosti muotoiltuina. Firefly III voi korjata ne automaattisesti. Jos pankkisi toimittaa tällaisia ​​tiedostoja, mutta sitä ei ole lueteltu tässä, avaa kysymys GitHubissa.',
    'job_config_uc_submit'                => 'Jatka',
    'invalid_import_account'              => 'Olet valinnut virheellisen tilin tietojen tuontia varten.',
    'import_liability_select'             => 'Vastuu',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Valitse kirjautumistunnuksesi',
    'job_config_spectre_login_text'       => 'Firefly III on löytänyt :count kirjautumistunnusta Spectre-tililtäsi. Mitä niistä haluat käyttää tietojen tuomiseen?',
    'spectre_login_status_active'         => 'Aktiivinen',
    'spectre_login_status_inactive'       => 'Ei käytössä',
    'spectre_login_status_disabled'       => 'Poistettu käytöstä',
    'spectre_login_new_login'             => 'Kirjaudu toiseen pankkiin, tai johonkin näistä pankeista toisilla tunnuksilla.',
    'job_config_spectre_accounts_title'   => 'Valitse tilit, joilta haluat tuoda tietoja',
    'job_config_spectre_accounts_text'    => 'Olet valinnut ":name" (:country). Sinulla on :count tiliä saatavilla tässä rahalaitoksessa. Valitse Firefly III-omaisuustilit, joihin näiden tilien tapahtumat tulisi tallentaa. Muista, että tietojen tuonnissa sekä Firefly III-tilillä että ":name"-tilillä on oltava sama valuutta.',
    'spectre_do_not_import'               => '(älä tuo)',
    'spectre_no_mapping'                  => 'Vaikuttaa siltä, ​​että et ole valinnut tiliä, josta haluat tuoda tietoja.',
    'imported_from_account'               => 'Tuotu tililtä ":account"',
    'spectre_account_with_number'         => 'Tili :number',
    'job_config_spectre_apply_rules'      => 'Aja säännöt',
    'job_config_spectre_apply_rules_text' => 'Oletuksena sääntöjäsi sovelletaan tapahtumiin, jotka luodaan tämän tuontirutiinin aikana. Jos et halua, että näin tapahtuu, poista valinta tästä valintaruudusta.',

    // job configuration for bunq:
    'should_download_config'              => 'Sinun tulisi ladata tämän työn <a href=":route">määritystiedosto</a>. Tämä helpottaa tietojen tuontia jatkossa.',
    'share_config_file'                   => 'Jos olet tuonut tietoja julkisesta pankista, sinun pitäisi <a href="https://github.com/firefly-iii/import-configurations/wiki">jakaa asetustiedostosi</a> jotta muiden käyttäjien on helppo tuoda tietojaan. Asetustiedoston jakaminen ei paljasta taloudellisia tietojasi.',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Tila',
    'spectre_extra_key_card_type'          => 'Kortin tyyppi',
    'spectre_extra_key_account_name'       => 'Tilin nimi',
    'spectre_extra_key_client_name'        => 'Asiakkaan nimi',
    'spectre_extra_key_account_number'     => 'Tilinumero',
    'spectre_extra_key_blocked_amount'     => 'Varattu summa',
    'spectre_extra_key_available_amount'   => 'Käytettävissä oleva summa',
    'spectre_extra_key_credit_limit'       => 'Luottoraja',
    'spectre_extra_key_interest_rate'      => 'Korkoprosentti',
    'spectre_extra_key_expiry_date'        => 'Voimassaoloaika',
    'spectre_extra_key_open_date'          => 'Avauspäivä',
    'spectre_extra_key_current_time'       => 'Tämänhetkinen aika',
    'spectre_extra_key_current_date'       => 'Nykyinen päivämäärä',
    'spectre_extra_key_cards'              => 'Kortit',
    'spectre_extra_key_units'              => 'Yksiköt',
    'spectre_extra_key_unit_price'         => 'Yksikköhinta',
    'spectre_extra_key_transactions_count' => 'Maksutapahtumien lukumäärä',

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Tuonnin asetukset (4/4) - Yhdistä tuontitiedot Firefly III -tietoihin',
    'job_config_map_text'             => 'Seuraavissa taulukoissa vasen arvo näyttää ladatusta tiedostostasi löytyvät tiedot. Sinun tehtäväsi on kartoittaa tämä arvo, jos mahdollista, arvoon, joka on jo tietokannassasi. Firefly pysyy tässä kartoituksessa. Jos määritettävää arvoa ei ole tai et halua kartoittaa tiettyä arvoa, älä valitse mitään.',
    'job_config_map_nothing'          => 'Tiedostossasi ei ole tietoja joita voisit kartoittaa olemassa oleviin arvoihin. Paina "Aloita tuonti" jatkaaksesi.',
    'job_config_field_value'          => 'Kentän arvo',
    'job_config_field_mapped'         => 'Kohdistettu',
    'map_do_not_map'                  => '(älä kohdista)',
    'job_config_map_submit'           => 'Aloita tuonti',


    // import status page:
    'import_with_key'                 => 'Tuo näppäimellä \':key\'',
    'status_wait_title'               => 'Ole hyvä ja odota',
    'status_wait_text'                => 'Tämä ruutu katoaa hetken kuluttua.',
    'status_running_title'            => 'Tuonti on käynnissä',
    'status_job_running'              => 'Odota, tuontia suoritetaan ...',
    'status_job_storing'              => 'Odota, tietoja tallennetaan ...',
    'status_job_rules'                => 'Odota, ajetaan sääntöjä ...',
    'status_fatal_title'              => 'Vakava virhe',
    'status_fatal_text'               => 'Tuonnissa on tapahtunut virhe, josta se ei pystynyt palautumaan. Pahoittelut!',
    'status_fatal_more'               => 'Tätä (mahdollisesti hyvin salaperäistä) virheilmoitusta täydentävät lokitiedostot, jotka löydät kiintolevyltäsi, tai Docker kontista josta ajat Firefly III:a.',
    'status_finished_title'           => 'Tuonti valmis',
    'status_finished_text'            => 'Tuonti on valmistunut.',
    'finished_with_errors'            => 'Tuonnin aikana tapahtui virheitä. Ole hyvä ja tarkista ne huolellisesti.',
    'unknown_import_result'           => 'Tuonnin lopputulos tuntematon',
    'result_no_transactions'          => 'Yhtään tapahtumaa ei ole tuotu. Ehkä ne kaikki olivat kaksoiskappaleita, eikä tapahtumia yksinkertaisesti ollut yhtään tuotavana. Ehkä lokitiedostot voivat kertoa mitä tapahtui. Jos tuot tietoja säännöllisesti, tämä on normaalia.',
    'result_one_transaction'          => 'Tarkalleen yksi tapahtuma on tuotu. Se on tallennettu tägin <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> alle, jossa voit tarkastella sitä tarkemmin.',
    'result_many_transactions'        => 'Firefly III on tuonut :count tapahtumaa. Ne on tallennettu tägin <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> alle, jossa voit tarkastella niitä tarkemmin.',

    // general errors and warnings:
    'bad_job_status'                  => 'Päästäksesi tälle sivulle tuontityösi tila ei voi olla ":status".',

    // error message
    'duplicate_row'                   => 'Riviä #:row (":description") ei voitu tuoda. Se on jo olemassa.',

];

<?php

/**
 * email.php
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
    // common items
    'greeting'                         => 'Hei siellä,',
    'closing'                          => 'Beep boop,',
    'signature'                        => 'Firefly III Postirobotti',
    'footer_ps'                        => 'P.S. Tämä viesti lähetettiin, koska sitä pyydettiin osoitteesta :ipAddress.',

    // admin test
    'admin_test_subject'               => 'Testiviesti Firefly III applikaatioltasi',
    'admin_test_body'                  => 'Tämä on testiviesti Firefly III instanssiltasi. Se lähetettiin osoitteeseen :email.',

    // new IP
    'login_from_new_ip'                => 'Uusi kirjautuminen Firefly III:een',
    'new_ip_body'                      => 'Firefly III havaitsi uuden kirjautumisen tilillesi tuntemattomasta IP-osoitteesta. Jos et ole koskaan kirjautunut alla olevasta IP-osoitteesta tai edellisestä kirjautumisesta on yli kuusi kuukautta, Firefly III varoittaa sinua.',
    'new_ip_warning'                   => 'Jos tunnistat tämän IP-osoitteen tai kirjautumisen, voit ohittaa tämän viestin. Jos et ole kirjautunut, tai jos sinulla ei ole aavistustakaan mistä tässä on kyse, tarkista salasanasi turvallisuus, vaihda se ja kirjaudu ulos kaikista muista istunnoista. Voit tehdä tämän profiilisivullasi. Tietenkin sinulla on jo 2FA käytössä, eikö vain? Pysy turvassa!',
    'ip_address'                       => 'IP-osoite',
    'host_name'                        => 'Palvelin',
    'date_time'                        => 'Päivämäärä + aika',

    // access token created
    'access_token_created_subject'     => 'Uusi käyttöoikeustunnus luotiin',
    'access_token_created_body'        => 'Joku (toivottavasti sinä) loi juuri uuden Firefly III käyttöoikeustunnuksen käyttäjätilillesi.',
    'access_token_created_explanation' => 'Tämän tunnisteen avulla he voivat käyttää <strong>kaikkia</strong> taloudellisia tietojasi Firefly III -rajapinnan kautta.',
    'access_token_created_revoke'      => 'Jos tämä et ollut sinä, peruuta tämä tunnus mahdollisimman pian osoitteessa :url.',

    // registered
    'registered_subject'               => 'Tervetuloa Firefly III:een!',
    'registered_welcome'               => 'Tervetuloa <a style="color:#337ab7" href=":address">Firefly III:een</a>. Rekisteröitymisesi onnistui ja tämä sähköposti lähetettiin sen vahvistamiseksi. Jei!',
    'registered_pw'                    => 'Jos olet jo unohtanut salasanasi, nollaa se käyttäen <a style="color:#337ab7" href=":address/password/reset">salasanan nollaustyökalua</a>.',
    'registered_help'                  => 'Jokaisen sivun oikeassa yläkulmassa on apukuvake. Jos tarvitset apua, napsauta sitä!',
    'registered_doc_html'              => 'Jos et vielä ole ehtinyt, lue <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/personal-finances">suuri teoria</a>.',
    'registered_doc_text'              => 'Jos et ole vielä ole lukenut, lue ensimmäisen käytön opas ja täydellinen kuvaus.',
    'registered_closing'               => 'Nauti!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Salasanan nollaus:',
    'registered_doc_link'              => 'Dokumentaatio:',

    // email change
    'email_change_subject'             => 'Firefly III sähköpostiosoitteesi on muuttunut',
    'email_change_body_to_new'         => 'Joko sinä, tai joku jolla on pääsy Firefly III -tilillesi, on vaihtanut sähköpostiosoitteesi. Jos et odottanut tätä viestiä, ohita ja poista se.',
    'email_change_body_to_old'         => 'Joko Sinä tai joku jolla on pääsy Firefly III -tilillesi on muuttanut sähköpostiosoitettasi. Jos tämä tuli yllätyksenä, sinun <strong>täytyy</strong> seurata alla olevaa "kumoa"-linkkiä suojataksesi tilisi!',
    'email_change_ignore'              => 'Jos teit itse tämän muutoksen, voit turvallisesti ohittaa tämän viestin.',
    'email_change_old'                 => 'Vanha sähköpostiosoite oli: :email',
    'email_change_old_strong'          => 'Vanha sähköpostiosoite oli: <strong>:email</strong>',
    'email_change_new'                 => 'Uusi sähköpostiosoite on: :email',
    'email_change_new_strong'          => 'Uusi sähköpostiosoite on: <strong>:email</strong>',
    'email_change_instructions'        => 'Et voi käyttää Firefly III:a ennen kuin vahvistat tämän muutoksen. Ole hyvä ja seuraa alla olevaa linkkiä.',
    'email_change_undo_link'           => 'Kumoa muutos seuraamalla linkkiä:',

    // OAuth token created
    'oauth_created_subject'            => 'Uusi OAuth-asiakas on luotu',
    'oauth_created_body'               => 'Joku (toivottavasti sinä) loi juuri uuden Firefly III API OAuth asiakkaan käyttäjätilillesi. Sen nimi on ":name" ja callback URL <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'Tämän asiakkaan avulla he voivat käyttää <strong>kaikkia</strong> taloudellisia tietojasi Firefly III -rajapinnan kautta.',
    'oauth_created_undo'               => 'Jos tämä et ollut sinä, peruuta tämä asiakas mahdollisimman pian osoitteessa :url.',

    // reset password
    'reset_pw_subject'                 => 'Salasanasi nollauspyyntö',
    'reset_pw_instructions'            => 'Joku yritti nollata salasanasi. Jos olit sinä, seuraa alla olevaa linkkiä tehdäksesi sen.',
    'reset_pw_warning'                 => '<strong>Varmista</strong>, että linkki menee todella Firefly III:een, johon odotat sen menevän!',

    // error
    'error_subject'                    => 'Firefly III:ssa tapahtui virhe',
    'error_intro'                      => 'Firefly III v:version tapahtui virhe: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'Virhe oli tyyppiä ":class".',
    'error_timestamp'                  => 'Virhe tapahtui kello: :time.',
    'error_location'                   => 'Tämä virhe tapahtui tiedostossa "<span style="font-family: monospace;">:file</span>" rivillä :line koodilla :code.',
    'error_user'                       => 'Virhe tpahtui käyttäjällä #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'Virheen tapahtuessa käyttäjä ei ollut kirjautuneena tai käyttäjää ei havaittu.',
    'error_ip'                         => 'Tähän virheeseen liittyvä IP-osoite on: :ip',
    'error_url'                        => 'URL on: :url',
    'error_user_agent'                 => 'Käyttäjä-agentti: :userAgent',
    'error_stacktrace'                 => 'Täydellinen stack trace on alla. Jos tämä on bugi Firefly III:ssa, voit lähettää tämän viestin osoitteeseen <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-ii. rg</a>. Tämä voi auttaa korjaamaan juuri kohtaamasi virheen.',
    'error_github_html'                => 'Jos haluat, voit myös avata uuden tiketin <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHubissa</a>.',
    'error_github_text'                => 'Jos haluat, voit myös avata uuden tiketin osoitteessa https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'Täydellinen stack trace:',

    // report new journals
    'new_journals_subject'             => 'Firefly III on luonut uuden tapahtuman|Firefly III on luonut :count uutta tapahtumaa',
    'new_journals_header'              => 'Firefly III on luonut tapahtuman sinulle. Löydät sen Firefly III -asennuksestasi:|Firefly III on luonut sinulle :count tapahtumaa. Löydät ne Firefly III -asennuksestasi:',
];

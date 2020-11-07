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
    'greeting'                         => 'Hoi,',
    'closing'                          => 'Bliep bloep,',
    'signature'                        => 'De Firefly III e-mailrobot',
    'footer_ps'                        => 'PS: dit bericht krijg je dankzij een actie vanaf :ipAddress.',

    // admin test
    'admin_test_subject'               => 'Een testbericht van je Firefly III-installatie',
    'admin_test_body'                  => 'Dit is een testbericht vanaf jouw Firefly III-installatie. Het is verstuurd naar :email.',

    // new IP
    'login_from_new_ip'                => 'Nieuwe login op Firefly III',
    'new_ip_body'                      => 'Firefly III heeft een nieuwe login op je account gedetecteerd van een onbekend IP-adres. Je krijgt deze waarschuwing omdat je nooit hebt ingelogd vanaf het onderstaande IP-adres, of dat was meer dan zes maanden geleden.',
    'new_ip_warning'                   => 'Je mag dit bericht negeren als je het IP adres herkent. Als je niet hebt ingelogd of je hebt geen flauw idee waar dit over gaat, zorg dan dat je je wachtwoord verandert en al je andere sessies uitlogt. Dit kan op je profielpagina. Je hebt 2FA al aanstaan toch? Stay safe!',
    'ip_address'                       => 'IP adres',
    'host_name'                        => 'Host',
    'date_time'                        => 'Datum & tijd',

    // access token created
    'access_token_created_subject'     => 'Er is een nieuw access token gegenereerd',
    'access_token_created_body'        => 'Zojuist heeft iemand (hopelijk jij) voor jouw gebruikersaccount een nieuw Firefly III API Access Token gemaakt.',
    'access_token_created_explanation' => 'Met dit token heeft die persoon toegang tot <strong>al je</strong> financiële records via de Firefly III API.',
    'access_token_created_revoke'      => 'Als jij dit niet was, cancel dit token dan zo snel mogelijk via :url.',

    // registered
    'registered_subject'               => 'Welkom bij Firefly III!',
    'registered_welcome'               => 'Welkom bij <a style="color:#337ab7" href=":address">Firefly III</a>. Deze e-mail bevestigt je registratie. Hoera!',
    'registered_pw'                    => 'Als je nu al je wachtwoord bent vergeten <a style="color:#337ab7" href=":address/password/reset">reset deze dan meteen</a>.',
    'registered_help'                  => 'Er staat een help-icoontje rechtsboven op elke pagina. Gebruik die vooral!',
    'registered_doc_html'              => 'Lees de <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/personal-finances">grand theory</a> als je dat nog niet had gedaan.',
    'registered_doc_text'              => 'Lees de handleiding en de beschrijving van Firefly III als je dat nog niet gedaan had.',
    'registered_closing'               => 'Geniet ervan!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Wachtwoord resetten:',
    'registered_doc_link'              => 'Documentatie:',

    // email change
    'email_change_subject'             => 'Je Firefly III e-mailadres is veranderd',
    'email_change_body_to_new'         => 'Jij of iemand met toegang tot je Firefly III account heeft je e-mailadres gewijzigd. Pleur dit mailtje weg als jij dit niet was.',
    'email_change_body_to_old'         => 'Jij of iemand met toegang tot je Firefly III account heeft je e-mailadres gewijzigd. Klik op <strong>de "undo"-link</strong> hieronder als jij dat niet was!',
    'email_change_ignore'              => 'Negeer dit mailtje als jij het was.',
    'email_change_old'                 => 'Het oude e-mailadres was: :email',
    'email_change_old_strong'          => 'Het oude e-mailadres was: <strong>:email</strong>',
    'email_change_new'                 => 'Het nieuwe e-mailadres is: :email',
    'email_change_new_strong'          => 'Het nieuwe e-mailadres is: <strong>:email</strong>',
    'email_change_instructions'        => 'Firefly III doet het niet tot je de verandering bevestigt. Volg de link hieronder om dat te doen.',
    'email_change_undo_link'           => 'Maak dit ongedaan door de link te volgen:',

    // OAuth token created
    'oauth_created_subject'            => 'Er is een nieuwe OAuth client aangemaakt',
    'oauth_created_body'               => 'Iemand (hopelijk jij) heeft zojuist een nieuwe Firefly III API OAuth Client gemaakt. Bijbehorende label is ":name" en de callback URL is <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'Met deze client heeft diegene toegang tot <strong>al je</strong> financiële records via de Firefly III API.',
    'oauth_created_undo'               => 'Als jij dit niet was, cancel deze client dan zo snel mogelijk via :url.',

    // reset password
    'reset_pw_subject'                 => 'Verzoek om je wachtwoord te resetten',
    'reset_pw_instructions'            => 'Iemand heeft geprobeerd je wachtwoord te resetten. Volg de link hieronder als jij dat was.',
    'reset_pw_warning'                 => '<strong>CHECK</strong> of deze link ook echt naar jouw Firefly III installatie gaat!',

    // error
    'error_subject'                    => 'Fout opgetreden in Firefly III',
    'error_intro'                      => 'Firefly III v:version liep een fout aan: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'De fout was van type ":class".',
    'error_timestamp'                  => 'De fout is opgetreden op/om: :time.',
    'error_location'                   => 'De fout is opgetreden in bestand "<span style="font-family: monospace;">:file</span>" op regel :line met code :code.',
    'error_user'                       => 'De fout is opgetreden bij gebruiker #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'Er is was gebruiker ingelogd op het moment dat de fout optrad of, er werd geen gebruiker gedetecteerd.',
    'error_ip'                         => 'Het IP-adres met betrekking tot deze fout is: :ip',
    'error_url'                        => 'URL is: :url',
    'error_user_agent'                 => 'User agent: :userAgent',
    'error_stacktrace'                 => 'De volledige stacktrace staat hieronder. Als je denkt dat dit een bug in Firefly III is, kun je dit bericht doorsturen naar <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. Dit kan helpen om de fout te verhelpen waar je net tegenaan bent gelopen.',
    'error_github_html'                => 'Als je wilt, kun je ook een nieuw issue openen op <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'Als je wilt, kun je ook een nieuw issue openen op https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'De volledige stacktrace staat hieronder:',

    // report new journals
    'new_journals_subject'             => 'Firefly III heeft een nieuwe transactie aangemaakt|Firefly III heeft :count nieuwe transacties aangemaakt',
    'new_journals_header'              => 'Firefly III heeft een nieuwe transactie voor je gemaakt. Je kan deze terug vinden in je Firefly III installatie:|Firefly III heeft :count nieuwe transacties voor je gemaakt. Je kan deze terug vinden in je Firefly III installatie:',
];

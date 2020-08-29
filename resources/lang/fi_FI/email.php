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
    'login_from_new_ip'                => 'New login on Firefly III',
    'new_ip_body'                      => 'Firefly III detected a new login on your account from an unknown IP address. If you never logged in from the IP address below, or it has been more than six months ago, Firefly III will warn you.',
    'new_ip_warning'                   => 'If you recognize this IP address or the login, you can ignore this message. If you didn\'t login, of if you have no idea what this is about, verify your password security, change it, and log out all other sessions. To do this, go to your profile page. Of course you have 2FA enabled already, right? Stay safe!',
    'ip_address'                       => 'IP address',
    'host_name'                        => 'Host',
    'date_time'                        => 'Date + time',

    // access token created
    'access_token_created_subject'     => 'Uusi käyttöoikeustunnus luotiin',
    'access_token_created_body'        => 'Joku (toivottavasti sinä) loi juuri uuden Firefly III käyttöoikeustunnuksen käyttäjätilillesi.',
    'access_token_created_explanation' => 'With this token, they can access <strong>all</strong> of your financial records through the Firefly III API.',
    'access_token_created_revoke'      => 'If this wasn\'t you, please revoke this token as soon as possible at :url.',

    // registered
    'registered_subject'               => 'Tervetuloa Firefly III:een!',
    'registered_welcome'               => 'Welcome to <a style="color:#337ab7" href=":address">Firefly III</a>. Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_pw'                    => 'If you have forgotten your password already, please reset it using <a style="color:#337ab7" href=":address/password/reset">the password reset tool</a>.',
    'registered_help'                  => 'There is a help-icon in the top right corner of each page. If you need help, click it!',
    'registered_doc_html'              => 'If you haven\'t already, please read the <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/grand-theory">grand theory</a>.',
    'registered_doc_text'              => 'If you haven\'t already, please read the first use guide and the full description.',
    'registered_closing'               => 'Nauti!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Salasanan nollaus:',
    'registered_doc_link'              => 'Dokumentaatio:',

    // email change
    'email_change_subject'             => 'Firefly III sähköpostiosoitteesi on muuttunut',
    'email_change_body_to_new'         => 'Joko sinä, tai joku jolla on pääsy Firefly III -tilillesi, on vaihtanut sähköpostiosoitteesi. Jos et odottanut tätä viestiä, ohita ja poista se.',
    'email_change_body_to_old'         => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you <strong>must</strong> follow the "undo"-link below to protect your account!',
    'email_change_ignore'              => 'If you initiated this change, you may safely ignore this message.',
    'email_change_old'                 => 'Vanha sähköpostiosoite oli: :email',
    'email_change_old_strong'          => 'Vanha sähköpostiosoite oli: <strong>:email</strong>',
    'email_change_new'                 => 'Uusi sähköpostiosoite on: :email',
    'email_change_new_strong'          => 'Uusi sähköpostiosoite on: <strong>:email</strong>',
    'email_change_instructions'        => 'You cannot use Firefly III until you confirm this change. Please follow the link below to do so.',
    'email_change_undo_link'           => 'To undo the change, follow this link:',

    // OAuth token created
    'oauth_created_subject'            => 'A new OAuth client has been created',
    'oauth_created_body'               => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'With this client, they can access <strong>all</strong> of your financial records through the Firefly III API.',
    'oauth_created_undo'               => 'If this wasn\'t you, please revoke this client as soon as possible at :url.',

    // reset password
    'reset_pw_subject'                 => 'Salasanasi nollauspyyntö',
    'reset_pw_instructions'            => 'Somebody tried to reset your password. If it was you, please follow the link below to do so.',
    'reset_pw_warning'                 => '<strong>PLEASE</strong> verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject'                    => 'Caught an error in Firefly III',
    'error_intro'                      => 'Firefly III v:version ran into an error: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'The error was of type ":class".',
    'error_timestamp'                  => 'Virhe tapahtui kello: :time.',
    'error_location'                   => 'This error occurred in file "<span style="font-family: monospace;">:file</span>" on line :line with code :code.',
    'error_user'                       => 'The error was encountered by user #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'There was no user logged in for this error or no user was detected.',
    'error_ip'                         => 'The IP address related to this error is: :ip',
    'error_url'                        => 'URL on: :url',
    'error_user_agent'                 => 'User agent: :userAgent',
    'error_stacktrace'                 => 'The full stacktrace is below. If you think this is a bug in Firefly III, you can forward this message to <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. This can help fix the bug you just encountered.',
    'error_github_html'                => 'If you prefer, you can also open a new issue on <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'If you prefer, you can also open a new issue on https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'The full stacktrace is below:',

    // report new journals
    'new_journals_subject'             => 'Firefly III has created a new transaction|Firefly III has created :count new transactions',
    'new_journals_header'              => 'Firefly III has created a transaction for you. You can find it in your Firefly III installation:|Firefly III has created :count transactions for you. You can find them in your Firefly III installation:',
];

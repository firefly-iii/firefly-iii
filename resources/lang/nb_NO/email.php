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
    'greeting'                                => 'Hei der,',
    'closing'                                 => 'Pip Boop,',
    'signature'                               => 'The Firefly III Mail Robot',
    'footer_ps'                               => 'PS: Denne meldingen ble sendt fordi en forespørsel fra IP :ipAddress utløste den.',

    // admin test
    'admin_test_subject'                      => 'En testmelding fra Firefly III-installasjonen',
    'admin_test_body'                         => 'Dette er en testmelding fra din Firefly III-instans. Den ble sendt til :email.',

    // new IP
    'login_from_new_ip'                       => 'Ny pålogging på Firefly III',
    'new_ip_body'                             => 'Firefly III oppdaget en ny pålogging på kontoen fra en ukjent IP-adresse. Hvis du aldri har logget inn fra IP-adressen under, eller det har vært mer enn et halvt år siden, vil Firefly III advare deg.',
    'new_ip_warning'                          => 'Hvis du gjenkjenner denne IP-adressen eller påloggingen, kan du ignorere denne meldingen. Hvis du ikke har logget inn, så har du ikke peiling på hva dette gjelder, bekreft passordsikkerhet, endre det, og logg ut alle økter. For å gjøre dette, gå til profilsiden. Selvsagt har du 2FA aktivert allerede, ikke sant? Vær trygg!',
    'ip_address'                              => 'IP-adresse',
    'host_name'                               => 'Vert',
    'date_time'                               => 'Dato + klokkeslett',

    // access token created
    'access_token_created_subject'            => 'En ny tilgangstoken ble opprettet',
    'access_token_created_body'               => 'Noen (forhåpentligvis du) har nettopp opprettet en ny Firefly III API Access Token for brukerkontoen din.',
    'access_token_created_explanation'        => 'Med denne token, kan de få tilgang til **alle** av dine finansielle poster gjennom Firefly III API.',
    'access_token_created_revoke'             => 'Hvis dette ikke var deg, vennligst fjern dette tokenet så snart som mulig på :url',

    // registered
    'registered_subject'                      => 'Velkommen til Firefly III!',
    'registered_welcome'                      => 'Velkommen til [Firefly III](:address). Din registrering er fullført, og denne e-posten er her for å bekrefte det. Kanon!',
    'registered_pw'                           => 'Hvis du har glemt passordet ditt allerede, kan du tilbakestille det ved å bruke [passord reset tool](:address/password/reset).',
    'registered_help'                         => 'Det er et hjelp-ikon i hjørnet øverst til høyre på hver side. Hvis du trenger hjelp, kan du klikke på den!',
    'registered_doc_html'                     => 'Hvis du ikke har allerede, vennligst les [grand theory](https://docs.firefly-iii.org/about-firefly-ii/personal-finances).',
    'registered_doc_text'                     => 'Hvis du ikke har gjort allerede, vennligst også les veiledningen for første bruk og den fullstendige beskrivelsen.',
    'registered_closing'                      => 'Kos deg!',
    'registered_firefly_iii_link'             => 'Firefly III:',
    'registered_pw_reset_link'                => 'Tilbakestill passord:',
    'registered_doc_link'                     => 'Dokumentasjon:',

    // email change
    'email_change_subject'                    => 'Din Firefly III e-postadresse er endret',
    'email_change_body_to_new'                => 'Du eller noen med tilgang til din Firefly III konto har endret e-postadressen din. Hvis du ikke forventet denne meldingen, kan du se bort fra og slette den.',
    'email_change_body_to_old'                => 'Du eller noen med tilgang til din Firefly III konto har endret e-postadressen din. Hvis du ikke forventet dette, så må du **må** følge "angre"-koblingen nedenfor for å beskytte kontoen din!',
    'email_change_ignore'                     => 'Hvis du initierte denne endringen, kan du trygt ignorere denne meldingen.',
    'email_change_old'                        => 'Den gamle e-postadressen var: :email',
    'email_change_old_strong'                 => 'Den gamle e-postadressen var: **:email',
    'email_change_new'                        => 'Den nye e-postadressen er: :email',
    'email_change_new_strong'                 => 'Den nye e-postadressen er: **:email',
    'email_change_instructions'               => 'Du kan ikke bruke Firefly III før du bekrefter denne endringen. Følg linken nedenfor for å gjøre det.',
    'email_change_undo_link'                  => 'For å angre endringen, følg denne linken:',

    // OAuth token created
    'oauth_created_subject'                   => 'En ny OAuth-klient er opprettet',
    'oauth_created_body'                      => 'Noen (forhåpentligvis du) har nettopp opprettet en ny Firefly III API OAuth Client for din brukerkonto. Den er merket ":name" og har tilbakeringing URL `:url`.',
    'oauth_created_explanation'               => 'Med denne kunden, får de tilgang til **alle** av dine finansielle poster gjennom Firefly III API.',
    'oauth_created_undo'                      => 'Hvis dette ikke var deg, vennligst slette denne klienten så snart som mulig ved `:url',

    // reset password
    'reset_pw_subject'                        => 'Din forespørsel om tilbakestilling av passord',
    'reset_pw_instructions'                   => 'Noen prøvde å tilbakestille passordet ditt. Hvis det var deg, vennligst følg linken nedenfor for å fullføre.',
    'reset_pw_warning'                        => '**PLEASE** bekrefter at lenken faktisk går til Firefly III slik du forventer at den skal gå!',

    // error
    'error_subject'                           => 'Rett en feil i Firefly III',
    'error_intro'                             => 'Firefly III v:version fikk en feil: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                              => 'Feilen var av typen ":class.',
    'error_timestamp'                         => 'Feilen oppstod på: :time.',
    'error_location'                          => 'Denne feilen oppstod i filen "<span style="font-family: monospace;">:file</span>" på linje :line med kode :code.',
    'error_user'                              => 'Feilen oppstod på av brukeren #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                           => 'Det var ingen bruker som var pålogget for denne feilen, eller ingen bruker ble oppdaget.',
    'error_ip'                                => 'The IP address related to this error is: :ip',
    'error_url'                               => 'URL is: :url',
    'error_user_agent'                        => 'User agent: :userAgent',
    'error_stacktrace'                        => 'The full stacktrace is below. If you think this is a bug in Firefly III, you can forward this message to <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. This can help fix the bug you just encountered.',
    'error_github_html'                       => 'If you prefer, you can also open a new issue on <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                       => 'If you prefer, you can also open a new issue on https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'                  => 'The full stacktrace is below:',
    'error_headers'                           => 'The following headers may also be relevant:',

    // report new journals
    'new_journals_subject'                    => 'Firefly III has created a new transaction|Firefly III has created :count new transactions',
    'new_journals_header'                     => 'Firefly III has created a transaction for you. You can find it in your Firefly III installation:|Firefly III has created :count transactions for you. You can find them in your Firefly III installation:',

    // bill warning
    'bill_warning_subject_end_date'           => 'Your bill ":name" is due to end in :diff days',
    'bill_warning_subject_now_end_date'       => 'Your bill ":name" is due to end TODAY',
    'bill_warning_subject_extension_date'     => 'Your bill ":name" is due to be extended or cancelled in :diff days',
    'bill_warning_subject_now_extension_date' => 'Your bill ":name" is due to be extended or cancelled TODAY',
    'bill_warning_end_date'                   => 'Your bill **":name"** is due to end on :date. This moment will pass in about **:diff days**.',
    'bill_warning_extension_date'             => 'Your bill **":name"** is due to be extended or cancelled on :date. This moment will pass in about **:diff days**.',
    'bill_warning_end_date_zero'              => 'Your bill **":name"** is due to end on :date. This moment will pass **TODAY!**',
    'bill_warning_extension_date_zero'        => 'Your bill **":name"** is due to be extended or cancelled on :date. This moment will pass **TODAY!**',
    'bill_warning_please_action'              => 'Please take the appropriate action.',

];

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
    'greeting'                         => 'Salut,',
    'closing'                          => 'Bip boop,',
    'signature'                        => 'Robot Mail Firefly III',
    'footer_ps'                        => 'PS: Acest mesaj a fost trimis deoarece o solicitare de la IP :ipAddress a declanşat-o.',

    // admin test
    'admin_test_subject'               => 'Un mesaj de testare de la instalarea Firefly III',
    'admin_test_body'                  => 'Acesta este un mesaj de test de la instanța dvs. Firefly III. Acesta a fost trimis la :email.',

    // access token created
    'access_token_created_subject'     => 'Un nou token de acces a fost creat',
    'access_token_created_body'        => 'Cineva (sperăm că dvs.) tocmai a creat un nou Firefly III API Access Token pentru contul dvs. de utilizator.',
    'access_token_created_explanation' => 'Cu acest token, pot accesa <strong>toate</strong> înregistrările financiare prin API-ul Firefly III.',
    'access_token_created_revoke'      => 'Dacă nu ai fost tu, te rugăm să revoci acest token cât mai curând posibil la :url.',

    // registered
    'registered_subject'               => 'Bun venit la Firefly III!',
    'registered_welcome'               => 'Bine ați venit la <a style="color:#337ab7" href=":address">Firefly III</a>. Înregistrarea dvs. s-a făcut, iar acest e-mail este aici pentru a-l confirma. Yay!',
    'registered_pw'                    => 'Dacă v-ați uitat deja parola, vă rugăm să o resetați folosind <a style="color:#337ab7" href=":address/password/reset">unealta de resetare a parolei</a>.',
    'registered_help'                  => 'Există o pictogramă de ajutor în colțul din dreapta sus al fiecărei pagini. Dacă ai nevoie de ajutor, apasă pe ea!',
    'registered_doc_html'              => 'Dacă nu ați citit deja, vă rugăm sa cititi<a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/grand-theory">documentatia</a>.',
    'registered_doc_text'              => 'Dacă nu ați facut-o deja, va rugam citit ghidul de utilizare și descrierea completă.',
    'registered_closing'               => 'Bucurați-vă de el!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Resetare parolă:',
    'registered_doc_link'              => 'Documentaţie:',

    // email change
    'email_change_subject'             => 'Adresa ta de email a fost schimbată',
    'email_change_body_to_new'         => 'Dumneavoastră sau cineva cu acces la contul dvs. Firefly III v-a schimbat adresa de e-mail. Dacă nu ați așteptat acest mesaj, vă rugăm să îl ignorați și să îl ștergeți.',
    'email_change_body_to_old'         => 'Dumneavoastră sau cineva cu acces la contul dvs. Firefly III v-a schimbat adresa de e-mail. Dacă nu v-ați așteptat ca acest lucru să se întâmple, <strong>trebuie</strong> să urmați linkul "undo" de mai jos pentru a vă proteja contul!',
    'email_change_ignore'              => 'Dacă ați inițiat această schimbare, puteți ignora în siguranță acest mesaj.',
    'email_change_old'                 => 'Vechea adresă de e-mail a fost: :email',
    'email_change_old_strong'          => 'Vechea adresă de e-mail a fost: <strong>:email</strong>',
    'email_change_new'                 => 'Noua adresă de e-mail este: :email',
    'email_change_new_strong'          => 'Noua adresă de e-mail este: <strong>:email</strong>',
    'email_change_instructions'        => 'Nu puteți utiliza Firefly III până când nu confirmați această modificare. Vă rugăm să urmați link-ul de mai jos pentru a face acest lucru.',
    'email_change_undo_link'           => 'Pentru a anula modificarea, urmați acest link:',

    // OAuth token created
    'oauth_created_subject'            => 'Un nou client OAuth a fost creat',
    'oauth_created_body'               => 'Cineva (sperăm că dvs.) tocmai a creat un nou client Firefly III API OAuth pentru contul dvs. de utilizator. Este etichetat ":name" și are URL-ul de apel invers <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'Cu acest client, acesta poate accesa <strong>toate</strong> înregistrările financiare prin API-ul Firefly III.',
    'oauth_created_undo'               => 'Dacă nu ai fost tu, te rugăm să revoci acest client cât mai curând posibil la :url.',

    // reset password
    'reset_pw_subject'                 => 'Solicitarea de resetare a parolei',
    'reset_pw_instructions'            => 'Cineva a încercat să-ți reseteze parola. Dacă ai fost, te rugăm să urmezi link-ul de mai jos pentru a face acest lucru.',
    'reset_pw_warning'                 => '<strong>VĂ RUGĂM</strong> verifică dacă linkul merge efectiv la link-ul pe care îl așteptați!',

    // error
    'error_subject'                    => 'Am descoperit o eroare în Firefly III',
    'error_intro'                      => 'Firefly III v:version a întâmpinat o eroare: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'Eroarea a fost de tip ":class".',
    'error_timestamp'                  => 'Eroarea a apărut pe/la: :time.',
    'error_location'                   => 'Această eroare a apărut în fișierul "<span style="font-family: monospace;">:file</span>" pe linia :line cu codul :code.',
    'error_user'                       => 'Eroarea a fost întâlnită de utilizatorul #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'Nu a existat niciun utilizator conectat pentru această eroare sau niciun utilizator nu a fost detectat.',
    'error_ip'                         => 'Adresa IP asociată acestei erori este: :ip',
    'error_url'                        => 'URL-ul este: :url',
    'error_user_agent'                 => 'Agent utilizator: :userAgent',
    'error_stacktrace'                 => 'Lantul erorilor este mai jos. Dacă credeți că acesta este un bug în Firefly III, puteți transmite acest mesaj la <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii. rg</a>. Acest lucru poate ajuta la rezolvarea problemei pe care tocmai ați întâlnit-o.',
    'error_github_html'                => 'Dacă preferați, puteți de asemenea deschide o nouă problemă pe <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'Dacă preferați, puteți de asemenea deschide o nouă problemă pe <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_stacktrace_below'           => 'Stacktrack-ul complet este mai jos:',

    // report new journals
    'new_journals_subject'             => 'Firefly III a creat o nouă tranzacție, Firefly III a creat :count tranzacții noi',
    'new_journals_header'              => 'Firefly III a creat o tranzacție pentru dvs. O puteți găsi în instalarea dvs. Firefly III:|Firefly III a creat :count tranzacții pentru dvs. Le puteți găsi în instalarea Firefly III:',
];

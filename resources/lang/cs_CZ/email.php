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
    'greeting'                                => 'Ahoj,',
    'closing'                                 => 'Píp píp,',
    'signature'                               => 'Firefly III e-mail robot',
    'footer_ps'                               => 'PS: Tato zpráva byla odeslána, na žádost z IP :ipAddress.',

    // admin test
    'admin_test_subject'                      => 'Testovací zpráva z vaší instalace Firefly III',
    'admin_test_body'                         => 'Toto je testovací zpráva z instance Firefly III. Byla odeslána na :email.',

    // new IP
    'login_from_new_ip'                       => 'Nové přihlášení do Firefly III',
    'new_ip_body'                             => 'Firefly III zjistil nové přihlášení na Vašem účtu z neznámé IP adresy. Pokud jste se nikdy nepřihlásili z IP adresy níže, nebo to bylo před více než šesti měsíci, Firefly III Vás upozorní.',
    'new_ip_warning'                          => 'Pokud rozpoznáte tuto IP adresu nebo přihlašovací jméno, můžete tuto zprávu ignorovat. Pokud jste se nepřihlásili, nebo jestli nemáte tušení, o co jde, ověřte zabezpečení hesla, změňte ho a odhlásíte všechny ostatní relace. Chcete-li to provést, jděte na stránku svého profilu. Samozřejmě už máte dvoufaktorové přihlašování povoleno, že? Zůstaňte v bezpečí!',
    'ip_address'                              => 'IP adresa',
    'host_name'                               => 'Hostitel',
    'date_time'                               => 'Datum + čas',

    // access token created
    'access_token_created_subject'            => 'Byl vytvořen nový přístupový token',
    'access_token_created_body'               => 'Někdo (doufejme, že vy) právě vytvořil nový přístupový Token Firefly III API pro váš uživatelský účet.',
    'access_token_created_explanation'        => 'With this token, they can access **all** of your financial records through the Firefly III API.',
    'access_token_created_revoke'             => 'If this wasn\'t you, please revoke this token as soon as possible at :url',

    // registered
    'registered_subject'                      => 'Vítejte ve Firefly III!',
    'registered_welcome'                      => 'Welcome to [Firefly III](:address). Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_pw'                           => 'If you have forgotten your password already, please reset it using [the password reset tool](:address/password/reset).',
    'registered_help'                         => 'V pravém horním rohu každé stránky je ikona nápovědy. Pokud potřebujete pomoc, klikněte na ní!',
    'registered_doc_html'                     => 'If you haven\'t already, please read the [grand theory](https://docs.firefly-iii.org/about-firefly-iii/personal-finances).',
    'registered_doc_text'                     => 'If you haven\'t already, please also read the first use guide and the full description.',
    'registered_closing'                      => 'Užívejte!',
    'registered_firefly_iii_link'             => 'Firefly III:',
    'registered_pw_reset_link'                => 'Obnovení hesla:',
    'registered_doc_link'                     => 'Dokumentace:',

    // email change
    'email_change_subject'                    => 'Vaše Firefly III e-mailová adresa se změnila',
    'email_change_body_to_new'                => 'Vy nebo někdo s přístupem k vašemu účtu Firefly III změnil vaši e-mailovou adresu. Pokud jste neočekávali tuto zprávu, prosím ignorujte a odstraňte ji.',
    'email_change_body_to_old'                => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you **must** follow the "undo"-link below to protect your account!',
    'email_change_ignore'                     => 'Pokud jste iniciovali tuto změnu, můžete tuto zprávu klidně ignorovat.',
    'email_change_old'                        => 'Stará e-mailová adresa byla: :email',
    'email_change_old_strong'                 => 'The old email address was: **:email**',
    'email_change_new'                        => 'Nová e-mailová adresa je: :email',
    'email_change_new_strong'                 => 'The new email address is: **:email**',
    'email_change_instructions'               => 'Dokud nepotvrdíte tuto změnu, tak nemůžete používat Firefly III. Postupujte prosím kliknutím na níže uvedený odkaz.',
    'email_change_undo_link'                  => 'Změnu vrátíte zpět kliknutím na odkaz:',

    // OAuth token created
    'oauth_created_subject'                   => 'Byl vytvořen nový OAuth klient',
    'oauth_created_body'                      => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL `:url`.',
    'oauth_created_explanation'               => 'With this client, they can access **all** of your financial records through the Firefly III API.',
    'oauth_created_undo'                      => 'If this wasn\'t you, please revoke this client as soon as possible at `:url`',

    // reset password
    'reset_pw_subject'                        => 'Požadavek na obnovení Vašeho hesla',
    'reset_pw_instructions'                   => 'Někdo se pokusil obnovit Vaše heslo. Pokud jste to byli Vy, postupujte prosím podle níže uvedeného odkazu.',
    'reset_pw_warning'                        => '**PLEASE** verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject'                           => 'Zachycená chyba ve Firefly III',
    'error_intro'                             => 'Firefly III v:version narazil na chybu: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                              => 'Třída chyby „:class“.',
    'error_timestamp'                         => 'K chybě došlo v: :time.',
    'error_location'                          => 'Tato chyba se vyskytla v souboru "<span style="font-family: monospace;">:file</span>" na řádku :line s kódem :code.',
    'error_user'                              => 'Chyba se vyskytla u uživatele #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                           => 'Pro tuto chybu nebyl přihlášen žádný uživatel nebo nebyl žádný uživatelů zjištěn.',
    'error_ip'                                => 'IP adresa související s touto chybou je: :ip',
    'error_url'                               => 'Adresa URL je: :url',
    'error_user_agent'                        => 'User agent: :userAgent',
    'error_stacktrace'                        => 'Úplný zásobník je uveden níže. Pokud si myslíte, že se jedná o chybu ve Firefly III, můžete tuto zprávu přeposlat na <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii. rg</a>. To může pomoci opravit chybu, na kterou jste právě narazili.',
    'error_github_html'                       => 'Pokud chcete, můžete vytvořit hlášení problému na <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                       => 'Pokud chcete, můžete vytvořit hlášení problému na https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'                  => 'Celý zásobník je níže:',
    'error_headers'                           => 'The following headers may also be relevant:',

    // report new journals
    'new_journals_subject'                    => 'Firefly III vytvořil novou transakci|Firefly III vytvořil :count nových transakcí',
    'new_journals_header'                     => 'Firefly III pro Vás vytvořil transakci. Můžete ji najít ve vaší instalaci Firefly III:|Firefly III vytvořil :count transakcí. Najdete je ve vaší instalaci Firefly III:',

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

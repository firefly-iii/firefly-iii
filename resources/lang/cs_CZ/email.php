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
    'greeting'                         => 'Ahoj,',
    'closing'                          => 'Píp píp,',
    'signature'                        => 'Firefly III e-mail robot',
    'footer_ps'                        => 'PS: Tato zpráva byla odeslána, na žádost z IP :ipAddress.',

    // admin test
    'admin_test_subject'               => 'Testovací zpráva z vaší instalace Firefly III',
    'admin_test_body'                  => 'Toto je testovací zpráva z instance Firefly III. Byla odeslána na :email.',

    // new IP
    'login_from_new_ip'                => 'Nové přihlášení do Firefly III',
    'new_ip_body'                      => 'Firefly III zjistil nové přihlášení na Vašem účtu z neznámé IP adresy. Pokud jste se nikdy nepřihlásili z IP adresy níže, nebo to bylo před více než šesti měsíci, Firefly III Vás upozorní.',
    'new_ip_warning'                   => 'Pokud rozpoznáte tuto IP adresu nebo přihlašovací jméno, můžete tuto zprávu ignorovat. Pokud jste se nepřihlásili, nebo jestli nemáte tušení, o co jde, ověřte zabezpečení hesla, změňte ho a odhlásíte všechny ostatní relace. Chcete-li to provést, jděte na stránku svého profilu. Samozřejmě už máte dvoufaktorové přihlašování povoleno, že? Zůstaňte v bezpečí!',
    'ip_address'                       => 'IP adresa',
    'host_name'                        => 'Hostitel',
    'date_time'                        => 'Datum + čas',

    // access token created
    'access_token_created_subject'     => 'Byl vytvořen nový přístupový token',
    'access_token_created_body'        => 'Někdo (doufejme, že vy) právě vytvořil nový přístupový Token Firefly III API pro váš uživatelský účet.',
    'access_token_created_explanation' => 'Pomocí tohoto tokenu mají přístup <strong>ke všem</strong> vašim finančním záznamům prostřednictvím rozhraní Firefly III.',
    'access_token_created_revoke'      => 'Pokud jste to nebyli vy, prosím zrušte tento token co nejdříve na adrese :url.',

    // registered
    'registered_subject'               => 'Vítejte ve Firefly III!',
    'registered_welcome'               => 'Vítejte v <a style="color:#337ab7" href=":address">Firefly III</a>. Vaše registrace se úspěšně provedla a tento e-mail Vám přišel jako potvrzení. Hurá!',
    'registered_pw'                    => 'Pokud jste již zapomněli své heslo, obnovte jej pomocí <a style="color:#337ab7" href=":address/password/reset">nástroje pro obnovení hesla</a>.',
    'registered_help'                  => 'V pravém horním rohu každé stránky je ikona nápovědy. Pokud potřebujete pomoc, klikněte na ní!',
    'registered_doc_html'              => 'Pokud jste tak již neudělali, přečtěte si prosím <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/personal-finances">hlavní myšlenku</a>.',
    'registered_doc_text'              => 'Pokud jste tak již neudělali, přečtěte si prosím návod pro první použití a úplný popis.',
    'registered_closing'               => 'Užívejte!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Obnovení hesla:',
    'registered_doc_link'              => 'Dokumentace:',

    // email change
    'email_change_subject'             => 'Vaše Firefly III e-mailová adresa se změnila',
    'email_change_body_to_new'         => 'Vy nebo někdo s přístupem k vašemu účtu Firefly III změnil vaši e-mailovou adresu. Pokud jste neočekávali tuto zprávu, prosím ignorujte a odstraňte ji.',
    'email_change_body_to_old'         => 'Vy nebo někdo s přístupem k vašemu účtu Firefly III změnil vaši e-mailovou adresu. Pokud jste neočekávali, že se tak stane, <strong>musíte</strong> pro ochranu vašeho účtu následovat odkaz "zrušení"!',
    'email_change_ignore'              => 'Pokud jste iniciovali tuto změnu, můžete tuto zprávu klidně ignorovat.',
    'email_change_old'                 => 'Stará e-mailová adresa byla: :email',
    'email_change_old_strong'          => 'Stará e-mailová adresa byla: <strong>:email</strong>',
    'email_change_new'                 => 'Nová e-mailová adresa je: :email',
    'email_change_new_strong'          => 'Nová e-mailová adresa je: <strong>:email</strong>',
    'email_change_instructions'        => 'Dokud nepotvrdíte tuto změnu, tak nemůžete používat Firefly III. Postupujte prosím kliknutím na níže uvedený odkaz.',
    'email_change_undo_link'           => 'Změnu vrátíte zpět kliknutím na odkaz:',

    // OAuth token created
    'oauth_created_subject'            => 'Byl vytvořen nový OAuth klient',
    'oauth_created_body'               => 'Někdo (doufejme, že vy) právě vytvořil nový Firefly III API OAuth klienta pro váš uživatelský účet. Je pojmenovaný „:name“ a má URL adresu <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'S tímto klientem mají přístup <strong>ke všem</strong> vašim finančním záznamům prostřednictvím rozhraní Firefly III.',
    'oauth_created_undo'               => 'Pokud jste to nebyli Vy, zrušte tohoto klienta co nejdříve na adrese :url.',

    // reset password
    'reset_pw_subject'                 => 'Požadavek na obnovení Vašeho hesla',
    'reset_pw_instructions'            => 'Někdo se pokusil obnovit Vaše heslo. Pokud jste to byli Vy, postupujte prosím podle níže uvedeného odkazu.',
    'reset_pw_warning'                 => '<strong>PROSÍM</strong> ověřte, že odkaz jde opravdu na Firefly III, kam očekáváte!',

    // error
    'error_subject'                    => 'Zachycená chyba ve Firefly III',
    'error_intro'                      => 'Firefly III v:version narazil na chybu: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'Třída chyby „:class“.',
    'error_timestamp'                  => 'K chybě došlo v: :time.',
    'error_location'                   => 'Tato chyba se vyskytla v souboru "<span style="font-family: monospace;">:file</span>" na řádku :line s kódem :code.',
    'error_user'                       => 'Chyba se vyskytla u uživatele #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'Pro tuto chybu nebyl přihlášen žádný uživatel nebo nebyl žádný uživatelů zjištěn.',
    'error_ip'                         => 'IP adresa související s touto chybou je: :ip',
    'error_url'                        => 'Adresa URL je: :url',
    'error_user_agent'                 => 'User agent: :userAgent',
    'error_stacktrace'                 => 'Úplný zásobník je uveden níže. Pokud si myslíte, že se jedná o chybu ve Firefly III, můžete tuto zprávu přeposlat na <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii. rg</a>. To může pomoci opravit chybu, na kterou jste právě narazili.',
    'error_github_html'                => 'Pokud chcete, můžete vytvořit hlášení problému na <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'Pokud chcete, můžete vytvořit hlášení problému na https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'Celý zásobník je níže:',

    // report new journals
    'new_journals_subject'             => 'Firefly III vytvořil novou transakci|Firefly III vytvořil :count nových transakcí',
    'new_journals_header'              => 'Firefly III pro Vás vytvořil transakci. Můžete ji najít ve vaší instalaci Firefly III:|Firefly III vytvořil :count transakcí. Najdete je ve vaší instalaci Firefly III:',
];

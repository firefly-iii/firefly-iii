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
    'greeting'                         => 'Cześć,',
    'closing'                          => 'Jestę robotę',
    'signature'                        => 'Robot pocztowy Firefly III',
    'footer_ps'                        => 'PS: Ta wiadomość została wysłana, ponieważ została wywołana przez żądanie z adresu IP :ipAddress .',

    // admin test
    'admin_test_subject'               => 'Wiadomość testowa z twojej instalacji Firefly III',
    'admin_test_body'                  => 'To jest wiadomość testowa z twojej instancji Firefly III. Została wysłana na :email.',

    // access token created
    'access_token_created_subject'     => 'Utworzono nowy token dostępu',
    'access_token_created_body'        => 'Ktoś (mam nadzieję, że Ty) właśnie utworzył nowy token dostępu API Firefly III dla Twojego konta użytkownika.',
    'access_token_created_explanation' => 'Z tym tokenem można uzyskać dostęp do <strong>wszystkich</strong> Twoich zapisów finansowych za pośrednictwem API Firefly III.',
    'access_token_created_revoke'      => 'Jeśli to nie Ty, cofnij ten token tak szybko jak to możliwe pod adresem :url.',

    // registered
    'registered_subject'               => 'Witaj w Firefly III!',
    'registered_welcome'               => 'Witaj w <a style="color:#337ab7" href=":address">Firefly III</a>. Twoja rejestracja już się powiodła, a ten e-mail jest tutaj, aby go potwierdzić. Super!',
    'registered_pw'                    => 'Jeśli zapomniałeś już swojego hasła, zresetuj je używając <a style="color:#337ab7" href=":address/password/reset">narzędzia do resetowania hasła</a>.',
    'registered_help'                  => 'W prawym górnym rogu każdej strony jest ikonka pomocy. Jeśli potrzebujesz pomocy, kliknij ją!',
    'registered_doc_html'              => 'Jeśli jeszcze tego nie zrobiłeś, przeczytaj <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/grand-theory">wielką teorię</a>.',
    'registered_doc_text'              => 'Jeśli jeszcze tego nie zrobiłeś, przeczytaj przewodnik pierwszego użycia i pełny opis.',
    'registered_closing'               => 'Dobrej zabawy!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Resetowanie hasła:',
    'registered_doc_link'              => 'Dokumentacja:',

    // email change
    'email_change_subject'             => 'Twój adres e-mail Firefly III został zmieniony',
    'email_change_body_to_new'         => 'Ty lub ktoś z dostępem do Twojego konta Firefly III zmienił Twój adres e-mail. Jeśli spodziewałeś się tej wiadomości, zignoruj ją i usuń.',
    'email_change_body_to_old'         => 'Ty lub ktoś z dostępem do Twojego konta Firefly III zmienił Twój adres e-mail. Jeśli nie oczekiwałeś, że tak się stanie, <strong>musisz</strong> postępuj zgodnie z poniższym linkiem "cofnij" aby chronić swoje konto!',
    'email_change_ignore'              => 'Jeśli zainicjowałeś tę zmianę, możesz bezpiecznie zignorować tę wiadomość.',
    'email_change_old'                 => 'Stary adres e-mail to: :email',
    'email_change_old_strong'          => 'Stary adres e-mail to: <strong>:email</strong>',
    'email_change_new'                 => 'Nowy adres e-mail to: :email',
    'email_change_new_strong'          => 'Nowy adres e-mail to: <strong>:email</strong>',
    'email_change_instructions'        => 'Nie możesz używać Firefly III, dopóki nie potwierdzisz tej zmiany. Kliknij poniższy link, aby to zrobić.',
    'email_change_undo_link'           => 'Aby cofnąć zmianę, kliknij ten link:',

    // OAuth token created
    'oauth_created_subject'            => 'Nowy klient OAuth został utworzony',
    'oauth_created_body'               => 'Ktoś (mam nadzieję, że Ty) właśnie utworzył nowego klienta API OAuth Firefly III dla Twojego konta użytkownika. Jest oznaczony ":name" i ma zwrotny adres URL <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'Z tym klientem można uzyskać dostęp do <strong>wszystkich</strong> Twoich zapisów finansowych za pośrednictwem API Firefly III.',
    'oauth_created_undo'               => 'Jeśli to nie Ty, cofnij tego klienta tak szybko jak to możliwe pod adresem :url.',

    // reset password
    'reset_pw_subject'                 => 'Żądanie zmiany hasła',
    'reset_pw_instructions'            => 'Ktoś próbował zresetować hasło. Jeśli to Ty, kliknij poniższy link, aby to zrobić.',
    'reset_pw_warning'                 => '<strong>PROSZĘ</strong> sprawdź, czy link rzeczywiście przejdzie do Firefly III, którego oczekiwałeś!',

    // error
    'error_subject'                    => 'Błąd w Firefly III',
    'error_intro'                      => 'Firefly III v:version napotkał błąd: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'Błąd był typu ":class".',
    'error_timestamp'                  => 'Błąd wystąpił o: :time.',
    'error_location'                   => 'Błąd wystąpił w pliku "<span style="font-family: monospace;">:file</span>" linia :line z kodem :code.',
    'error_user'                       => 'Błąd został napotkany przez użytkownika #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'Dla tego błędu nie znaleziono zalogowanego użytkownika lub nie wykryto żadnego użytkownika.',
    'error_ip'                         => 'Adres IP związany z tym błędem to: :ip',
    'error_url'                        => 'Adres URL to: :url',
    'error_user_agent'                 => 'Agent użytkownika: :userAgent',
    'error_stacktrace'                 => 'Pełny opis błędu znajduje się poniżej. Jeśli uważasz, że jest to błąd w Firefly III, możesz przesłać tę wiadomość do <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii. rg</a>. To może pomóc naprawić napotkany właśnie błąd.',
    'error_github_html'                => 'Jeśli wolisz, możesz również otworzyć nowy problem na <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'Jeśli wolisz, możesz również otworzyć nowy problem na https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'Pełny opis błędu znajduje się poniżej:',

    // report new journals
    'new_journals_subject'             => 'Firefly III stworzył nową transakcję|Firefly III stworzył :count nowych transakcji',
    'new_journals_header'              => 'Firefly III stworzył dla Ciebie transakcję. Możesz znaleźć ją w Firefly III:|Firefly III stworzył dla Ciebie transakcje :count. Możesz je znaleźć w Firefly III:',
];

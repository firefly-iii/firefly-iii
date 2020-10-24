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
    'greeting'                         => 'Здравейте,',
    'closing'                          => 'Beep boop,',
    'signature'                        => 'Пощенският робот на Firefly III',
    'footer_ps'                        => 'PS: Това съобщение беше изпратено, защото заявка от IP :ipAddress го задейства.',

    // admin test
    'admin_test_subject'               => 'Тестово съобщение от вашата инсталация на Firefly III',
    'admin_test_body'                  => 'Това е тестово съобщение от вашата Firefly III инстанция. То беше изпратено на :email.',

    // new IP
    'login_from_new_ip'                => 'Ново влизане в Firefly III',
    'new_ip_body'                      => 'Firefly III откри нов вход за вашия акаунт от неизвестен IP адрес. Ако никога не сте влизали от IP адреса по-долу или е било преди повече от шест месеца, Firefly III ще ви предупреди.',
    'new_ip_warning'                   => 'Ако разпознаете този IP адрес или данните за вход, можете да игнорирате това съобщение. Ако не сте влезли вие или ако нямате представа за какво става въпрос, проверете защитата на паролата си, променете я и излезте от всички останали сесии. За да направите това, отидете на страницата на вашия профил. Разбира се, че вече сте активирали 2FA, нали? Пазете се!',
    'ip_address'                       => 'IP адрес',
    'host_name'                        => 'Сървър',
    'date_time'                        => 'Дата + час',

    // access token created
    'access_token_created_subject'     => 'Създаден е нов маркер за достъп (токен)',
    'access_token_created_body'        => 'Някой (дано да сте вие) току-що създаде нов Firefly III API Token за вашия потребителски акаунт.',
    'access_token_created_explanation' => 'С този токен те могат да имат достъп до <strong> всички </strong> ваши финансови записи чрез Firefly III API.',
    'access_token_created_revoke'      => 'Ако това не сте вие, моля, отменете този токен възможно най-скоро на адрес :url.',

    // registered
    'registered_subject'               => 'Добре дошли в Firefly III!',
    'registered_welcome'               => 'Добре дошли в <a style="color:#337ab7" href=":address">Firefly III</a>. Вашата регистрация е направена и този имейл е тук, за да го потвърди. Супер!',
    'registered_pw'                    => 'Ако вече сте забравили паролата си, моля нулирайте я с помощта на <a style="color:#337ab7" href=":address/password/reset"> инструмента за възстановяване на паролата </a>.',
    'registered_help'                  => 'В горния десен ъгъл на всяка страница има икона за помощ. Ако имате нужда от помощ, щракнете върху нея!',
    'registered_doc_html'              => 'If you haven\'t already, please read the <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/personal-finances">grand theory</a>.',
    'registered_doc_text'              => 'Ако още не сте го направили, моля прочетете ръководството за използване както и пълното описание.',
    'registered_closing'               => 'Наслаждавайте се!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Смяна на парола:',
    'registered_doc_link'              => 'Документация:',

    // email change
    'email_change_subject'             => 'Вашият имейл адрес за Firefly III е променен',
    'email_change_body_to_new'         => 'Вие или някой с достъп до вашия акаунт в Firefly III е променили имейл адреса ви. Ако не очаквате това съобщение, моля игнорирайте го и го изтрийте.',
    'email_change_body_to_old'         => 'Вие или някой с достъп до вашия акаунт в Firefly III е променил имейл адреса ви. Ако не сте очаквали това да се случи, <strong>трябва</strong> да последвате връзката „отмяна“ по-долу, за да защитите акаунта си!',
    'email_change_ignore'              => 'Ако сте инициирали тази промяна, можете безопасно да игнорирате това съобщение.',
    'email_change_old'                 => 'Старият имейл адрес беше: :email',
    'email_change_old_strong'          => 'Старият имейл адрес беше: <strong>:email</strong>',
    'email_change_new'                 => 'Новият имейл адрес е: :email',
    'email_change_new_strong'          => 'Новият имейл адрес е: <strong>:email</strong>',
    'email_change_instructions'        => 'Не можете да използвате Firefly III докато не потвърдите тази промяна. Моля, следвайте линка по-долу, за да го направите.',
    'email_change_undo_link'           => 'За да отмените промяната последвайте тази връзка:',

    // OAuth token created
    'oauth_created_subject'            => 'Създаден е нов клиент на OAuth',
    'oauth_created_body'               => 'Някой (дано да сте вие) току-що създаде нов клиент OAuth API на Firefly III за вашия потребителски акаунт. Той е обозначен като ":name" и има URL адрес за обратно извикване <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'С този клиент те могат да имат достъп до <strong> всички </strong> ваши финансови записи чрез Firefly III API.',
    'oauth_created_undo'               => 'Ако това не сте вие, моля отменете този клиент възможно най-скоро на адрес :url.',

    // reset password
    'reset_pw_subject'                 => 'Вашето искане за смяна на парола',
    'reset_pw_instructions'            => 'Някой се опита да смени паролата ви. Ако сте вие, моля последвайте линка по-долу, за да го направите.',
    'reset_pw_warning'                 => '<strong> МОЛЯ </strong> проверете дали връзката всъщност отива към адреса на Firefly III, къде очаквате да отиде!',

    // error
    'error_subject'                    => 'Уловена е грешка в Firefly III',
    'error_intro'                      => 'Firefly III v:version попадна в грешка: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'Грешката беше от вид:":class".',
    'error_timestamp'                  => 'Грешката се случи на/в: :time.',
    'error_location'                   => 'Тази грешка се появи във файл "<span style="font-family: monospace;">:file</span>" на ред: :line с код: :code.',
    'error_user'                       => 'На грешката попадна потребител #:id,<a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'Нямаше регистриран потребител при тази грешка или не бе открит потребителя.',
    'error_ip'                         => 'IP адресът, свързан с тази грешка, е: :ip',
    'error_url'                        => 'URL адресът е: :url',
    'error_user_agent'                 => 'Броузър агент: :userAgent',
    'error_stacktrace'                 => 'Пълният стак на грешката е отдолу. Ако смятате, че това е грешка в Firefly III, можете да препратите това съобщение до <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. Това може да помогне за отстраняване на грешката, която току-що срещнахте.',
    'error_github_html'                => 'Ако предпочитате, можете също да отворите нов проблем на <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'Ако предпочитате, можете също да отворите нов проблем на https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'Пълният stacktrace е отдолу:',

    // report new journals
    'new_journals_subject'             => 'Firefly III създаде нова транзакция | Firefly III създаде :count нови транзакции',
    'new_journals_header'              => 'Firefly III създаде транзакция за вас. Можете да я намерите във вашата инсталация на Firefly III: | Firefly III създаде :count транзакции за вас. Можете да ги намерите във вашата инсталация на Firefly III:',
];

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
    'greeting'                         => 'Привет,',
    'closing'                          => 'Бип-бип,',
    'signature'                        => 'Почтовый робот Firefly III',
    'footer_ps'                        => 'PS: Это сообщение было отправлено, потому что его запросили с IP :ipAddress.',

    // admin test
    'admin_test_subject'               => 'Тестовое сообщение от вашей установки Firefly III',
    'admin_test_body'                  => 'Это тестовое сообщение из вашего экземпляра Firefly III. Оно было отправлено на :email.',

    // access token created
    'access_token_created_subject'     => 'Создан новый токен доступа',
    'access_token_created_body'        => 'Кто-то (надеемся, что вы) только что создал новый токен доступа к Firefly III API для вашей учетной записи.',
    'access_token_created_explanation' => 'With this token, they can access <strong>all</strong> of your financial records through the Firefly III API.',
    'access_token_created_revoke'      => 'If this wasn\'t you, please revoke this token as soon as possible at :url.',

    // registered
    'registered_subject'               => 'Добро пожаловать в Firefly III!',
    'registered_welcome'               => 'Welcome to <a style="color:#337ab7" href=":address">Firefly III</a>. Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_pw'                    => 'If you have forgotten your password already, please reset it using <a style="color:#337ab7" href=":address/password/reset">the password reset tool</a>.',
    'registered_help'                  => 'There is a help-icon in the top right corner of each page. If you need help, click it!',
    'registered_doc_html'              => 'If you haven\'t already, please read the <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/grand-theory">grand theory</a>.',
    'registered_doc_text'              => 'If you haven\'t already, please read the first use guide and the full description.',
    'registered_closing'               => 'Наслаждайтесь!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Сбросить пароль:',
    'registered_doc_link'              => 'Документация:',

    // email change
    'email_change_subject'             => 'Ваш адрес электронной почты Firefly III был изменен',
    'email_change_body_to_new'         => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this message, please ignore and delete it.',
    'email_change_body_to_old'         => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you <strong>must</strong> follow the "undo"-link below to protect your account!',
    'email_change_ignore'              => 'Если вы инициировали это изменение, вы можете спокойно проигнорировать это сообщение.',
    'email_change_old'                 => 'Старый адрес электронной почты: :email',
    'email_change_old_strong'          => 'Старый адрес электронной почты: <strong>:email</strong>',
    'email_change_new'                 => 'Новый адрес электронной почты: :email',
    'email_change_new_strong'          => 'Новый адрес электронной почты: <strong>:email</strong>',
    'email_change_instructions'        => 'Вы не можете использовать Firefly III, пока не подтвердите это изменение. Перейдите по ссылке ниже, чтобы сделать это.',
    'email_change_undo_link'           => 'Чтобы отменить изменения, перейдите по ссылке:',

    // OAuth token created
    'oauth_created_subject'            => 'Создан новый OAuth клиент',
    'oauth_created_body'               => 'Кто-то (надеемся, что вы) только что создал новый клиент API OAuth для вашей учетной записи. Он назван ":name" и имеет обратный URL <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'With this client, they can access <strong>all</strong> of your financial records through the Firefly III API.',
    'oauth_created_undo'               => 'If this wasn\'t you, please revoke this client as soon as possible at :url.',

    // reset password
    'reset_pw_subject'                 => 'Ваш запрос на сброс пароля',
    'reset_pw_instructions'            => 'Somebody tried to reset your password. If it was you, please follow the link below to do so.',
    'reset_pw_warning'                 => '<strong>PLEASE</strong> verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject'                    => 'Caught an error in Firefly III',
    'error_intro'                      => 'Firefly III v:version ran into an error: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'Ошибка типа ":class".',
    'error_timestamp'                  => 'Ошибка произошла в: :time.',
    'error_location'                   => 'This error occurred in file "<span style="font-family: monospace;">:file</span>" on line :line with code :code.',
    'error_user'                       => 'The error was encountered by user #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'There was no user logged in for this error or no user was detected.',
    'error_ip'                         => 'IP адрес, связанный с этой ошибкой: :ip',
    'error_url'                        => 'URL-адрес: :url',
    'error_user_agent'                 => 'User agent: :userAgent',
    'error_stacktrace'                 => 'The full stacktrace is below. If you think this is a bug in Firefly III, you can forward this message to <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a>. This can help fix the bug you just encountered.',
    'error_github_html'                => 'If you prefer, you can also open a new issue on <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'If you prefer, you can also open a new issue on https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'Полная трассировки стека:',

    // report new journals
    'new_journals_subject'             => 'Firefly III создал новую транзакцию|Firefly III создал :count новых транзакций',
    'new_journals_header'              => 'Firefly III создал для вас транзакцию. Вы можете найти её в вашей установке Firefly III: |Firefly III создал для вас :count транзакций. Вы можете найти их в вашей установке Firefly III:',
];

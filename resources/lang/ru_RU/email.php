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
    'access_token_created_explanation' => 'С этим токеном они могут получить доступ к <strong>всем</strong> вашим финансовым картам через Firefly III API.',
    'access_token_created_revoke'      => 'Если это были не вы, пожалуйста, отмените этот токен как можно скорее по адресу :url.',

    // registered
    'registered_subject'               => 'Добро пожаловать в Firefly III!',
    'registered_welcome'               => 'Добро пожаловать в <a style="color:#337ab7" href=":address">Firefly III</a>. Вы успешно зарегистрированы, и это письмо отправлено для подтверждения регистрации. Ура!',
    'registered_pw'                    => 'Если вы уже забыли свой пароль, пожалуйста, сбросьте его <a style="color:#337ab7" href=":address/password/reset">с помощью инструмента сброса пароля</a>.',
    'registered_help'                  => 'В верхнем правом углу страницы есть иконка справки. Если вам нужна помощь, нажмите её!',
    'registered_doc_html'              => 'Если вы ещё этого не сделали, прочтите <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/grand-theory">грандиозную теорию</a>.',
    'registered_doc_text'              => 'Если вы еще этого не сделали, прочтите краткое руководство по использованию и полное описание.',
    'registered_closing'               => 'Наслаждайтесь!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Сбросить пароль:',
    'registered_doc_link'              => 'Документация:',

    // email change
    'email_change_subject'             => 'Ваш адрес электронной почты Firefly III был изменен',
    'email_change_body_to_new'         => 'Вы или кто-то, у кого есть доступ к вашей учетной записи Firefly III, изменил ваш адрес электронной почты. Если вы не ожидали этого сообщения, проигнорируйте и удалите его.',
    'email_change_body_to_old'         => 'Вы или кто-то, у кого есть доступ к вашей учетной записи Firefly III, изменили ваш адрес электронной почты. Если вы не ожидали, что это произошло, вы <strong>должны</strong> перейти по ссылке "Отменить" ниже, чтобы защитить свой аккаунт!',
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
    'oauth_created_explanation'        => 'С этим клиентом они могут получить доступ к <strong>всем</strong> вашим финансовым картам через Firefly III API.',
    'oauth_created_undo'               => 'Если это были не вы, пожалуйста, отмените этот клиент как можно скорее по адресу :url.',

    // reset password
    'reset_pw_subject'                 => 'Ваш запрос на сброс пароля',
    'reset_pw_instructions'            => 'Кто-то пытался сбросить ваш пароль. Если это вы, пожалуйста, перейдите по ссылке ниже, чтобы сделать это.',
    'reset_pw_warning'                 => '<strong>ПОЖАЛУЙСТА</strong> убедитесь, что ссылка действительно ведён Firefly III, как вы и ожидаете!',

    // error
    'error_subject'                    => 'Найдена ошибка в Firefly III',
    'error_intro'                      => 'В Firefly III v:version произошла ошибка: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'Ошибка типа ":class".',
    'error_timestamp'                  => 'Ошибка произошла в: :time.',
    'error_location'                   => 'Эта ошибка произошла в файле <span style="font-family: monospace;">:file</span> в строке :line с кодом :code.',
    'error_user'                       => 'У пользователя #:id произошла ошибка, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'Пользователь не авторизован из-за этой ошибки или пользователь не был обнаружен.',
    'error_ip'                         => 'IP адрес, связанный с этой ошибкой: :ip',
    'error_url'                        => 'URL-адрес: :url',
    'error_user_agent'                 => 'User agent: :userAgent',
    'error_stacktrace'                 => 'Полный stacktrace находится ниже. Если вы считаете, что это ошибка в Firefly III, вы можете направить это сообщение на <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii. rg</a>. Это может помочь исправить ошибку, с которой вы столкнулись.',
    'error_github_html'                => 'Если вы предпочитаете, вы также можете новый запрос на <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'Если вы предпочитаете, вы также можете открыть новый вопрос на https://github.com/firefly-iii/firefly-iii/issues.',
    'error_stacktrace_below'           => 'Полная трассировки стека:',

    // report new journals
    'new_journals_subject'             => 'Firefly III создал новую транзакцию|Firefly III создал :count новых транзакций',
    'new_journals_header'              => 'Firefly III создал для вас транзакцию. Вы можете найти её в вашей установке Firefly III: |Firefly III создал для вас :count транзакций. Вы можете найти их в вашей установке Firefly III:',
];

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
    'greeting'                                => 'Привіт,',
    'closing'                                 => 'Біп боп,',
    'signature'                               => 'Поштовий робот Firefly III',
    'footer_ps'                               => 'PS: це повідомлення було надіслано, оскільки надійшов запит з IP :ipAddress.',

    // admin test
    'admin_test_subject'                      => 'Тестове повідомлення з вашої інсталяції Firefly III',
    'admin_test_body'                         => 'Це тестове повідомлення з вашого екземпляра Firefly III. Воно було надіслано на :email.',

    // invite
    'invitation_created_subject'              => 'An invitation has been created',
    'invitation_created_body'                 => 'Admin user ":email" created a user invitation which can be used by whoever is behind email address ":invitee". The invite will be valid for 48hrs.',
    'invite_user_subject'                     => 'You\'ve been invited to create a Firefly III account.',
    'invitation_introduction'                 => 'You\'ve been invited to create a Firefly III account on **:host**. Firefly III  is a personal, self-hosted, private personal finance manager. All the cool kids are using it.',
    'invitation_invited_by'                   => 'You\'ve been invited by ":admin" and this invitation was sent to ":invitee". That\'s you, right?',
    'invitation_url'                          => 'The invitation is valid for 48 hours and can be redeemed by surfing to [Firefly III](:url). Enjoy!',

    // new IP
    'login_from_new_ip'                       => 'Новий вхід в Firefly III',
    'slack_login_from_new_ip'                 => 'New Firefly III login from IP :ip (:host)',
    'new_ip_body'                             => 'Firefly III зафіксував новий вхід в ваш аккаунт з невідомої IP-адреси. Якщо ви не авторизовувались в системі з IP-адреси, вказаної нижче, або з часу останньої авторизації пройшло більше шести місяців, Firefly III попередить вас.',
    'new_ip_warning'                          => 'Якщо ви впізнаєте цю IP-адресу або цей логін, ви можете проігнорувати це повідомлення. Якщо ви не увійшли, якщо не маєте жодного уявлення про те, що відбувається, перевірте чи в безпеці ваш пароль, змініть його та закрийте всі інші сесії. Для цього перейдіть на сторінку вашого профілю. Звичайно, ви вже увімкнули двофакторну авторизацію, чи не так? Залишайтеся у безпеці!',
    'ip_address'                              => 'IP-адреса',
    'host_name'                               => 'Хост',
    'date_time'                               => 'Дата + час',

    // access token created
    'access_token_created_subject'            => 'Створено новий токен доступу',
    'access_token_created_body'               => 'Хтось (сподіваюся, ви) щойно створив новий токен доступу Firefly III API для вашого облікового запису.',
    'access_token_created_explanation'        => 'With this token, they can access **all** of your financial records through the Firefly III API.',
    'access_token_created_revoke'             => 'If this wasn\'t you, please revoke this token as soon as possible at :url',

    // registered
    'registered_subject'                      => 'Вітаємо у Firefly III!',
    'registered_subject_admin'                => 'A new user has registered',
    'admin_new_user_registered'               => 'A new user has registered. User **:email** was given user ID #:id.',
    'registered_welcome'                      => 'Welcome to [Firefly III](:address). Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_pw'                           => 'If you have forgotten your password already, please reset it using [the password reset tool](:address/password/reset).',
    'registered_help'                         => 'There is a help-icon in the top right corner of each page. If you need help, click it!',
    'registered_doc_html'                     => 'If you haven\'t already, please read the [grand theory](https://docs.firefly-iii.org/about-firefly-iii/personal-finances).',
    'registered_doc_text'                     => 'If you haven\'t already, please also read the first use guide and the full description.',
    'registered_closing'                      => 'Насолоджуйтесь!',
    'registered_firefly_iii_link'             => 'Firefly III:',
    'registered_pw_reset_link'                => 'Зміна пароля:',
    'registered_doc_link'                     => 'Документація:',

    // new version
    'new_version_email_subject'               => 'A new Firefly III version is available',

    // email change
    'email_change_subject'                    => 'Your Firefly III email address has changed',
    'email_change_body_to_new'                => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this message, please ignore and delete it.',
    'email_change_body_to_old'                => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen, you **must** follow the "undo"-link below to protect your account!',
    'email_change_ignore'                     => 'If you initiated this change, you may safely ignore this message.',
    'email_change_old'                        => 'The old email address was: :email',
    'email_change_old_strong'                 => 'The old email address was: **:email**',
    'email_change_new'                        => 'The new email address is: :email',
    'email_change_new_strong'                 => 'The new email address is: **:email**',
    'email_change_instructions'               => 'You cannot use Firefly III until you confirm this change. Please follow the link below to do so.',
    'email_change_undo_link'                  => 'To undo the change, follow this link:',

    // OAuth token created
    'oauth_created_subject'                   => 'A new OAuth client has been created',
    'oauth_created_body'                      => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL `:url`.',
    'oauth_created_explanation'               => 'With this client, they can access **all** of your financial records through the Firefly III API.',
    'oauth_created_undo'                      => 'If this wasn\'t you, please revoke this client as soon as possible at `:url`',

    // reset password
    'reset_pw_subject'                        => 'Your password reset request',
    'reset_pw_instructions'                   => 'Somebody tried to reset your password. If it was you, please follow the link below to do so.',
    'reset_pw_warning'                        => '**PLEASE** verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject'                           => 'Caught an error in Firefly III',
    'error_intro'                             => 'Firefly III v:version ran into an error: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                              => 'The error was of type ":class".',
    'error_timestamp'                         => 'The error occurred on/at: :time.',
    'error_location'                          => 'This error occurred in file "<span style="font-family: monospace;">:file</span>" on line :line with code :code.',
    'error_user'                              => 'The error was encountered by user #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                           => 'There was no user logged in for this error or no user was detected.',
    'error_ip'                                => 'The IP address related to this error is: :ip',
    'error_url'                               => 'URL-адреса: :url',
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

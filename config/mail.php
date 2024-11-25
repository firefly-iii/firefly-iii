<?php

/**
 * mail.php
 * Copyright (c) 2019 james@firefly-iii.org.
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
    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    */
    'default'  => envNonEmpty('MAIL_MAILER', 'log'),

    'mailers'  => [
        'smtp'       => [
            'transport'   => 'smtp',
            'host'        => envNonEmpty('MAIL_HOST', 'smtp.mailtrap.io'),
            'port'        => (int)env('MAIL_PORT', 2525),
            'encryption'  => envNonEmpty('MAIL_ENCRYPTION', 'tls'),
            'username'    => envNonEmpty('MAIL_USERNAME', 'user@example.com'),
            'password'    => envNonEmpty('MAIL_PASSWORD', 'password'),
            'timeout'     => null,
            'verify_peer' => null !== env('MAIL_ENCRYPTION'),
        ],
        'mailersend' => [
            'transport' => 'mailersend',
        ],
        'ses'        => [
            'transport' => 'ses',
        ],

        'mailgun'    => [
            'transport' => 'mailgun',
        ],

        'mandrill'   => [
            'transport' => 'mandrill',
        ],

        'postmark'   => [
            'transport' => 'postmark',
        ],

        'sendmail'   => [
            'transport' => 'sendmail',
            'path'      => envNonEmpty('MAIL_SENDMAIL_COMMAND', '/usr/sbin/sendmail -bs'),
        ],
        'log'        => [
            'transport' => 'log',
            'channel'   => env('MAIL_LOG_CHANNEL', 'stack'),
            'level'     => 'info',
        ],
        'null'       => [
            'transport' => 'log',
            'channel'   => env('MAIL_LOG_CHANNEL', 'stack'),
            'level'     => 'notice',
        ],

        'array'      => [
            'transport' => 'array',
        ],
    ],

    'from'     => ['address' => envNonEmpty('MAIL_FROM', 'changeme@example.com'), 'name' => 'Firefly III Mailer'],
    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],
];

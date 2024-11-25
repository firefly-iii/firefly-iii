<?php

/**
 * logging.php
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

use FireflyIII\Support\Logging\AuditLogger;
use Monolog\Handler\SyslogUdpHandler;

// standard config for both log things:
$defaultChannels    = ['daily', 'stdout'];
$auditChannels      = ['audit_daily', 'audit_stdout'];

// validChannels is missing 'stack' because we already check for that one.
$validChannels      = ['single', 'papertrail', 'stdout', 'daily', 'syslog', 'errorlog'];
$validAuditChannels = ['audit_papertrail', 'audit_stdout', 'audit_stdout', 'audit_daily', 'audit_syslog', 'audit_errorlog'];

// which settings did the user set, if any?
$defaultLogChannel  = (string)envNonEmpty('LOG_CHANNEL', 'stack');
$auditLogChannel    = (string)envNonEmpty('AUDIT_LOG_CHANNEL', '');

if ('stack' === $defaultLogChannel) {
    $defaultChannels = ['daily', 'stdout'];
}
if (in_array($defaultLogChannel, $validChannels, true)) {
    $defaultChannels = [$defaultLogChannel];
}

if (in_array($auditLogChannel, $validAuditChannels, true)) {
    $auditChannels = [$auditLogChannel];
}

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default'  => envNonEmpty('LOG_CHANNEL', 'stack'),
    'level'    => envNonEmpty('APP_LOG_LEVEL', 'info'),
    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "custom", "stack"
    |
    */

    'channels' => [
        /*
         * 'stack' and 'audit' are the two "generic" channels that
         * are valid destinations for logs.
         */
        'stack'            => [
            'driver'   => 'stack',
            'channels' => $defaultChannels,
        ],
        'audit'            => [
            'driver'   => 'stack',
            'channels' => $auditChannels,
        ],
        // There are 6 valid destinations for the normal logs, listed below:
        'single'           => [
            'driver' => 'single',
            'path'   => storage_path('logs/laravel.log'),
            'level'  => envNonEmpty('APP_LOG_LEVEL', 'info'),
        ],
        'papertrail'       => [
            'driver'       => 'monolog',
            'level'        => envNonEmpty('APP_LOG_LEVEL', 'info'),
            'handler'      => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_HOST'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],
        'stdout'           => [
            'driver' => 'single',
            'path'   => 'php://stdout',
            'level'  => envNonEmpty('APP_LOG_LEVEL', 'info'),
        ],
        'daily'            => [
            'driver' => 'daily',
            'path'   => storage_path('logs/ff3-'.PHP_SAPI.'.log'),
            'level'  => envNonEmpty('APP_LOG_LEVEL', 'info'),
            'days'   => 7,
        ],
        'syslog'           => [
            'driver' => 'syslog',
            'level'  => envNonEmpty('APP_LOG_LEVEL', 'info'),
        ],
        'errorlog'         => [
            'driver' => 'errorlog',
            'level'  => envNonEmpty('APP_LOG_LEVEL', 'info'),
        ],

        /*
         * There are 5 valid destinations for the audit logs, listed below.
         * The only one missing is "single".
         */
        'audit_papertrail' => [
            'driver'       => 'monolog',
            'level'        => envNonEmpty('AUDIT_LOG_LEVEL', 'info'),
            'handler'      => SyslogUdpHandler::class,
            'tap'          => [AuditLogger::class],
            'handler_with' => [
                'host' => env('PAPERTRAIL_HOST'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],
        'audit_stdout'     => [
            'driver' => 'single',
            'path'   => 'php://stdout',
            'tap'    => [AuditLogger::class],
            'level'  => envNonEmpty('AUDIT_LOG_LEVEL', 'info'),
        ],
        'audit_daily'      => [
            'driver' => 'daily',
            'path'   => storage_path('logs/ff3-audit.log'),
            'tap'    => [AuditLogger::class],
            'level'  => envNonEmpty('AUDIT_LOG_LEVEL', 'info'),
            'days'   => 90,
        ],
        'audit_syslog'     => [
            'driver' => 'syslog',
            'tap'    => [AuditLogger::class],
            'level'  => envNonEmpty('AUDIT_LOG_LEVEL', 'info'),
        ],
        'audit_errorlog'   => [
            'driver' => 'errorlog',
            'tap'    => [AuditLogger::class],
            'level'  => envNonEmpty('AUDIT_LOG_LEVEL', 'info'),
        ],
    ],
];

<?php
/**
 * logging.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

    'default' => envNonEmpty('LOG_CHANNEL', 'daily'),

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
        'stack' => [
            'driver'   => 'stack',
            'channels' => ['daily', 'slack'],
        ],

        'single'    => [
            'driver' => 'single',
            'path'   => storage_path('logs/laravel.log'),
            'level'  => envNonEmpty('APP_LOG_LEVEL', 'info'),
        ],
        'stdout'    => [
            'driver' => 'single',
            'path'   => 'php://stdout',
            'level'  => envNonEmpty('APP_LOG_LEVEL', 'info'),
        ],
        'docker_out'    => [
            'driver' => 'single',
            'path'   => 'php://stdout',
            'level'  => envNonEmpty('APP_LOG_LEVEL', 'info'),
        ],
        'daily'     => [
            'driver' => 'daily',
            'path'   => storage_path('logs/ff3-' . PHP_SAPI . '.log'),
            'level'  => envNonEmpty('APP_LOG_LEVEL', 'info'),
            'days'   => 7,
        ],
        'audit'     => [
            'driver' => 'daily',
            'path'   => storage_path('logs/ff3-audit.log'),
            'tap'    => [AuditLogger::class],
            'level'  => 'info',
            'days'   => 90,
        ],
        'dailytest' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/test-ff3-' . PHP_SAPI . '.log'),
            'level'  => envNonEmpty('APP_LOG_LEVEL', 'info'),
            'days'   => 7,
        ],

        'slack' => [
            'driver'   => 'slack',
            'url'      => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Firefly III Log Robot',
            'emoji'    => ':boom:',
            'level'    => 'error',
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level'  => envNonEmpty('APP_LOG_LEVEL', 'info'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level'  => envNonEmpty('APP_LOG_LEVEL', 'info'),
        ],
    ],

];

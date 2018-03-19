<?php
/**
 * logging.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

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

    'default' => env('LOG_CHANNEL', 'daily'),

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
            'channels' => ['single'],
        ],

        'single' => [
            'driver' => 'single',
            'path'   => storage_path('logs/laravel.log'),
            'level'  => 'debug',
        ],

        'daily'     => [
            'driver' => 'daily',
            'path'   => storage_path('logs/ff3-' . php_sapi_name() . '.log'),
            'level'  => env('APP_LOG_LEVEL', 'info'),
            'days'   => 7,
        ],
        'dailytest' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/test-ff3-' . php_sapi_name() . '.log'),
            'level'  => env('APP_LOG_LEVEL', 'info'),
            'days'   => 7,
        ],

        'slack' => [
            'driver'   => 'slack',
            'url'      => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji'    => ':boom:',
            'level'    => 'critical',
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level'  => env('APP_LOG_LEVEL', 'info'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level'  => env('APP_LOG_LEVEL', 'info'),
        ],
    ],

];

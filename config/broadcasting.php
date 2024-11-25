<?php

/**
 * broadcasting.php
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
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "ably", "redis", "log", "null"
    |
    */

    'default'     => env('BROADCAST_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [
        'pusher' => [
            'driver'         => 'pusher',
            'key'            => env('PUSHER_APP_KEY'),
            'secret'         => env('PUSHER_APP_SECRET'),
            'app_id'         => env('PUSHER_APP_ID'),
            'options'        => [
                'cluster'   => env('PUSHER_APP_CLUSTER'),
                'host'      => null !== env('PUSHER_HOST') ? env('PUSHER_HOST') : 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port'      => env('PUSHER_PORT', 443),
                'scheme'    => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS'    => 'https' === env('PUSHER_SCHEME', 'https'),
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'ably'   => [
            'driver' => 'ably',
            'key'    => env('ABLY_KEY'),
        ],

        'redis'  => [
            'driver'     => 'redis',
            'connection' => 'default',
        ],

        'log'    => [
            'driver' => 'log',
        ],

        'null'   => [
            'driver' => 'null',
        ],
    ],
];

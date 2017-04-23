<?php
/**
 * session.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);


return [
    'driver'          => env('SESSION_DRIVER', 'file'),
    'lifetime'        => 120,
    'expire_on_close' => false,
    'encrypt'         => true,
    'files'           => storage_path('framework/sessions'),
    'connection'      => null,
    'table'           => 'sessions',
    'store'           => null,
    'lottery'         => [2, 100],
    'cookie'          => 'firefly_session',
    'path'            => env('COOKIE_PATH', '/'),
    'domain'          => env('COOKIE_DOMAIN', null),
    'secure'          => env('COOKIE_SECURE', false),
    'http_only'       => true,
];

<?php

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
    'same_site'       => null,
];

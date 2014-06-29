<?php

return [
    'driver'     => 'file',
    'path'       => storage_path() . '/cache',
    'connection' => null,
    'table'      => 'cache',
    'memcached'  => [
        ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],
    ],
    'prefix'     => 'laravel',
];

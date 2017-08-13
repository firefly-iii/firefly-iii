<?php
declare(strict_types=1);

/**
 * database.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

// for heroku:
$databaseUrl = getenv('DATABASE_URL');
$host        = '';
$username    = '';
$password    = '';
$database    = '';

if (!($databaseUrl === false)) {
    $options  = parse_url($databaseUrl);
    $host     = $options['host'];
    $username = $options['user'];
    $password = $options['pass'];
    $database = substr($options['path'], 1);
}

return [

    'fetch'       => PDO::FETCH_OBJ,
    'default'     => env('DB_CONNECTION', 'pgsql'),
    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE', storage_path('database/database.sqlite')),
            'prefix'   => '',
        ],

        'mysql' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', 'localhost'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'forge'),
            'username'  => env('DB_USERNAME', 'forge'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ],

        'pgsql' => [
            'driver'   => 'pgsql',
            'host'     => env('DB_HOST', $host),
            'port'     => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', $database),
            'username' => env('DB_USERNAME', $username),
            'password' => env('DB_PASSWORD', $password),
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
            'sslmode'  => 'prefer',
        ],

    ],
    'migrations'  => 'migrations',
    'redis'       => [
        'cluster' => false,
        'default' => [
            'host'     => env('REDIS_HOST', 'localhost'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => 0,
        ],
    ],
];

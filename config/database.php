<?php

/**
 * database.php
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

use function Safe\parse_url;

$databaseUrl = getenv('DATABASE_URL');
$host        = '';
$username    = '';
$password    = '';
$database    = '';
$port        = '';

if (false !== $databaseUrl) {
    $options  = parse_url($databaseUrl);
    $host     = $options['host'] ?? 'firefly_iii_db';
    $username = $options['user'] ?? 'firefly';
    $port     = $options['port'] ?? '5432';
    $password = $options['pass'] ?? 'secret_firefly_password';
    $database = substr($options['path'] ?? '/firefly', 1);
}

// Get SSL parameters from .env file.
$mysql_ssl_ca_dir  = env('MYSQL_SSL_CAPATH');
$mysql_ssl_ca_file = env('MYSQL_SSL_CA');
$mysql_ssl_cert    = env('MYSQL_SSL_CERT');
$mysql_ssl_key     = env('MYSQL_SSL_KEY');
$mysql_ssl_ciphers = env('MYSQL_SSL_CIPHER');
$mysql_ssl_verify  = env('MYSQL_SSL_VERIFY_SERVER_CERT');

$mySqlSSLOptions = [];
$useSSL          = envDefaultWhenEmpty(env('MYSQL_USE_SSL'), false);
if (false !== $useSSL && null !== $useSSL && '' !== $useSSL) {
    if (null !== $mysql_ssl_ca_dir) {
        $mySqlSSLOptions[PDO::MYSQL_ATTR_SSL_CAPATH] = $mysql_ssl_ca_dir;
    }
    if (null !== $mysql_ssl_ca_file) {
        $mySqlSSLOptions[PDO::MYSQL_ATTR_SSL_CA] = $mysql_ssl_ca_file;
    }
    if (null !== $mysql_ssl_cert) {
        $mySqlSSLOptions[PDO::MYSQL_ATTR_SSL_CERT] = $mysql_ssl_cert;
    }
    if (null !== $mysql_ssl_key) {
        $mySqlSSLOptions[PDO::MYSQL_ATTR_SSL_KEY] = $mysql_ssl_key;
    }
    if (null !== $mysql_ssl_ciphers) {
        $mySqlSSLOptions[PDO::MYSQL_ATTR_SSL_CIPHER] = $mysql_ssl_ciphers;
    }
    if (null !== $mysql_ssl_verify) {
        $mySqlSSLOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $mysql_ssl_verify;
    }
}

return [
    'default'     => envDefaultWhenEmpty(env('DB_CONNECTION'), 'mysql'),
    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => envDefaultWhenEmpty(env('DB_DATABASE'), storage_path('database/database.sqlite')),
            'prefix'   => '',
        ],
        'mysql'  => [
            'driver'      => 'mysql',
            'host'        => envDefaultWhenEmpty(env('DB_HOST'), $host),
            'port'        => envDefaultWhenEmpty(env('DB_PORT'), $port),
            'database'    => envDefaultWhenEmpty(env('DB_DATABASE'), $database),
            'username'    => envDefaultWhenEmpty(env('DB_USERNAME'), $username),
            'password'    => env('DB_PASSWORD', $password),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_unicode_ci',
            'prefix'      => '',
            'strict'      => true,
            'engine'      => 'InnoDB',
            'options'     => $mySqlSSLOptions,
        ],
        'pgsql'  => [
            'driver'      => 'pgsql',
            'host'        => envDefaultWhenEmpty(env('DB_HOST'), $host),
            'port'        => envDefaultWhenEmpty(env('DB_PORT'), $port),
            'database'    => envDefaultWhenEmpty(env('DB_DATABASE'), $database),
            'username'    => envDefaultWhenEmpty(env('DB_USERNAME'), $username),
            'password'    => env('DB_PASSWORD', $password),
            'charset'     => 'utf8',
            'prefix'      => '',
            'search_path' => envDefaultWhenEmpty(env('PGSQL_SCHEMA'), 'public'),
            'schema'      => envDefaultWhenEmpty(env('PGSQL_SCHEMA'), 'public'),
            'sslmode'     => envDefaultWhenEmpty(env('PGSQL_SSL_MODE'), 'prefer'),
            'sslcert'     => env('PGSQL_SSL_CERT'),
            'sslkey'      => env('PGSQL_SSL_KEY'),
            'sslrootcert' => env('PGSQL_SSL_ROOT_CERT'),
        ],
        'sqlsrv' => [
            'driver'   => 'sqlsrv',
            'host'     => env('DB_HOST', 'localhost'),
            'port'     => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => '',
        ],
    ],
    'migrations'  => 'migrations',
    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */
    'redis'       => [
        'client'  => env('REDIS_CLIENT', 'predis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'predis'),
            // 'prefix'  => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
        ],
        'default' => [
            'scheme'   => envDefaultWhenEmpty(env('REDIS_SCHEME'), 'tcp'),
            'url'      => env('REDIS_URL'),
            'path'     => env('REDIS_PATH'),
            'host'     => envDefaultWhenEmpty(env('REDIS_HOST'), '127.0.0.1'),
            'port'     => envDefaultWhenEmpty(env('REDIS_PORT'), 6379),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache'   => [
            'scheme'   => envDefaultWhenEmpty(env('REDIS_SCHEME'), 'tcp'),
            'url'      => env('REDIS_URL'),
            'path'     => env('REDIS_PATH'),
            'host'     => envDefaultWhenEmpty(env('REDIS_HOST'), '127.0.0.1'),
            'port'     => envDefaultWhenEmpty(env('REDIS_PORT'), 6379),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];

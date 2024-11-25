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

$databaseUrl       = getenv('DATABASE_URL');
$host              = '';
$username          = '';
$password          = '';
$database          = '';
$port              = '';

if (false !== $databaseUrl) {
    $options  = parse_url($databaseUrl);
    $host     = $options['host'] ?? 'firefly_iii_db';
    $username = $options['user'] ?? 'firefly';
    $port     = $options['port'] ?? '5432';
    $password = $options['pass'] ?? 'secret_firefly_password';
    $database = substr($options['path'] ?? '/firefly', 1);
}

// Get SSL parameters from .env file.
$mysql_ssl_ca_dir  = envNonEmpty('MYSQL_SSL_CAPATH', null);
$mysql_ssl_ca_file = envNonEmpty('MYSQL_SSL_CA', null);
$mysql_ssl_cert    = envNonEmpty('MYSQL_SSL_CERT', null);
$mysql_ssl_key     = envNonEmpty('MYSQL_SSL_KEY', null);
$mysql_ssl_ciphers = envNonEmpty('MYSQL_SSL_CIPHER', null);
$mysql_ssl_verify  = envNonEmpty('MYSQL_SSL_VERIFY_SERVER_CERT', null);

$mySqlSSLOptions   = [];
$useSSL            = envNonEmpty('MYSQL_USE_SSL', false);
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
    'default'     => envNonEmpty('DB_CONNECTION', 'mysql'),
    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => envNonEmpty('DB_DATABASE', storage_path('database/database.sqlite')),
            'prefix'   => '',
        ],
        'mysql'  => [
            'driver'      => 'mysql',
            'host'        => envNonEmpty('DB_HOST', $host),
            'port'        => envNonEmpty('DB_PORT', $port),
            'database'    => envNonEmpty('DB_DATABASE', $database),
            'username'    => envNonEmpty('DB_USERNAME', $username),
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
            'host'        => envNonEmpty('DB_HOST', $host),
            'port'        => envNonEmpty('DB_PORT', $port),
            'database'    => envNonEmpty('DB_DATABASE', $database),
            'username'    => envNonEmpty('DB_USERNAME', $username),
            'password'    => env('DB_PASSWORD', $password),
            'charset'     => 'utf8',
            'prefix'      => '',
            'search_path' => envNonEmpty('PGSQL_SCHEMA', 'public'),
            'schema'      => envNonEmpty('PGSQL_SCHEMA', 'public'),
            'sslmode'     => envNonEmpty('PGSQL_SSL_MODE', 'prefer'),
            'sslcert'     => envNonEmpty('PGSQL_SSL_CERT'),
            'sslkey'      => envNonEmpty('PGSQL_SSL_KEY'),
            'sslrootcert' => envNonEmpty('PGSQL_SSL_ROOT_CERT'),
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
            'scheme'   => envNonEmpty('REDIS_SCHEME', 'tcp'),
            'url'      => envNonEmpty('REDIS_URL'),
            'path'     => envNonEmpty('REDIS_PATH'),
            'host'     => envNonEmpty('REDIS_HOST', '127.0.0.1'),
            'port'     => envNonEmpty('REDIS_PORT', 6379),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD', null),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache'   => [
            'scheme'   => envNonEmpty('REDIS_SCHEME', 'tcp'),
            'url'      => envNonEmpty('REDIS_URL'),
            'path'     => envNonEmpty('REDIS_PATH'),
            'host'     => envNonEmpty('REDIS_HOST', '127.0.0.1'),
            'port'     => envNonEmpty('REDIS_PORT', 6379),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD', null),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];

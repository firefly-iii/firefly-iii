<?php

return [

    'default'     => 'mysql',
    'connections' => [

        'mysql' => [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'homestead',
            'username'  => 'homestead',
            'password'  => 'secret',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ],
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => realpath(__DIR__.'/../../../tests/_data/testing.sqlite'),
            'prefix'   => ''
        ],

        'pgsql' => [
            'driver'   => 'pgsql',
            'host'     => 'localhost',
            'database' => 'homestead',
            'username' => 'homestead',
            'password' => 'secret',
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
        ],

    ],

];
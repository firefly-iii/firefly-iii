<?php
declare(strict_types = 1);


return [

    'allow_register' => true,
    'defaults'       => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],
    'guards'         => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver'   => 'token',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => FireflyIII\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'email'    => 'emails.password',
            'table'    => 'password_resets',
            'expire'   => 60,
        ],
    ],

];

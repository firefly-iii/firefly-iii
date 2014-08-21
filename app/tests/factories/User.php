<?php
use League\FactoryMuffin\Facade;

Facade::define(
    'User',
    [
        'email'          => 'safeEmail',
        'password'       => function () {
                return \Str::random(60);
            },
        'reset'          => function () {
                return \Str::random(32);
            },
        'remember_token' => null,
        'migrated'       => 'boolean'
    ]
);
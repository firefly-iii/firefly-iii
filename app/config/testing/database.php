<?php
return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => realpath(__DIR__.'/../../../tests/_data/db.sqlite'),
            'prefix'   => ''
        ]

    ]
];

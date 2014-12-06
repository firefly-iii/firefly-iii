<?php
return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => 'tests/_data/testing.sqlite',
            'prefix'   => ''
        ]

    ]
];
<?php
return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        ]

    ]
];

<?php

return [
    'default'     => 'production',
    'connections' => [

        'production' => [
            'host'      => '',
            'username'  => '',
            'password'  => '',
            'key'       => '',
            'keyphrase' => '',
            'root'      => '/var/www',
        ],
    ],
    'groups'      => [
        'web' => ['production']

    ],

];

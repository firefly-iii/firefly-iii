<?php

return [
    'default'     => 'sync',
    'connections' => [

        'sync'       => [
            'driver' => 'sync',
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host'   => 'localhost',
            'queue'  => 'default',
            'ttr'    => 60,
        ],

        'sqs'        => [
            'driver' => 'sqs',
            'key'    => 'your-public-key',
            'secret' => 'your-secret-key',
            'queue'  => 'your-queue-url',
            'region' => 'us-east-1',
        ],

        'iron'       => [
            'driver'  => 'iron',
            'host'    => 'mq-aws-us-east-1.iron.io',
            'token'   => 'your-token',
            'project' => 'your-project-id',
            'queue'   => 'your-queue-name',
            'encrypt' => true,
        ],

        'redis'      => [
            'driver' => 'redis',
            'queue'  => 'default',
        ],

    ],
    'failed'      => [
        'database' => 'mysql', 'table' => 'failed_jobs',
    ],

];

<?php

return [
    'driver'     => 'smtp',
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'from'       => ['address' => 'empty@example.com', 'name' => 'Firefly III'],
    'encryption' => 'tls',
    'username'   => 'empty@example.com',
    'password'   => '',
    'sendmail'   => '/usr/sbin/sendmail -bs',
    'pretend'    => false,
];

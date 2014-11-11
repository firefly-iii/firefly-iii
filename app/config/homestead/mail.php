<?php

return [
    'driver'     => 'smtp',
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'from'       => ['address' => '@gmail.com', 'name' => 'Firefly III'],
    'encryption' => 'tls',
    'username'   => '@gmail.com',
    'password'   => '',
    'sendmail'   => '/usr/sbin/sendmail -bs',
    'pretend'    => false,
];

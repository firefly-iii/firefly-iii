<?php

return [
    'driver'     => 'smtp',
    'host'       => '',
    'port'       => 587,
    'from'       => ['address' => '', 'name' => 'Firefly III'],
    'encryption' => 'tls',
    'username'   => '',
    'password'   => '',
    'sendmail'   => '/usr/sbin/sendmail -bs',
    'pretend'    => true,
];

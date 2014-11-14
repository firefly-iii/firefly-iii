<?php

return [
    'driver'     => 'smtp',
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'from'       => ['address' => 'thegrumpydictator@gmail.com', 'name' => 'Firefly III'],
    'encryption' => 'tls',
    'username'   => 'thegrumpydictator@gmail.com',
    'password'   => 'eyp-ort-ab-ig-york-ig-e-kne',
    'sendmail'   => '/usr/sbin/sendmail -bs',
    'pretend'    => false,
];

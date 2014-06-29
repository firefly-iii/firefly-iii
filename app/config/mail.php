<?php

return [
    'driver'     => 'smtp',
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'from'       => ['address' => 'nder.firefly@gmail.com', 'name' => 'Firefly V'],
    'encryption' => 'tls',
    'username'   => 'nder.firefly@gmail.com',
    'password'   => 'bzQj252LqefJnorN28dLzph7oNclXNEV986mjX',
    'sendmail'   => '/usr/sbin/sendmail -bs',
    'pretend'    => false,
];

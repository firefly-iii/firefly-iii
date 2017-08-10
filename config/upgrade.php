<?php
/**
 * upgrade.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

return [
    'text' => [
        'upgrade' =>
            [
                '4.3'   => 'Make sure you run the migrations and clear your cache. If you need more help, please check Github or the Firefly III website.',
                '4.6.3' => 'This will be the last version to require PHP7.0. Future versions will require PHP7.1 minimum.',
            ],
        'install' =>
            [
                '4.3'   => 'Welcome to Firefly! Make sure you follow the installation guide. If you need more help, please check Github or the Firefly III website. The installation guide has a FAQ which you should check out as well.',
                '4.6.3' => 'This will be the last version to require PHP7.0. Future versions will require PHP7.1 minimum.',
                '4.6.4' => 'This version of Firefly III requires PHP7.1.'
            ],
    ],
];

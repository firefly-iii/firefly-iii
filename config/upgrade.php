<?php
/**
 * upgrade.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    'text' => [
        'upgrade' =>
            [
                '4.3'   => 'Make sure you run the migrations and clear your cache. If you need more help, please check Github or the Firefly III website.',
                '4.6.3' => 'This will be the last version to require PHP7.0. Future versions will require PHP7.1 minimum.',
                '4.6.4' => 'This version of Firefly III requires PHP7.1.',
            ],
        'install' =>
            [
                '4.3'   => 'Welcome to Firefly! Make sure you follow the installation guide. If you need more help, please check Github or the Firefly III website. The installation guide has a FAQ which you should check out as well.',
                '4.6.3' => 'This will be the last version to require PHP7.0. Future versions will require PHP7.1 minimum.',
                '4.6.4' => 'This version of Firefly III requires PHP7.1.',
            ],
    ],
];

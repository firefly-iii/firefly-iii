<?php
/**
 * session.php
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
    'driver'          => env('SESSION_DRIVER', 'file'),
    'lifetime'        => 10080,
    'expire_on_close' => false,
    'encrypt'         => true,
    'files'           => storage_path('framework/sessions'),
    'connection'      => null,
    'table'           => 'sessions',
    'store'           => null,
    'lottery'         => [2, 100],
    'cookie'          => 'firefly_session',
    'path'            => env('COOKIE_PATH', '/'),
    'domain'          => env('COOKIE_DOMAIN', null),
    'secure'          => env('COOKIE_SECURE', false),
    'http_only'       => true,
    'same_site'       => null,
];

<?php

/**
 * session.php
 * Copyright (c) 2019 james@firefly-iii.org.
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    'driver'          => env('SESSION_DRIVER', 'file'),
    'lifetime'        => 120,
    'expire_on_close' => true,
    'encrypt'         => true,
    'files'           => storage_path('framework/sessions'),
    'connection'      => null,
    'table'           => 'sessions',
    'store'           => null,
    'lottery'         => [2, 100],
    'cookie'          => env('COOKIE_NAME','firefly_iii_session'),
    'path'            => env('COOKIE_PATH', '/'),
    'domain'          => env('COOKIE_DOMAIN', null),
    'secure'          => env('COOKIE_SECURE', null),
    'http_only'       => true,
    'same_site'       => env('COOKIE_SAMESITE', 'lax'),
];

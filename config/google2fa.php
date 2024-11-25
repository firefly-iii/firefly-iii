<?php

/**
 * google2fa.php
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

use PragmaRX\Google2FALaravel\Support\Constants;

return [
    // Auth container binding

    'enabled'              => true,

    /*
     * Lifetime in minutes.
     * In case you need your users to be asked for a new one time passwords from time to time.
     */

    'lifetime'             => 0, // 0 = eternal

    // Renew lifetime at every new request.

    'keep_alive'           => true,

    // Auth container binding

    'auth'                 => 'auth',

    // 2FA verified session var

    'session_var'          => 'google2fa',

    // One Time Password request input name
    'otp_input'            => 'one_time_password',

    // One Time Password Window
    'window'               => 1,

    // Forbid user to reuse One Time Passwords.
    'forbid_old_passwords' => false,

    // User's table column for google2fa secret
    'otp_secret_column'    => 'mfa_secret',

    // One Time Password View
    'view'                 => 'auth.mfa',

    // One Time Password error message
    'error_messages'       => [
        'wrong_otp' => "The 'One Time Password' typed was wrong.",
    ],

    // Throw exceptions or just fire events?
    'throw_exceptions'     => true,

    'store_in_cookie'      => true,

    'qrcode_image_backend' => Constants::QRCODE_IMAGE_BACKEND_SVG,
];

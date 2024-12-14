<?php

/*
 * notifications.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

return [
    'channels'      => [
        'email'      => ['enabled' => true, 'ui_configurable' => 0],
        'slack'      => ['enabled' => true, 'ui_configurable' => 1],
        'ntfy'       => ['enabled' => true, 'ui_configurable' => 1],
        'pushover'   => ['enabled' => true, 'ui_configurable' => 1],
//        'gotify'     => ['enabled' => false, 'ui_configurable' => 0],
//        'pushbullet' => ['enabled' => false, 'ui_configurable' => 0],
    ],
    'notifications' => [
        'user'  => [
            // normal reminders
            'bill_reminder'        => ['enabled' => true, 'configurable' => true],
            'transaction_creation' => ['enabled' => true, 'configurable' => true],
            'rule_action_failures' => ['enabled' => true, 'configurable' => true],

            // security reminders
            'new_access_token'     => ['enabled' => true, 'configurable' => true],
            'user_login'           => ['enabled' => true, 'configurable' => true],
            'login_failure'        => ['enabled' => true, 'configurable' => true],
            'new_password'         => ['enabled' => true, 'configurable' => false],
            'enabled_mfa'          => ['enabled' => true, 'configurable' => false],
            'disabled_mfa'         => ['enabled' => true, 'configurable' => false],
            'few_left_mfa'         => ['enabled' => true, 'configurable' => false],
            'no_left_mfa'          => ['enabled' => true, 'configurable' => false],
            'many_failed_mfa'      => ['enabled' => true, 'configurable' => false],
            'new_backup_codes'     => ['enabled' => true, 'configurable' => false],
        ],
        'owner' => [
            // 'invitation_created' => ['enabled' => true],
            // 'some_notification'  => ['enabled' => true],
            'admin_new_reg'        => ['enabled' => true],
            'user_new_reg'         => ['enabled' => true],
            'new_version'          => ['enabled' => true],
            'invite_created'       => ['enabled' => true],
            'invite_redeemed'      => ['enabled' => true],
            'unknown_user_attempt' => ['enabled' => true],
        ],
    ],
    // // notifications
    //    'available_notifications'      => ['bill_reminder', 'new_access_token', 'transaction_creation', 'user_login', 'rule_action_failures'],
    //    'admin_notifications'          => ['admin_new_reg', 'user_new_reg', 'new_version', 'invite_created', 'invite_redeemed'],
];

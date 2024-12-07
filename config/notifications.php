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
        'email'      => ['enabled' => true, 'ui_configurable' => 0,],
        'slack'      => ['enabled' => true, 'ui_configurable' => 1,],
        'discord'      => ['enabled' => true, 'ui_configurable' => 1,],
        'nfty'       => ['enabled' => false, 'ui_configurable' => 0,],
        'pushover'   => ['enabled' => false, 'ui_configurable' => 0,],
        'gotify'     => ['enabled' => false, 'ui_configurable' => 0,],
        'pushbullet' => ['enabled' => false, 'ui_configurable' => 0,],
    ],
    'notifications' => [
        'user'  => [
            'some_notification' => [
                'enabled' => true,
                'email'   => '',
                'slack'   => '',
            ],
        ],
        'owner' => [
            //'invitation_created' => ['enabled' => true],
            // 'some_notification'  => ['enabled' => true],
            'admin_new_reg'   => ['enabled' => true],
            'user_new_reg'    => ['enabled' => true],
            'new_version'     => ['enabled' => true],
            'invite_created'  => ['enabled' => true],
            'invite_redeemed' => ['enabled' => true],
        ],
    ],
    // // notifications
    //    'available_notifications'      => ['bill_reminder', 'new_access_token', 'transaction_creation', 'user_login', 'rule_action_failures'],
    //    'admin_notifications'          => ['admin_new_reg', 'user_new_reg', 'new_version', 'invite_created', 'invite_redeemed'],
];

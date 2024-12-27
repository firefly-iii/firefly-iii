<?php

/*
 * ReturnsSettings.php
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

namespace FireflyIII\Notifications;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;

class ReturnsSettings
{
    public static function getSettings(string $channel, string $type, ?User $user): array
    {
        if ('ntfy' === $channel) {
            return self::getNtfySettings($type, $user);
        }

        throw new FireflyException(sprintf('Cannot handle channel "%s"', $channel));
    }

    private static function getNtfySettings(string $type, ?User $user)
    {
        $settings = [
            'ntfy_server' => 'https://ntfy.sh',
            'ntfy_topic'  => '',
            'ntfy_auth'   => false,
            'ntfy_user'   => '',
            'ntfy_pass'   => '',

        ];
        if ('user' === $type && null !== $user) {
            $settings['ntfy_server'] = Preferences::getEncryptedForUser($user, 'ntfy_server', 'https://ntfy.sh')->data;
            $settings['ntfy_topic']  = Preferences::getEncryptedForUser($user, 'ntfy_topic', '')->data;
            $settings['ntfy_auth']   = Preferences::getForUser($user, 'ntfy_auth', false)->data;
            $settings['ntfy_user']   = Preferences::getEncryptedForUser($user, 'ntfy_user', '')->data;
            $settings['ntfy_pass']   = Preferences::getEncryptedForUser($user, 'ntfy_pass', '')->data;
        }
        if ('owner' === $type) {
            $settings['ntfy_server'] = FireflyConfig::getEncrypted('ntfy_server', 'https://ntfy.sh')->data;
            $settings['ntfy_topic']  = FireflyConfig::getEncrypted('ntfy_topic', '')->data;
            $settings['ntfy_auth']   = FireflyConfig::get('ntfy_auth', false)->data;
            $settings['ntfy_user']   = FireflyConfig::getEncrypted('ntfy_user', '')->data;
            $settings['ntfy_pass']   = FireflyConfig::getEncrypted('ntfy_pass', '')->data;
        }

        // overrule config.
        config(['ntfy-notification-channel.server' => $settings['ntfy_server']]);
        config(['ntfy-notification-channel.topic' => $settings['ntfy_topic']]);

        if ($settings['ntfy_auth']) {
            // overrule auth as well.
            config(['ntfy-notification-channel.authentication.enabled' => true]);
            config(['ntfy-notification-channel.authentication.username' => $settings['ntfy_user']]);
            config(['ntfy-notification-channel.authentication.password' => $settings['ntfy_pass']]);
        }

        return $settings;
    }
}

<?php

/*
 * AdminNotifiable.php
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

namespace FireflyIII\Notifications\Notifiables;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NotificationChannels\Pushover\PushoverReceiver;

class OwnerNotifiable
{
    public function routeNotificationForSlack(): string
    {
        $res = app('fireflyconfig')->getEncrypted('slack_webhook_url', '')->data;
        if (is_array($res)) {
            $res = '';
        }

        return (string) $res;
    }

    public function routeNotificationForPushover()
    {
        Log::debug('Return settings for routeNotificationForPushover');
        $pushoverAppToken  = (string) app('fireflyconfig')->getEncrypted('pushover_app_token', '')->data;
        $pushoverUserToken = (string) app('fireflyconfig')->getEncrypted('pushover_user_token', '')->data;

        return PushoverReceiver::withUserKey($pushoverUserToken)
            ->withApplicationToken($pushoverAppToken)
        ;
    }

    /**
     * Get the notification routing information for the given driver.
     *
     * @param string            $driver
     * @param null|Notification $notification
     *
     * @return mixed
     */
    public function routeNotificationFor($driver, $notification = null)
    {
        $method = 'routeNotificationFor'.Str::studly($driver);
        if (method_exists($this, $method)) {
            Log::debug(sprintf('Redirect for settings to "%s".', $method));

            return $this->{$method}($notification); // @phpstan-ignore-line
        }
        Log::debug(sprintf('No method "%s" found, return generic settings.', $method));

        return match ($driver) {
            'mail'  => (string) config('firefly.site_owner'),
            default => null,
        };
    }
}

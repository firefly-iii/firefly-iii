<?php

declare(strict_types=1);

/*
 * SendsTestNotification.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Listeners\Test;

use Exception;
use FireflyIII\Events\Test\OwnerTestsNotificationChannel;
use FireflyIII\Events\Test\UserTestsNotificationChannel;
use FireflyIII\Notifications\Test\OwnerTestNotificationEmail;
use FireflyIII\Notifications\Test\OwnerTestNotificationPushover;
use FireflyIII\Notifications\Test\OwnerTestNotificationSlack;
use FireflyIII\Notifications\Test\UserTestNotificationEmail;
use FireflyIII\Notifications\Test\UserTestNotificationPushover;
use FireflyIII\Notifications\Test\UserTestNotificationSlack;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendsTestNotification
{
    public function handle(OwnerTestsNotificationChannel|UserTestsNotificationChannel $event): void
    {
        Log::debug(sprintf('Now in SendsTestNotification::handle(%s->"%s")', get_class($event), $event->channel));

        $type = str_contains(get_class($event), 'Owner') ? 'owner' : 'user';
        $key  = sprintf('%s-%s', $type, $event->channel);

        switch ($key) {
            case 'user-email':
                $class = UserTestNotificationEmail::class;

                break;

            case 'user-slack':
                $class = UserTestNotificationSlack::class;

                break;

            case 'user-pushover':
                $class = UserTestNotificationPushover::class;

                break;

            case 'owner-email':
                $class = OwnerTestNotificationEmail::class;

                break;

            case 'owner-slack':
                $class = OwnerTestNotificationSlack::class;

                break;

            case 'owner-pushover':
                $class = OwnerTestNotificationPushover::class;

                break;

            default:
                Log::error(sprintf('Unknown key "%s" in sendTestNotification method.', $key));

                return;
        }
        Log::debug(sprintf('Will send %s as a notification.', $class));

        try {
            Notification::send($event->user, new $class());
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'Bcc')) {
                Log::warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            if (str_contains($message, 'RFC 2822')) {
                Log::warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }
        Log::debug(sprintf('If you see no errors above this line, test notification was sent over channel "%s"', $event->channel));
    }
}

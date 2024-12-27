<?php

/**
 * AdminEventHandler.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\Admin\InvitationCreated;
use FireflyIII\Events\NewVersionAvailable;
use FireflyIII\Events\Security\UnknownUserAttemptedLogin;
use FireflyIII\Events\Test\OwnerTestNotificationChannel;
use FireflyIII\Notifications\Admin\UnknownUserLoginAttempt;
use FireflyIII\Notifications\Admin\UserInvitation;
use FireflyIII\Notifications\Admin\VersionCheckResult;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\Notifications\Test\OwnerTestNotificationEmail;
use FireflyIII\Notifications\Test\OwnerTestNotificationNtfy;
use FireflyIII\Notifications\Test\OwnerTestNotificationPushover;
use FireflyIII\Notifications\Test\OwnerTestNotificationSlack;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Class AdminEventHandler.
 */
class AdminEventHandler
{
    public function sendInvitationNotification(InvitationCreated $event): void
    {
        $sendMail = app('fireflyconfig')->get('notification_invite_created', true)->data;
        if (false === $sendMail) {
            return;
        }

        try {
            $owner = new OwnerNotifiable();
            Notification::send($owner, new UserInvitation($owner, $event->invitee));
        } catch (\Exception $e) { // @phpstan-ignore-line
            $message = $e->getMessage();
            if (str_contains($message, 'Bcc')) {
                app('log')->warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            if (str_contains($message, 'RFC 2822')) {
                app('log')->warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
        }
    }

    public function sendLoginAttemptNotification(UnknownUserAttemptedLogin $event): void
    {
        try {
            $owner = new OwnerNotifiable();
            Notification::send($owner, new UnknownUserLoginAttempt($event->address));
        } catch (\Exception $e) { // @phpstan-ignore-line
            $message = $e->getMessage();
            if (str_contains($message, 'Bcc')) {
                app('log')->warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            if (str_contains($message, 'RFC 2822')) {
                app('log')->warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
        }
    }

    /**
     * Send new version message to admin.
     */
    public function sendNewVersion(NewVersionAvailable $event): void
    {
        $sendMail = app('fireflyconfig')->get('notification_new_version', true)->data;
        if (false === $sendMail) {
            return;
        }

        try {
            $owner = new OwnerNotifiable();
            Notification::send($owner, new VersionCheckResult($event->message));
        } catch (\Exception $e) {// @phpstan-ignore-line
            $message = $e->getMessage();
            if (str_contains($message, 'Bcc')) {
                app('log')->warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            if (str_contains($message, 'RFC 2822')) {
                app('log')->warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
        }
    }

    /**
     * Sends a test message to an administrator.
     */
    public function sendTestNotification(OwnerTestNotificationChannel $event): void
    {
        Log::debug(sprintf('Now in sendTestNotification("%s")', $event->channel));

        switch ($event->channel) {
            case 'email':
                $class = OwnerTestNotificationEmail::class;

                break;

            case 'slack':
                $class = OwnerTestNotificationSlack::class;

                break;

            case 'ntfy':
                $class = OwnerTestNotificationNtfy::class;

                break;

            case 'pushover':
                $class = OwnerTestNotificationPushover::class;

                break;

            default:
                app('log')->error(sprintf('Unknown channel "%s" in sendTestNotification method.', $event->channel));

                return;
        }
        Log::debug(sprintf('Will send %s as a notification.', $class));

        try {
            Notification::send($event->owner, new $class($event->owner));
        } catch (\Exception $e) { // @phpstan-ignore-line
            $message = $e->getMessage();
            if (str_contains($message, 'Bcc')) {
                app('log')->warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            if (str_contains($message, 'RFC 2822')) {
                app('log')->warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                return;
            }
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
        }
        Log::debug(sprintf('If you see no errors above this line, test notification was sent over channel "%s"', $event->channel));
    }
}

<?php

/*
 * MFAHandler.php
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

namespace FireflyIII\Handlers\Events\Security;

use FireflyIII\Events\Security\DisabledMFA;
use FireflyIII\Events\Security\EnabledMFA;
use FireflyIII\Events\Security\MFABackupFewLeft;
use FireflyIII\Events\Security\MFABackupNoLeft;
use FireflyIII\Events\Security\MFAManyFailedAttempts;
use FireflyIII\Events\Security\MFANewBackupCodes;
use FireflyIII\Events\Security\MFAUsedBackupCode;
use FireflyIII\Notifications\Security\DisabledMFANotification;
use FireflyIII\Notifications\Security\EnabledMFANotification;
use FireflyIII\Notifications\Security\MFABackupFewLeftNotification;
use FireflyIII\Notifications\Security\MFABackupNoLeftNotification;
use FireflyIII\Notifications\Security\MFAManyFailedAttemptsNotification;
use FireflyIII\Notifications\Security\MFAUsedBackupCodeNotification;
use FireflyIII\Notifications\Security\NewBackupCodesNotification;
use Illuminate\Support\Facades\Notification;

class MFAHandler
{
    public function sendMFAEnabledMail(EnabledMFA $event): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        $user = $event->user;

        try {
            Notification::send($user, new EnabledMFANotification($user));
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

    public function sendNewMFABackupCodesMail(MFANewBackupCodes $event): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        $user = $event->user;

        try {
            Notification::send($user, new NewBackupCodesNotification($user));
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

    public function sendBackupFewLeftMail(MFABackupFewLeft $event): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        $user  = $event->user;
        $count = $event->count;

        try {
            Notification::send($user, new MFABackupFewLeftNotification($user, $count));
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

    public function sendMFAFailedAttemptsMail(MFAManyFailedAttempts $event): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        $user  = $event->user;
        $count = $event->count;

        try {
            Notification::send($user, new MFAManyFailedAttemptsNotification($user, $count));
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

    public function sendBackupNoLeftMail(MFABackupNoLeft $event): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        $user = $event->user;

        try {
            Notification::send($user, new MFABackupNoLeftNotification($user));
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

    public function sendUsedBackupCodeMail(MFAUsedBackupCode $event): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        $user = $event->user;

        try {
            Notification::send($user, new MFAUsedBackupCodeNotification($user));
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

    public function sendMFADisabledMail(DisabledMFA $event): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        $user = $event->user;

        try {
            Notification::send($user, new DisabledMFANotification($user));
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
}

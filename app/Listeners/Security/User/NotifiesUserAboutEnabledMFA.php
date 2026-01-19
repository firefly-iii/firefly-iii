<?php

declare(strict_types=1);
/*
 * NotifiesUserAboutEnabledMFA.php
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

namespace FireflyIII\Listeners\Security\User;

use Exception;
use FireflyIII\Events\Security\User\UserHasEnabledMFA;
use FireflyIII\Notifications\Security\EnabledMFANotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifiesUserAboutEnabledMFA
{
    public function handle(UserHasEnabledMFA $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        $user = $event->user;

        try {
            Notification::send($user, new EnabledMFANotification($user));
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
    }
}

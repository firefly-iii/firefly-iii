<?php

declare(strict_types=1);

/*
 * NotifiesUserAboutUsedBackupCode.php
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
use FireflyIII\Events\Security\User\UserHasUsedBackupCode;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\Security\MFAUsedBackupCodeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifiesUserAboutUsedBackupCode implements ShouldQueue
{
    public function handle(UserHasUsedBackupCode $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        $user = $event->user;
            NotificationSender::send($user, new MFAUsedBackupCodeNotification($user));
    }
}

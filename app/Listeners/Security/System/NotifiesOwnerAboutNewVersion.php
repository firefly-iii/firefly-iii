<?php

declare(strict_types=1);

/*
 * NotifiesOwnerAboutNewVersion.php
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

namespace FireflyIII\Listeners\Security\System;

use FireflyIII\Events\Security\System\SystemFoundNewVersionOnline;
use FireflyIII\Notifications\Admin\VersionCheckResult;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifiesOwnerAboutNewVersion implements ShouldQueue
{
    public function handle(SystemFoundNewVersionOnline $event): void
    {
        $sendMail = FireflyConfig::get('notification_new_version', true)->data;
        if (false === $sendMail) {
            return;
        }

        $owner    = new OwnerNotifiable();
        NotificationSender::send($owner, new VersionCheckResult($event->message));
    }
}

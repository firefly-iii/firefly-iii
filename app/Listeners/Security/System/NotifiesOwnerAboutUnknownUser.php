<?php

declare(strict_types=1);

/*
 * NotifiesOwnerAboutUnknownUser.php
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

use FireflyIII\Events\Security\System\UnknownUserTriedLogin;
use FireflyIII\Notifications\Admin\UnknownUserLoginAttempt;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\Notifications\NotificationSender;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifiesOwnerAboutUnknownUser implements ShouldQueue
{
    public function handle(UnknownUserTriedLogin $event): void
    {
        $owner = new OwnerNotifiable();
        NotificationSender::send($owner, new UnknownUserLoginAttempt($event->address));
    }
}

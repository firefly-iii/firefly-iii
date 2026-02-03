<?php
/*
 * NotifiesUserAboutNewAccessToken.php
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

use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\User\NewAccessToken;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Laravel\Passport\Events\AccessTokenCreated;

class NotifiesUserAboutNewAccessToken
{
    public function handle(AccessTokenCreated $event): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->find((int)$event->userId);

        if (null !== $user) {
            NotificationSender::send($user, new NewAccessToken());
        }
    }
}

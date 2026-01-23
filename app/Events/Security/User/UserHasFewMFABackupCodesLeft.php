<?php

declare(strict_types=1);

/*
 * UserHasFewMFABackupCodesLeft.php
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

namespace FireflyIII\Events\Security\User;

use FireflyIII\Events\Event;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

class UserHasFewMFABackupCodesLeft extends Event
{
    use SerializesModels;

    public User $user;

    public function __construct(
        Authenticatable|User|null $user,
        public int $count
    ) {
        if ($user instanceof User) {
            $this->user = $user;

            return;
        }

        throw new InvalidArgumentException('User must be an instance of User.');
    }
}

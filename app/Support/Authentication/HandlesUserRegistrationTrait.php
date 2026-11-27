<?php
/*
 * HandlersUserRegistrationTrait.php
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

declare(strict_types=1);

namespace FireflyIII\Support\Authentication;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;
use FireflyIII\User;

trait HandlesUserRegistrationTrait
{
    /**
     * @throws FireflyException
     */
    protected function createGroupMembership(User $user): void
    {
        $groupTitle          = $user->email;
        $index               = 1;

        /** @var null|UserGroup $group */
        $group               = null;
        $groupExists = UserGroup::query()->where('title', $groupTitle)->count() > 0;

        // always create a new group.
        while ($groupExists) {
            $groupTitle  = sprintf('%s-%d', $user->email, $index);
            $groupExists = UserGroup::query()->where('title', $groupTitle)->count() > 0;
            ++$index;
            if ($index > 1024) {
                throw new FireflyException('Email address can no longer be used for registrations.');
            }
        }
        $group = UserGroup::create(['title' => $groupTitle]);

        /** @var null|UserRole $role */
        $role                = UserRole::query()->where('title', UserRoleEnum::OWNER->value)->first();
        if (null === $role) {
            throw new FireflyException('The user role is unexpectedly empty. Did you run all migrations?');
        }
        GroupMembership::create(['user_id' => $user->id, 'user_group_id' => $group->id, 'user_role_id' => $role->id]);
        $user->user_group_id = $group->id;
        $user->save();
    }
}

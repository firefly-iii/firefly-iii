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
use Illuminate\Support\Facades\Log;

trait HandlesUserRegistrationTrait
{
    /**
     * @throws FireflyException
     */
    protected function createGroupMembership(User $user, bool $isNewUser = false): void
    {
        Log::debug(sprintf('Creating group membership for user "%s" (%s)', $user->email, var_export($isNewUser, true)));
        $groupTitle  = $user->email;
        $fullOwner   = UserRole::whereTitle(UserRoleEnum::OWNER->value)->first();
        $index       = 1;
        $groupExists = true;

        if (null === $fullOwner) {
            throw new FireflyException('The user role is unexpectedly empty. Did you run all migrations?');
        }

        if (!$isNewUser) {
            // check if user already has a full owner group membership:
            $membership = GroupMembership::query()->where('user_id', $user->id)->where('user_role_id', $fullOwner->id)->first();
            if (null !== $membership) {
                Log::debug(sprintf(
                    'User "%s" already has a group membership in group "%s" (%s), no need to create new one.',
                    $user->email,
                    $membership->userGroup->title,
                    $membership->userRole->title
                ));
            }
            // if not, create it.
            if (null === $membership) {
                Log::debug(sprintf('User "%s" does not have their own group, will create one.', $user->email));
                $isNewUser = true;
            }
        }

        // always create a new group for new user:
        if ($isNewUser) {
            while ($groupExists) {
                $groupExists = UserGroup::query()->where('title', $groupTitle)->count() > 0;
                Log::debug(sprintf('Group "%s" exists? %s', $groupTitle, var_export($groupExists, true)));
                if ($groupExists) {
                    $groupTitle = sprintf('%s-%d', $user->email, $index);
                    ++$index;
                }
                if ($index > 1024) {
                    throw new FireflyException('Email address can no longer be used for registrations.');
                }
            }
            $group               = UserGroup::create(['title' => $groupTitle]);

            GroupMembership::create(['user_id' => $user->id, 'user_group_id' => $group->id, 'user_role_id' => $fullOwner->id]);
            $user->user_group_id = $group->id;
            $user->save();
        }
    }
}

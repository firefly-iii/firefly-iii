<?php

/*
 * UserGroupTrait.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Support\Repositories\UserGroup;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

/**
 * Trait UserGroupTrait
 */
trait UserGroupTrait
{
    protected ?User      $user      = null;
    protected ?UserGroup $userGroup = null;

    public function getUserGroup(): ?UserGroup
    {
        return $this->userGroup;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function checkUserGroupAccess(UserRoleEnum $role): bool
    {
        $result = $this->user->hasRoleInGroupOrOwner($this->userGroup, $role);
        if ($result) {
            Log::debug(sprintf('User #%d has role %s in group #%d or is owner/full.', $this->user->id, $role->value, $this->userGroup->id));

            return true;
        }
        Log::warning(sprintf('User #%d DOES NOT have role %s in group #%d.', $this->user->id, $role->value, $this->userGroup->id));

        return false;
    }

    /**
     * TODO This method does not check if the user has access to this particular user group.
     */
    public function setUserGroup(UserGroup $userGroup): void
    {
        if (null === $this->user) {
            Log::warning(sprintf('User is not set in repository %s', static::class));
        }
        $this->userGroup = $userGroup;
    }

    /**
     * @throws FireflyException
     */
    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user      = $user;
            if (null === $user->userGroup) {
                throw new FireflyException(sprintf('User #%d has no user group.', $user->id));
            }
            $this->userGroup = $user->userGroup;

            return;
        }

        throw new FireflyException(sprintf('Object is of class %s, not User.', $user::class));
    }

    /**
     * @throws FireflyException
     */
    public function setUserGroupById(int $userGroupId): void
    {
        $memberships = GroupMembership::where('user_id', $this->user->id)
            ->where('user_group_id', $userGroupId)
            ->count()
        ;
        if (0 === $memberships) {
            throw new FireflyException(sprintf('User #%d has no access to administration #%d', $this->user->id, $userGroupId));
        }

        /** @var null|UserGroup $userGroup */
        $userGroup   = UserGroup::find($userGroupId);
        if (null === $userGroup) {
            throw new FireflyException(sprintf('Cannot find administration for user #%d', $this->user->id));
        }
        $this->setUserGroup($userGroup);
    }
}

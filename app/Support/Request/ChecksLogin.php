<?php

/*
 * ChecksLogin.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Support\Request;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;

/**
 * Trait ChecksLogin
 */
trait ChecksLogin
{
    /**
     * Verify the request.
     */
    public function authorize(): bool
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        // Only allow logged-in users
        $check     = auth()->check();
        if (!$check) {
            return false;
        }
        if (!property_exists($this, 'acceptedRoles')) { // @phpstan-ignore-line
            app('log')->debug('Request class has no acceptedRoles array');

            return true; // check for false already took place.
        }

        /** @var User $user */
        $user      = auth()->user();
        $userGroup = $this->getUserGroup();
        if (null === $userGroup) {
            app('log')->error('User has no valid user group submitted or otherwise.');

            return false;
        }

        /** @var UserRoleEnum $role */
        foreach ($this->acceptedRoles as $role) {
            // system owner cannot overrule this, MUST be member of the group.
            $access = $user->hasRoleInGroupOrOwner($userGroup, $role);
            if ($access) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the user group or NULL if none is set.
     * Will throw exception if invalid.
     * TODO duplicated in JSONAPI code.
     */
    public function getUserGroup(): ?UserGroup
    {
        /** @var User $user */
        $user      = auth()->user();
        app('log')->debug('Now in getUserGroup()');

        /** @var null|UserGroup $userGroup */
        $userGroup = $this->route()?->parameter('userGroup');
        if (null === $userGroup) {
            app('log')->debug('Request class has no userGroup parameter, but perhaps there is a parameter.');
            $userGroupId = (int)$this->get('user_group_id');
            if (0 === $userGroupId) {
                app('log')->debug(sprintf('Request class has no user_group_id parameter, grab default from user (group #%d).', $user->user_group_id));
                $userGroupId = (int)$user->user_group_id;
            }
            $userGroup   = UserGroup::find($userGroupId);
            if (null === $userGroup) {
                app('log')->error(sprintf('Request class has user_group_id (#%d), but group does not exist.', $userGroupId));

                return null;
            }
            app('log')->debug('Request class has valid user_group_id.');
        }

        return $userGroup;
    }
}

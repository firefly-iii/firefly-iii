<?php
/*
 * ValidatesUserGroupTrait.php
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

namespace FireflyIII\Support\Http\Api;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

/**
 * Trait ValidatesUserGroupTrait
 */
trait ValidatesUserGroupTrait
{
    /**
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    protected function validateUserGroup(Request $request): UserGroup
    {
        app('log')->debug(sprintf('validateUserGroup: %s', static::class));
        if (!auth()->check()) {
            app('log')->debug('validateUserGroup: user is not logged in, return NULL.');

            throw new AuthenticationException();
        }

        /** @var User $user */
        $user       = auth()->user();
        $groupId    = 0;
        if (!$request->has('user_group_id')) {
            $groupId = $user->user_group_id;
            app('log')->debug(sprintf('validateUserGroup: no user group submitted, use default group #%d.', $groupId));
        }
        if ($request->has('user_group_id')) {
            $groupId = (int)$request->get('user_group_id');
            app('log')->debug(sprintf('validateUserGroup: user group submitted, search for memberships in group #%d.', $groupId));
        }

        /** @var null|GroupMembership $membership */
        $membership = $user->groupMemberships()->where('user_group_id', $groupId)->first();

        if (null === $membership) {
            app('log')->debug(sprintf('validateUserGroup: user has no access to group #%d.', $groupId));

            throw new AuthorizationException((string)trans('validation.no_access_group'));
        }

        // need to get the group from the membership:
        /** @var null|UserGroup $group */
        $group      = $membership->userGroup;
        if (null === $group) {
            app('log')->debug(sprintf('validateUserGroup: group #%d does not exist.', $groupId));

            throw new AuthorizationException((string)trans('validation.belongs_user_or_user_group'));
        }
        app('log')->debug(sprintf('validateUserGroup: validate access of user to group #%d ("%s").', $groupId, $group->title));
        $roles      = property_exists($this, 'acceptedRoles') ? $this->acceptedRoles : [];
        if (0 === count($roles)) {
            app('log')->debug('validateUserGroup: no roles defined, so no access.');

            throw new AuthorizationException((string)trans('validation.no_accepted_roles_defined'));
        }
        app('log')->debug(sprintf('validateUserGroup: have %d roles to check.', count($roles)), $roles);

        /** @var UserRoleEnum $role */
        foreach ($roles as $role) {
            if ($user->hasRoleInGroupOrOwner($group, $role)) {
                app('log')->debug(sprintf('validateUserGroup: User has role "%s" in group #%d, return the group.', $role->value, $groupId));

                return $group;
            }
            app('log')->debug(sprintf('validateUserGroup: User does NOT have role "%s" in group #%d, continue searching.', $role->value, $groupId));
        }

        app('log')->debug('validateUserGroup: User does NOT have enough rights to access endpoint.');

        throw new AuthorizationException((string)trans('validation.belongs_user_or_user_group'));
    }
}

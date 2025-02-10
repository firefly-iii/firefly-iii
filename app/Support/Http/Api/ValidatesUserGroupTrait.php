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
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\UserGroup\UserGroupRepositoryInterface;
use FireflyIII\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Trait ValidatesUserGroupTrait
 */
trait ValidatesUserGroupTrait
{
    protected ?UserGroup $userGroup = null;

    /**
     * An "undocumented" filter
     *
     * TODO add this filter to the API docs.
     *
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    protected function validateUserGroup(Request $request): UserGroup
    {
        Log::debug(sprintf('validateUserGroup: %s', static::class));
        if (!auth()->check()) {
            Log::debug('validateUserGroup: user is not logged in, return NULL.');

            throw new AuthenticationException();
        }

        /** @var User $user */
        $user        = auth()->user();
        $groupId     = 0;
        if (!$request->has('user_group_id')) {
            $groupId = (int) $user->user_group_id;
            Log::debug(sprintf('validateUserGroup: no user group submitted, use default group #%d.', $groupId));
        }
        if ($request->has('user_group_id')) {
            $groupId = (int) $request->get('user_group_id');
            Log::debug(sprintf('validateUserGroup: user group submitted, search for memberships in group #%d.', $groupId));
        }

        /** @var UserGroupRepositoryInterface $repository */
        $repository  = app(UserGroupRepositoryInterface::class);
        $repository->setUser($user);
        $memberships = $repository->getMembershipsFromGroupId($groupId);

        if (0 === $memberships->count()) {
            Log::debug(sprintf('validateUserGroup: user has no access to group #%d.', $groupId));

            throw new AuthorizationException((string) trans('validation.no_access_group'));
        }

        // need to get the group from the membership:
        $group       = $repository->getById($groupId);
        if (null === $group) {
            Log::debug(sprintf('validateUserGroup: group #%d does not exist.', $groupId));

            throw new AuthorizationException((string) trans('validation.belongs_user_or_user_group'));
        }
        Log::debug(sprintf('validateUserGroup: validate access of user to group #%d ("%s").', $groupId, $group->title));
        $roles       = property_exists($this, 'acceptedRoles') ? $this->acceptedRoles : []; // @phpstan-ignore-line
        if (0 === count($roles)) {
            Log::debug('validateUserGroup: no roles defined, so no access.');

            throw new AuthorizationException((string) trans('validation.no_accepted_roles_defined'));
        }
        Log::debug(sprintf('validateUserGroup: have %d roles to check.', count($roles)), $roles);

        /** @var UserRoleEnum $role */
        foreach ($roles as $role) {
            if ($user->hasRoleInGroupOrOwner($group, $role)) {
                Log::debug(sprintf('validateUserGroup: User has role "%s" in group #%d, return the group.', $role->value, $groupId));
                $this->userGroup = $group;

                return $group;
            }
            Log::debug(sprintf('validateUserGroup: User does NOT have role "%s" in group #%d, continue searching.', $role->value, $groupId));
        }

        Log::debug('validateUserGroup: User does NOT have enough rights to access endpoint.');

        throw new AuthorizationException((string) trans('validation.belongs_user_or_user_group'));
    }
}

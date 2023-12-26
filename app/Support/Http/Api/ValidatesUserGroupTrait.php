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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Http\Request;

/**
 * Trait ValidatesUserGroupTrait
 */
trait ValidatesUserGroupTrait
{
    /**
     * This check does not validate which rights the user has, that comes later.
     *
     * @throws FireflyException
     */
    protected function validateUserGroup(Request $request): ?UserGroup
    {
        if (!auth()->check()) {
            app('log')->debug('validateUserGroup: user is not logged in, return NULL.');

            return null;
        }

        /** @var User $user */
        $user = auth()->user();
        if (!$request->has('user_group_id')) {
            $group = $user->userGroup;
            app('log')->debug(sprintf('validateUserGroup: no user group submitted, return default group #%d.', $group?->id));

            return $group;
        }
        $groupId = (int)$request->get('user_group_id');

        /** @var null|GroupMembership $membership */
        $membership = $user->groupMemberships()->where('user_group_id', $groupId)->first();
        if (null === $membership) {
            app('log')->debug('validateUserGroup: user has no access to this group.');

            throw new FireflyException((string)trans('validation.belongs_user_or_user_group'));
        }
        app('log')->debug(sprintf('validateUserGroup: user has role "%s" in group #%d.', $membership->userRole->title, $membership->userGroup->id));

        return $membership->userGroup;
    }
}

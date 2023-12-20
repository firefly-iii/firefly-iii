<?php

/*
 * UserGroupFactory.php
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

namespace FireflyIII\Factory;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;

/**
 * Class UserGroupFactory
 */
class UserGroupFactory
{
    /**
     * @throws FireflyException
     */
    public function create(array $data): UserGroup
    {
        $userGroup        = new UserGroup();
        $userGroup->title = $data['title'];
        $userGroup->save();

        // grab the OWNER role:
        $role = UserRole::whereTitle(UserRoleEnum::OWNER->value)->first();
        if (null === $role) {
            throw new FireflyException('Role "owner" does not exist.');
        }
        // make user member:
        $groupMembership                = new GroupMembership();
        $groupMembership->user_group_id = $userGroup->id;
        $groupMembership->user_id       = $data['user']->id;
        $groupMembership->user_role_id  = $role->id;
        $groupMembership->save();

        return $userGroup;
    }
}

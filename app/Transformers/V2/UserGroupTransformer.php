<?php

/*
 * UserGroupTransformer.php
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

namespace FireflyIII\Transformers\V2;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class UserGroupTransformer
 */
class UserGroupTransformer extends AbstractTransformer
{
    private array $memberships;

    public function __construct()
    {
        $this->memberships = [];
    }

    public function collectMetaData(Collection $objects): void
    {
        if (auth()->check()) {
            // collect memberships so they can be listed in the group.
            /** @var User $user */
            $user = auth()->user();

            /** @var UserGroup $userGroup */
            foreach ($objects as $userGroup) {
                $userGroupId = $userGroup->id;
                $access      = $user->hasRoleInGroupOrOwner($userGroup, UserRoleEnum::VIEW_MEMBERSHIPS) || $user->hasRole('owner');
                if ($access) {
                    $groupMemberships = $userGroup->groupMemberships()->get();

                    /** @var GroupMembership $groupMembership */
                    foreach ($groupMemberships as $groupMembership) {
                        $this->memberships[$userGroupId][] = [
                            'user_id'    => (string)$groupMembership->user_id,
                            'user_email' => $groupMembership->user->email,
                            'role'       => $groupMembership->userRole->title,
                        ];
                    }
                }
            }
        }
    }

    /**
     * Transform the user group.
     */
    public function transform(UserGroup $userGroup): array
    {
        return [
            'id'         => $userGroup->id,
            'created_at' => $userGroup->created_at->toAtomString(),
            'updated_at' => $userGroup->updated_at->toAtomString(),
            'title'      => $userGroup->title,
            'members'    => $this->memberships[$userGroup->id] ?? [],
        ];
        // if the user has a specific role in this group, then collect the memberships.
    }
}

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

namespace FireflyIII\Transformers;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Transformers\V2\AbstractTransformer;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class UserGroupTransformer
 */
class UserGroupTransformer extends AbstractTransformer
{
    private array $inUse;
    private array $memberships;
    private array $membershipsVisible;

    public function __construct()
    {
        $this->memberships        = [];
        $this->membershipsVisible = [];
        $this->inUse              = [];
    }

    public function collectMetaData(Collection $objects): Collection
    {
        if (auth()->check()) {
            // collect memberships so they can be listed in the group.
            /** @var User $user */
            $user = auth()->user();

            /** @var UserGroup $userGroup */
            foreach ($objects as $userGroup) {
                $userGroupId                            = $userGroup->id;
                $this->inUse[$userGroupId]              = $user->user_group_id === $userGroupId;
                $access                                 = $user->hasRoleInGroupOrOwner($userGroup, UserRoleEnum::VIEW_MEMBERSHIPS) || $user->hasRole('owner');
                $this->membershipsVisible[$userGroupId] = $access;
                if ($access) {
                    $groupMemberships = $userGroup->groupMemberships()->get();

                    /** @var GroupMembership $groupMembership */
                    foreach ($groupMemberships as $groupMembership) {
                        $this->memberships[$userGroupId][] = [
                            'user_id'    => (string) $groupMembership->user_id,
                            'user_email' => $groupMembership->user->email,
                            'role'       => $groupMembership->userRole->title,
                            'you'        => $groupMembership->user_id === $user->id,
                        ];
                    }
                }
            }
            $this->mergeMemberships();
        }

        return $objects;
    }

    private function mergeMemberships(): void
    {
        $new               = [];
        foreach ($this->memberships as $groupId => $members) {
            $new[$groupId] ??= [];

            foreach ($members as $member) {
                $mail                            = $member['user_email'];
                $new[$groupId][$mail] ??= [
                    'user_id'    => $member['user_id'],
                    'user_email' => $member['user_email'],
                    'you'        => $member['you'],
                    'roles'      => [],
                ];
                $new[$groupId][$mail]['roles'][] = $member['role'];
            }
        }
        $this->memberships = $new;
    }

    /**
     * Transform the user group.
     */
    public function transform(UserGroup $userGroup): array
    {
        $currency = Amount::getNativeCurrencyByUserGroup($userGroup);

        return [
            'id'                             => $userGroup->id,
            'created_at'                     => $userGroup->created_at->toAtomString(),
            'updated_at'                     => $userGroup->updated_at->toAtomString(),
            'in_use'                         => $this->inUse[$userGroup->id] ?? false,
            'title'                          => $userGroup->title,
            'can_see_members'                => $this->membershipsVisible[$userGroup->id] ?? false,
            'members'                        => array_values($this->memberships[$userGroup->id] ?? []),
            'native_currency_id'             => (string) $currency->id,
            'native_currency_name'           => $currency->name,
            'native_currency_code'           => $currency->code,
            'native_currency_symbol'         => $currency->symbol,
            'native_currency_decimal_places' => $currency->decimal_places,
        ];
        // if the user has a specific role in this group, then collect the memberships.
    }
}

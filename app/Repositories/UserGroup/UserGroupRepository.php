<?php
/*
 * UserGroupRepository.php
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

namespace FireflyIII\Repositories\UserGroup;

use FireflyIII\Factory\UserGroupFactory;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Class UserGroupRepository
 */
class UserGroupRepository implements UserGroupRepositoryInterface
{
    private User $user;

    /**
     * Returns all groups the user is member in.
     *
     * @inheritDoc
     */
    public function get(): Collection
    {
        $collection  = new Collection();
        $memberships = $this->user->groupMemberships()->get();
        /** @var GroupMembership $membership */
        foreach ($memberships as $membership) {
            /** @var UserGroup $group */
            $group = $membership->userGroup()->first();
            if (null !== $group) {
                $collection->push($group);
            }
        }
        return $collection;
    }

    /**
     * Returns all groups.
     *
     * @inheritDoc
     */
    public function getAll(): Collection
    {
        return UserGroup::all();
    }

    /**
     * @inheritDoc
     */
    public function setUser(Authenticatable | User | null $user): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        if (null !== $user) {
            $this->user = $user;
        }
    }

    /**
     * @param array $data
     *
     * @return UserGroup
     */
    public function store(array $data): UserGroup
    {
        $data['user'] = $this->user;
        /** @var UserGroupFactory $factory */
        $factory = app(UserGroupFactory::class);
        return $factory->create($data);
    }
}

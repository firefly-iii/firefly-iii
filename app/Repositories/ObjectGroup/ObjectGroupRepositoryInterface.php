<?php

/**
 * ObjectGroupRepositoryInterface.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Repositories\ObjectGroup;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface ObjectGroupRepositoryInterface
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 */
interface ObjectGroupRepositoryInterface
{
    /**
     * Delete all.
     */
    public function deleteAll(): void;

    /**
     * Delete empty ones.
     */
    public function deleteEmpty(): void;

    public function destroy(ObjectGroup $objectGroup): void;

    public function get(): Collection;

    public function getBills(ObjectGroup $objectGroup): Collection;

    public function getPiggyBanks(ObjectGroup $objectGroup): Collection;

    /**
     * Delete all.
     */
    public function resetOrder(): void;

    public function search(string $query, int $limit): Collection;

    public function setOrder(ObjectGroup $objectGroup, int $newOrder): ObjectGroup;

    public function update(ObjectGroup $objectGroup, array $data): ObjectGroup;
}

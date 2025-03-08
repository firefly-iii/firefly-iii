<?php

/**
 * UserRepositoryInterface.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Repositories\User;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\InvitedUser;
use FireflyIII\Models\Role;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface UserRepositoryInterface.
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 *
 */
interface UserRepositoryInterface
{
    /**
     * Returns a collection of all users.
     */
    public function all(): Collection;

    /**
     * Gives a user a role.
     */
    public function attachRole(User $user, string $role): bool;

    /**
     * This updates the users email address and records some things so it can be confirmed or undone later.
     * The user is blocked until the change is confirmed.
     *
     * @see updateEmail
     */
    public function changeEmail(User $user, string $newEmail): bool;

    /**
     * @return mixed
     */
    public function changePassword(User $user, string $password);

    public function changeStatus(User $user, bool $isBlocked, string $code): bool;

    /**
     * Returns a count of all users.
     */
    public function count(): int;

    public function createRole(string $name, string $displayName, string $description): Role;

    public function deleteEmptyGroups(): void;

    public function deleteInvite(InvitedUser $invite): void;

    public function destroy(User $user): bool;

    public function find(int $userId): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * Returns the first user in the DB. Generally only works when there is just one.
     */
    public function first(): ?User;

    public function getInvitedUsers(): Collection;

    public function getRole(string $role): ?Role;

    public function getRoleByUser(User $user): ?string;

    public function getRolesInGroup(User $user, int $groupId): array;

    /**
     * Return basic user information.
     */
    public function getUserData(User $user): array;

    public function getUserGroups(User $user): Collection;

    public function hasRole(null | Authenticatable | User $user, string $role): bool;

    public function inviteUser(null | Authenticatable | User $user, string $email): InvitedUser;

    public function redeemCode(string $code): void;

    /**
     * Remove any role the user has.
     */
    public function removeRole(User $user, string $role): void;

    /**
     * Set MFA code.
     */
    public function setMFACode(User $user, ?string $code): void;

    public function store(array $data): User;

    public function unblockUser(User $user): void;

    /**
     * Update user info.
     */
    public function update(User $user, array $data): User;

    /**
     * This updates the users email address. Same as changeEmail just without most logging. This makes sure that the
     * undo/confirm routine can't catch this one. The user is NOT blocked.
     *
     * @see changeEmail
     */
    public function updateEmail(User $user, string $newEmail): bool;

    public function validateInviteCode(string $code): bool;
}

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

use FireflyIII\Models\InvitedUser;
use FireflyIII\Models\Role;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface UserRepositoryInterface.
 */
interface UserRepositoryInterface
{
    /**
     * Returns a collection of all users.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Gives a user a role.
     *
     * @param User   $user
     * @param string $role
     *
     * @return bool
     */
    public function attachRole(User $user, string $role): bool;

    /**
     * This updates the users email address and records some things so it can be confirmed or undone later.
     * The user is blocked until the change is confirmed.
     *
     * @param User   $user
     * @param string $newEmail
     *
     * @return bool
     * @see updateEmail
     *
     */
    public function changeEmail(User $user, string $newEmail): bool;

    /**
     * @param User   $user
     * @param string $password
     *
     * @return mixed
     */
    public function changePassword(User $user, string $password);

    /**
     * @param User   $user
     * @param bool   $isBlocked
     * @param string $code
     *
     * @return bool
     */
    public function changeStatus(User $user, bool $isBlocked, string $code): bool;

    /**
     * Returns a count of all users.
     *
     * @return int
     */
    public function count(): int;

    /**
     * @param string $name
     * @param string $displayName
     * @param string $description
     *
     * @return Role
     */
    public function createRole(string $name, string $displayName, string $description): Role;

    /**
     *
     */
    public function deleteEmptyGroups(): void;

    /**
     * @param InvitedUser $invite
     *
     * @return void
     */
    public function deleteInvite(InvitedUser $invite): void;

    /**
     * @param User $user
     *
     * @return bool
     */
    public function destroy(User $user): bool;

    /**
     * @param int $userId
     *
     * @return User|null
     */
    public function find(int $userId): ?User;

    /**
     * @param string $email
     *
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Returns the first user in the DB. Generally only works when there is just one.
     *
     * @return null|User
     */
    public function first(): ?User;

    /**
     * @return Collection
     */
    public function getInvitedUsers(): Collection;

    /**
     * @param string $role
     *
     * @return Role|null
     */
    public function getRole(string $role): ?Role;

    /**
     * @param User $user
     *
     * @return string|null
     */
    public function getRoleByUser(User $user): ?string;

    /**
     * @param User $user
     * @param int  $groupId
     *
     * @return array
     */
    public function getRolesInGroup(User $user, int $groupId): array;

    /**
     * Return basic user information.
     *
     * @param User $user
     *
     * @return array
     */
    public function getUserData(User $user): array;

    /**
     * @param User|Authenticatable|null $user
     * @param string                    $role
     *
     * @return bool
     */
    public function hasRole(User | Authenticatable | null $user, string $role): bool;

    /**
     * @param User|Authenticatable|null $user
     * @param string                    $email
     *
     * @return InvitedUser
     */
    public function inviteUser(User | Authenticatable | null $user, string $email): InvitedUser;

    /**
     * @param string $code
     *
     * @return void
     */
    public function redeemCode(string $code): void;

    /**
     * Remove any role the user has.
     *
     * @param User   $user
     * @param string $role
     */
    public function removeRole(User $user, string $role): void;

    /**
     * Set MFA code.
     *
     * @param User        $user
     * @param string|null $code
     */
    public function setMFACode(User $user, ?string $code): void;

    /**
     * @param array $data
     *
     * @return User
     */
    public function store(array $data): User;

    /**
     * @param User $user
     */
    public function unblockUser(User $user): void;

    /**
     * Update user info.
     *
     * @param User  $user
     * @param array $data
     *
     * @return User
     */
    public function update(User $user, array $data): User;

    /**
     * This updates the users email address. Same as changeEmail just without most logging. This makes sure that the
     * undo/confirm routine can't catch this one. The user is NOT blocked.
     *
     * @param User   $user
     * @param string $newEmail
     *
     * @return bool
     * @see changeEmail
     *
     */
    public function updateEmail(User $user, string $newEmail): bool;

    /**
     * @param string $code
     *
     * @return bool
     */
    public function validateInviteCode(string $code): bool;
}

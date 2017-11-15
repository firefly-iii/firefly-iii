<?php
/**
 * UserRepositoryInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\User;

use FireflyIII\User;
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
     * @see updateEmail
     *
     * @return bool
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
     * @param User $user
     *
     * @return bool
     */
    public function destroy(User $user): bool;

    /**
     * @param int $userId
     *
     * @return User
     */
    public function find(int $userId): User;

    /**
     * @param string $email
     *
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Return basic user information.
     *
     * @param User $user
     *
     * @return array
     */
    public function getUserData(User $user): array;

    /**
     * @param User   $user
     * @param string $role
     *
     * @return bool
     */
    public function hasRole(User $user, string $role): bool;

    /**
     * This updates the users email address. Same as changeEmail just without most logging. This makes sure that the undo/confirm routine can't catch this one.
     * The user is NOT blocked.
     *
     * @param User   $user
     * @param string $newEmail
     *
     * @see changeEmail
     *
     * @return bool
     */
    public function updateEmail(User $user, string $newEmail): bool;
}

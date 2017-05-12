<?php
/**
 * UserRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\User;


use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface UserRepositoryInterface
 *
 * @package FireflyIII\Repositories\User
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
}

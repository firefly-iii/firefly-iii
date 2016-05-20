<?php
/**
 * UserRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

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
     * Returns a count of all users.
     *
     * @return int
     */
    public function count(): int;
}

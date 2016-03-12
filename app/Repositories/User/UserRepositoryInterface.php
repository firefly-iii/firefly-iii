<?php
/**
 * UserRepositoryInterface.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Repositories\User;


use FireflyIII\User;

/**
 * Interface UserRepositoryInterface
 *
 * @package FireflyIII\Repositories\User
 */
interface UserRepositoryInterface
{
    /**
     * @param User   $user
     * @param string $role
     *
     * @return bool
     */
    public function attachRole(User $user, string $role): bool;

    /**
     * @return int
     */
    public function count(): int;
}
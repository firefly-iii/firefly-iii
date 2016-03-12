<?php
/**
 * UserRepository.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Repositories\User;


use FireflyIII\Models\Role;
use FireflyIII\User;

/**
 * Class UserRepository
 *
 * @package FireflyIII\Repositories\User
 */
class UserRepository implements UserRepositoryInterface
{

    /**
     * @param User   $user
     * @param string $role
     *
     * @return bool
     */
    public function attachRole(User $user, string $role): bool
    {
        $admin = Role::where('name', 'owner')->first();
        $user->attachRole($admin);

        return true;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return User::count();
    }
}
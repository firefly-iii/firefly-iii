<?php
declare(strict_types = 1);
/**
 * UserRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Repositories\User;


use FireflyIII\Models\Role;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class UserRepository
 *
 * @package FireflyIII\Repositories\User
 */
class UserRepository implements UserRepositoryInterface
{

    /**
     * @return Collection
     */
    public function all(): Collection
    {
        return User::orderBy('id', 'DESC')->get(['users.*']);
    }

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
        $user->save();

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

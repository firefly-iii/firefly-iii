<?php

/**
 * RemoteUserProvider.php
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

namespace FireflyIII\Support\Authentication;

use FireflyIII\Console\Commands\Correction\CreatesGroupMemberships;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Role;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

/**
 * Class RemoteUserProvider
 */
class RemoteUserProvider implements UserProvider
{
    #[\Override]
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        throw new FireflyException(sprintf('Did not implement %s', __METHOD__));
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        throw new FireflyException(sprintf('Did not implement %s', __METHOD__));
    }

    /**
     * @param mixed $identifier
     *
     * @throws FireflyException
     */
    public function retrieveById($identifier): User
    {
        app('log')->debug(sprintf('Now at %s(%s)', __METHOD__, $identifier));
        $user = User::where('email', $identifier)->first();
        if (null === $user) {
            app('log')->debug(sprintf('User with email "%s" not found. Will be created.', $identifier));
            $user = User::create(
                [
                    'blocked'      => false,
                    'blocked_code' => null,
                    'email'        => $identifier,
                    'password'     => bcrypt(\Str::random(64)),
                ]
            );
            // if this is the first user, give them admin as well.
            if (1 === User::count()) {
                $roleObject = Role::where('name', 'owner')->first();
                $user->roles()->attach($roleObject);
            }
        }
        // make sure the user gets an administration as well.
        CreatesGroupMemberships::createGroupMembership($user);

        app('log')->debug(sprintf('Going to return user #%d (%s)', $user->id, $user->email));

        return $user;
    }

    /**
     * @param mixed $identifier
     * @param mixed $token
     *
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        throw new FireflyException(sprintf('A) Did not implement %s', __METHOD__));
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param mixed $token
     *
     * @throws FireflyException
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        throw new FireflyException(sprintf('B) Did not implement %s', __METHOD__));
    }

    /**
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));

        throw new FireflyException(sprintf('C) Did not implement %s', __METHOD__));
    }
}

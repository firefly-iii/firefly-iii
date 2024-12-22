<?php

/**
 * VerifiesAccessToken.php
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

namespace FireflyIII\Console\Commands;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;

/**
 * Trait VerifiesAccessToken.
 *
 * Verifies user access token for sensitive commands.
 */
trait VerifiesAccessToken
{
    /**
     * @throws FireflyException
     */
    public function getUser(): User
    {
        $userId     = (int) $this->option('user');

        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->find($userId);
        if (null === $user) {
            throw new FireflyException('300000: User is unexpectedly NULL');
        }

        return $user;
    }

    /**
     * Abstract method to make sure trait knows about method "option".
     *
     * @param null|string $key
     *
     * @return mixed
     */
    abstract public function option($key = null);

    /**
     * Returns false when given token does not match given user token.
     *
     * @throws FireflyException
     */
    protected function verifyAccessToken(): bool
    {
        $userId      = (int) $this->option('user');
        $token       = (string) $this->option('token');

        /** @var UserRepositoryInterface $repository */
        $repository  = app(UserRepositoryInterface::class);
        $user        = $repository->find($userId);

        if (null === $user) {
            app('log')->error(sprintf('verifyAccessToken(): no such user for input "%d"', $userId));

            return false;
        }
        $accessToken = app('preferences')->getForUser($user, 'access_token');
        if (null === $accessToken) {
            app('log')->error(sprintf('User #%d has no access token, so cannot access command line options.', $userId));

            return false;
        }
        if ($accessToken->data !== $token) {
            app('log')->error(sprintf('Invalid access token for user #%d.', $userId));
            app('log')->error(sprintf('Token given is "%s", expected something else.', $token));

            return false;
        }

        return true;
    }
}

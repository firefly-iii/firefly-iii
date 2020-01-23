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
use Log;

/**
 * Trait VerifiesAccessToken.
 *
 * Verifies user access token for sensitive commands.
 *
 * @codeCoverageIgnore
 */
trait VerifiesAccessToken
{
    /**
     * @return User
     * @throws FireflyException
     */
    public function getUser(): User
    {
        $userId = (int)$this->option('user');
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->findNull($userId);
        if (null === $user) {
            throw new FireflyException('User is unexpectedly NULL');
        }

        return $user;
    }

    /**
     * Abstract method to make sure trait knows about method "option".
     *
     * @param string|null $key
     *
     * @return mixed
     */
    abstract public function option($key = null);

    /**
     * Returns false when given token does not match given user token.
     *
     * @return bool
     */
    protected function verifyAccessToken(): bool
    {
        $userId = (int)$this->option('user');
        $token  = (string)$this->option('token');
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->findNull($userId);

        if (null === $user) {
            Log::error(sprintf('verifyAccessToken(): no such user for input "%d"', $userId));

            return false;
        }
        $accessToken = app('preferences')->getForUser($user, 'access_token', null);
        if (null === $accessToken) {
            Log::error(sprintf('User #%d has no access token, so cannot access command line options.', $userId));

            return false;
        }
        if (!($accessToken->data === $token)) {
            Log::error(sprintf('Invalid access token for user #%d.', $userId));
            Log::error(sprintf('Token given is "%s", expected something else.', $token));

            return false;
        }

        return true;
    }
}
